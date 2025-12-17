jQuery(document).ready(function ($) {
    console.log("✅ AI Content Writer JS loaded");

    // 🔹 Cache DOM elements for better performance
    const elements = {
        contentType: $('#aicw_content_type'),
        language: $('#aicw_language'),
        tone: $('#aicw_tone'),
        keywords: $('#aicw_keywords'),
        
        topic: $('#aicw_topic'),
        existing: $('#aicw_existing'),
        withImage: $('#aicw_with_image'),
        
        generateBtn: $('#aicw_generate_btn'),
        spinner: $('.aicw-spinner'),
        errorMessage: $('.aicw-error-message'),
        resultSection: $('.aicw-result-section'),
        result: $('#aicw_result'),
        copyBtn: $('#aicw_copy_btn'),
        saveDraftBtn: $('#aicw_save_draft_btn'),
        copyMessage: $('.aicw-copy-message'),
        
        imagePreviewWrap: $('#aicw_image_preview_wrap'),
        imagePreview: $('#aicw_image_preview'),
        imageStatus: $('#aicw_image_status')
    };

    // 🔹 Global variable to store the last generated image ID
    let lastGeneratedImageId = 0;
    
    // 🔹 Retry configuration
    const RETRY_CONFIG = {
        maxRetries: 3,
        retryDelay: 3000, // 3 seconds
        retryMessages: [
            'Model overloaded',
            'try again',
            'service unavailable',
            '503',
            '429'
        ]
    };

    // 🔹 Collect form data efficiently
    window.getAICWData = function () {
        // Get post ID from WordPress
        let post_id = 0;
        
        // Try to get post ID from WordPress editor
        if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            try {
                post_id = wp.data.select('core/editor').getCurrentPostId() || 0;
            } catch (e) {
                console.log("Could not get post ID from editor:", e);
            }
        }
        
        // Try to get from URL (for edit post page)
        if (!post_id) {
            const urlParams = new URLSearchParams(window.location.search);
            post_id = urlParams.get('post') || 0;
        }
        
        console.log("📝 Current Post ID:", post_id);
        
        const data = {
            action: 'aicw_generate_content',
            nonce: aicwAjax.nonce,
            content_type: elements.contentType.val() || '',
            language: elements.language.val() || 'en',
            tone: elements.tone.val() || 'friendly',
            keywords: elements.keywords.val()?.trim() || '',
            topic: elements.topic.val()?.trim() || '',
            existing: elements.existing.val()?.trim() || '',
            with_image: elements.withImage.is(':checked') ? 1 : 0,
            post_id: post_id,
        };
        
        console.log("📦 AJAX Data to send:", data);
        return data;
    };

    // 🔹 Debounce function to prevent multiple rapid clicks
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // 🔹 Check if error message indicates retry is needed
    function shouldRetry(errorMessage) {
        if (!errorMessage) return false;
        const lowerMsg = errorMessage.toLowerCase();
        return RETRY_CONFIG.retryMessages.some(msg => lowerMsg.includes(msg));
    }

    // 🔹 Update button text with retry countdown
    function updateButtonWithCountdown(seconds, attemptNumber) {
        const countdown = setInterval(() => {
            if (seconds > 0) {
                elements.generateBtn.text(`Retrying in ${seconds}s... (Attempt ${attemptNumber}/${RETRY_CONFIG.maxRetries})`);
                seconds--;
            } else {
                clearInterval(countdown);
            }
        }, 1000);
        return countdown;
    }

    // 🔹 Main AJAX request with retry logic
    function makeAjaxRequest(data, attemptNumber = 1) {
        console.log(`🔄 Attempt ${attemptNumber}/${RETRY_CONFIG.maxRetries}`);
        
        return $.ajax({
            url: aicwAjax.ajax_url,
            type: 'POST',
            data: data,
            dataType: 'json',
            timeout: 60000
        }).then(
            function (response) {
                // Success handler
                return Promise.resolve(response);
            },
            function (xhr, status, error) {
                // Error handler - check if we should retry
                let errorMessage = '';
                
                try {
                    const jsonResponse = JSON.parse(xhr.responseText);
                    errorMessage = jsonResponse?.data?.message || jsonResponse?.message || error;
                } catch (e) {
                    errorMessage = error || status;
                }
                
                console.log(`❌ Attempt ${attemptNumber} failed:`, errorMessage);
                
                // Check if we should retry
                if (shouldRetry(errorMessage) && attemptNumber < RETRY_CONFIG.maxRetries) {
                    console.log(`⏳ Will retry after ${RETRY_CONFIG.retryDelay / 1000} seconds...`);
                    
                    // Show countdown
                    let secondsLeft = Math.floor(RETRY_CONFIG.retryDelay / 1000);
                    const countdown = updateButtonWithCountdown(secondsLeft, attemptNumber + 1);
                    
                    elements.errorMessage
                        .html(`⚠️ ${errorMessage}<br>Retrying automatically... (Attempt ${attemptNumber}/${RETRY_CONFIG.maxRetries})`)
                        .show();
                    
                    // Wait and retry
                    return new Promise((resolve) => {
                        setTimeout(() => {
                            clearInterval(countdown);
                            elements.generateBtn.text('Generating...');
                            resolve(makeAjaxRequest(data, attemptNumber + 1));
                        }, RETRY_CONFIG.retryDelay);
                    });
                }
                
                // No more retries or different error
                return Promise.reject({ status, error, errorMessage, xhr });
            }
        );
    }

    // 🔹 Handle Content Type Change
    function handleContentTypeChange() {
        const selected = elements.contentType.val();
        const label = $('label[for="aicw_topic"]');
        const topicWrap = $('#aicw_topic_wrap');
        const rewriteWrap = $('#aicw_rewrite_wrap');

        if (selected === 'rewrite') {
            label.text('Rewrite Content');
            topicWrap.hide();
            rewriteWrap.slideDown();
            elements.topic.val('');
            elements.withImage.prop('checked', false).prop('disabled', true);
        } else {
            label.text('Topic or Prompt');
            topicWrap.show();
            rewriteWrap.slideUp();
            elements.existing.val('');
            elements.withImage.prop('disabled', false);
        }
    }

    // 🔹 Optimized Generate Content with retry logic
    const handleGenerateContent = debounce(function (e) {
        e.preventDefault();
        console.log("🚀 Generate button clicked");

        const data = getAICWData();

        // Quick validation
        if (data.content_type === 'rewrite' && !data.existing) {
            alert('Please paste the text you want to rewrite.');
            return;
        } else if (data.content_type !== 'rewrite' && !data.topic) {
            alert('Please enter a topic or prompt.');
            return;
        }

        // UI updates
        elements.errorMessage.hide();
        elements.resultSection.hide();
        elements.imagePreviewWrap.hide();
        elements.spinner.show();
        elements.generateBtn.prop('disabled', true).text('Generating...');

        // 🔹 Make AJAX request with retry logic
        makeAjaxRequest(data)
            .then(function (response) {
                console.log("✅ AJAX success:", response);
                elements.spinner.hide();
                elements.generateBtn.prop('disabled', false).text('Generate Content');
                elements.errorMessage.hide();

                if (response && response.success) {
                    $('#aicw_result').val(response.data.content);
                    elements.resultSection.show();
                    
                    // ✅ Store the image ID for later use
                    if (response.data.image_id && response.data.image_id > 0) {
                        lastGeneratedImageId = response.data.image_id;
                        console.log("📸 Stored image ID:", lastGeneratedImageId);
                        
                        // Store in hidden field
                        $('#aicw_last_image_id').remove();
                        $('<input>').attr({
                            type: 'hidden',
                            id: 'aicw_last_image_id',
                            value: lastGeneratedImageId
                        }).appendTo('body');
                        
                        // Store in image preview data attribute
                        if (response.data.image_url) {
                            elements.imagePreview.data('attachment-id', lastGeneratedImageId);
                        }
                    }
                    
                    // Handle Image Response
                    if (response.data.image_url) {
                        elements.imagePreview.attr('src', response.data.image_url);
                        elements.imagePreviewWrap.show();

                        // Build status message
                        let statusHtml = '';
                        const imageMessage = response.data.image_message || 'Image generated';
                        
                        if (response.data.image_status === 'success' || response.data.image_status === 'pixabay_success') {
                            statusHtml = '<span style="color: green; font-weight: 600;">✅ Success: </span>' + imageMessage;
                        } else if (response.data.image_status === 'placeholder_fallback') {
                            statusHtml = '<span style="color: #ff9900; font-weight: 600;">⚠️ Placeholder: </span>' + imageMessage;
                        } else {
                            statusHtml = '<span style="color: red; font-weight: 600;">❌ Error: </span>' + imageMessage;
                        }
                        
                        // Add image ID to status
                        if (response.data.image_id) {
                            statusHtml += ' <span style="color: #666;">(ID: ' + response.data.image_id + ')</span>';
                        }
                        
                        // Add featured image status
                        if (response.data.featured_set) {
                            if (response.data.featured_set === 'yes') {
                                statusHtml += ' <span style="color: green;">(Featured ✓)</span>';
                                refreshFeaturedImageUI(response.data.image_id, response.data.image_url);
                            } else if (response.data.featured_set === 'no') {
                                statusHtml += ' <span style="color: orange;">(Featured ✗)</span>';
                            }
                        }
                        
                        elements.imageStatus.html(statusHtml);
                    } else {
                        elements.imagePreviewWrap.hide();
                    }
                    
                } else {
                    const msg = response?.data?.message || response?.message || 'Unknown error occurred.';
                    elements.errorMessage.text('❌ ' + msg).show();
                }
            })
            .catch(function (errorData) {
                console.error("❌ AJAX final error:", errorData);
                elements.spinner.hide();
                elements.generateBtn.prop('disabled', false).text('Generate Content');
                
                let errorMsg = '❌ ';
                if (errorData.status === 'timeout') {
                    errorMsg += 'Request timeout. Please try again.';
                } else if (errorData.errorMessage) {
                    errorMsg += errorData.errorMessage;
                    if (shouldRetry(errorData.errorMessage)) {
                        errorMsg += '<br><br>💡 <strong>Tip:</strong> The AI service is currently busy. Please wait a few moments and try again.';
                    }
                } else {
                    errorMsg += 'Error occurred. Please try again.';
                }
                
                elements.errorMessage.html(errorMsg).show();
            });
    }, 500);

    // 🔥 Function to refresh WordPress featured image UI
    function refreshFeaturedImageUI(imageId, imageUrl) {
        console.log("🔄 Attempting to refresh featured image UI for ID:", imageId);
        
        // Method 1: Use WordPress REST API if available
        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
            try {
                // Update the post's featured image in WordPress store
                wp.data.dispatch('core/editor').editPost({
                    featured_media: imageId
                });
                
                console.log("✅ Updated featured image via WordPress data store");
                
                // Force a UI refresh
                setTimeout(() => {
                    if (wp.data && wp.data.dispatch && wp.data.dispatch('core/editor').refreshPost) {
                        wp.data.dispatch('core/editor').refreshPost();
                        console.log("✅ Refreshed post data");
                    }
                }, 1000);
                
            } catch (error) {
                console.log("⚠️ Could not update via WordPress store:", error);
            }
        }
        
        // Method 2: Simulate a click on the featured image to refresh it
        setTimeout(() => {
            const featuredImageContainer = document.querySelector('.editor-post-featured-image');
            if (featuredImageContainer) {
                console.log("🔄 Found featured image container, attempting refresh...");
                
                // Method 2a: Update the image src directly
                const existingImage = featuredImageContainer.querySelector('img');
                if (existingImage && imageUrl) {
                    existingImage.src = imageUrl;
                    console.log("✅ Updated image src directly");
                }
                
                // Method 2b: Trigger a custom event
                const event = new CustomEvent('aicw-featured-image-updated', {
                    detail: { imageId: imageId, imageUrl: imageUrl }
                });
                document.dispatchEvent(event);
            }
        }, 500);
        
        // Method 3: Show notification to refresh page
        setTimeout(() => {
            if (!document.querySelector('.aicw-refresh-notice')) {
                const notice = document.createElement('div');
                notice.className = 'notice notice-info aicw-refresh-notice is-dismissible';
                notice.style.cssText = 'margin: 10px 0; padding: 10px; background: #f0f6fc; border-left: 4px solid #72aee6;';
                notice.innerHTML = `
                    <p><strong>Featured Image Updated!</strong> The image has been set as featured. If it doesn't appear, please refresh the page or click on the featured image area.</p>
                    <button type="button" class="notice-dismiss" onclick="this.parentElement.remove()">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                `;
                
                const adminNotices = document.querySelector('.wrap h1')?.parentNode;
                if (adminNotices) {
                    adminNotices.insertBefore(notice, adminNotices.firstChild);
                }
            }
        }, 1000);
    }

    // Copy result button
    elements.copyBtn.on('click', function () {
        const content = elements.result.val();
        if (!content) return;

        navigator.clipboard.writeText(content).then(() => {
            elements.copyMessage.fadeIn().delay(2000).fadeOut();
        });
    });

    // Save as Draft button
    elements.saveDraftBtn.on('click', function () {
        const content = elements.result.val();
        if (!content) {
            alert('No content to save.');
            return;
        }

        // Get image ID from multiple sources
        let image_id = 0;
        
        // 1. Try global variable
        if (lastGeneratedImageId > 0) {
            image_id = lastGeneratedImageId;
        }
        
        // 2. Try hidden field
        if (!image_id) {
            const hiddenId = $('#aicw_last_image_id').val();
            if (hiddenId && parseInt(hiddenId) > 0) {
                image_id = parseInt(hiddenId);
            }
        }
        
        // 3. Try data attribute from image preview
        if (!image_id && elements.imagePreviewWrap.is(':visible')) {
            const storedId = elements.imagePreview.data('attachment-id');
            if (storedId) {
                image_id = parseInt(storedId);
            }
        }
        
        console.log("💾 Saving draft with:", {
            contentLength: content.length,
            image_id: image_id,
            hasImage: elements.imagePreviewWrap.is(':visible')
        });
        
        // Show loading state
        const originalText = elements.saveDraftBtn.text();
        elements.saveDraftBtn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: aicwAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'aicw_save_draft',
                nonce: aicwAjax.nonce,
                title: 'AI Generated Content',
                content: content,
                image_id: image_id
            },
            dataType: 'json',
            timeout: 30000,
            success: function (response) {
                console.log("💾 Save draft response:", response);
                elements.saveDraftBtn.prop('disabled', false).text(originalText);
                
                if (response && response.success) {
                    let message = response.data.message;
                    if (response.data.featured_set === 'yes') {
                        message += '\n✅ Featured image set!';
                    } else if (image_id > 0) {
                        message += '\n⚠️ Image saved in media library';
                    }
                    
                    alert(message);
                    
                    // Open edit page after a short delay
                    if (response.data.edit_url) {
                        setTimeout(() => {
                            window.open(response.data.edit_url, '_blank');
                        }, 500);
                    }
                    
                } else {
                    const msg = response?.data?.message || 'Failed to save draft.';
                    alert('❌ ' + msg);
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ Save draft error:", {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    readyState: xhr.readyState,
                    statusCode: xhr.status
                });
                
                elements.saveDraftBtn.prop('disabled', false).text(originalText);
                
                let errorMsg = 'Error saving draft. ';
                if (status === 'timeout') {
                    errorMsg += 'Request timed out.';
                } else if (xhr.responseText) {
                    try {
                        const jsonResponse = JSON.parse(xhr.responseText);
                        errorMsg += jsonResponse.message || jsonResponse.data?.message || '';
                    } catch (e) {
                        errorMsg += 'Server error occurred.';
                    }
                }
                
                alert('❌ ' + errorMsg);
            }
        });
    });

    // Event handlers
    elements.contentType.on('change', handleContentTypeChange);
    elements.generateBtn.on('click', handleGenerateContent);

    // Initialize
    handleContentTypeChange();
});