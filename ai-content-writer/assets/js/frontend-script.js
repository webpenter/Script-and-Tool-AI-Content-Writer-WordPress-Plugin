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
        withImage: $('#aicw_with_image'), // Re-added
        
        generateBtn: $('#aicw_generate_btn'),
        spinner: $('.aicw-spinner'),
        errorMessage: $('.aicw-error-message'),
        resultSection: $('.aicw-result-section'),
        result: $('#aicw_result'),
        copyBtn: $('#aicw_copy_btn'),
        saveDraftBtn: $('#aicw_save_draft_btn'),
        copyMessage: $('.aicw-copy-message'),
        
        imagePreviewWrap: $('#aicw_image_preview_wrap'), // New
        imagePreview: $('#aicw_image_preview'),           // New
        imageStatus: $('#aicw_image_status')              // New
    };

    // 🔹 Collect form data efficiently
    window.getAICWData = function () {
        const data = {
            action: 'aicw_generate_content',
            nonce: aicwAjax.nonce,
            content_type: elements.contentType.val() || '',
            language: elements.language.val() || 'en',
            tone: elements.tone.val() || 'friendly',
            keywords: elements.keywords.val()?.trim() || '',
            topic: elements.topic.val()?.trim() || '',
            existing: elements.existing.val()?.trim() || '',
            with_image: elements.withImage.is(':checked') ? 1 : 0 // Re-added
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
            elements.withImage.prop('checked', false).prop('disabled', true); // Disable image on rewrite mode
        } else {
            label.text('Topic or Prompt');
            topicWrap.show();
            rewriteWrap.slideUp();
            elements.existing.val('');
            elements.withImage.prop('disabled', false);
        }
    }

    // 🔹 Optimized Generate Content with debouncing
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
        elements.imagePreviewWrap.hide(); // Hide image preview before new generation
        elements.spinner.show();
        elements.generateBtn.prop('disabled', true);

        // 🔹 Add timeout for AJAX request
        const ajaxPromise = $.ajax({
            url: aicwAjax.ajax_url,
            type: 'POST',
            data: data,
            dataType: 'json',
            timeout: 60000 // Increased timeout for potential image generation
        });

        // Handle AJAX with timeout
        $.when(ajaxPromise).then(
            function (response) {
                console.log("✅ AJAX success:", response);
                elements.spinner.hide();
                elements.generateBtn.prop('disabled', false);

                if (response && response.success) {
                    $('#aicw_result').val(response.data.content);
                    elements.resultSection.show();
                    
                    // Handle Image Response with Status and Message
                    if (response.data.image_url) {
                        elements.imagePreview.attr('src', response.data.image_url);
                        elements.imagePreviewWrap.show();

                        // Update status message based on status code
                        let statusHtml = '';
                        // FIX: Directly use the message from the backend (response.data.image_message)
                        const imageMessage = response.data.image_message || 'Unknown image status received.';
                        
                        if (response.data.image_status === 'success') {
                            statusHtml = '<span style="color: green; font-weight: 600;">✅ Success: </span>' + imageMessage;
                        } else if (response.data.image_status === 'placeholder_fallback') {
                             // Use yellow/orange for placeholder fallback
                            statusHtml = '<span style="color: #ff9900; font-weight: 600;">⚠️ Placeholder: </span>' + imageMessage;
                        } else {
                            // Use red for hard errors
                            statusHtml = '<span style="color: red; font-weight: 600;">❌ Error: </span>' + imageMessage;
                        }
                        elements.imageStatus.html(statusHtml);

                    } else {
                        elements.imagePreviewWrap.hide();
                    }
                    
                } else {
                    // This section handles content generation errors, not image errors
                    const msg = response?.data?.message || response?.message || 'Unknown error occurred.';
                    elements.errorMessage.text(msg).show();
                }
            },
            function (xhr, status, error) {
                console.error("❌ AJAX error:", status, error);
                elements.spinner.hide();
                elements.generateBtn.prop('disabled', false);
                
                if (status === 'timeout') {
                    elements.errorMessage.text('Request timeout. Please try again.').show();
                } else {
                    elements.errorMessage.text('Error occurred. Please try again.').show();
                }
            }
        );
    }, 500); // 500ms debounce

    // Event handlers
    elements.contentType.on('change', handleContentTypeChange);
    elements.generateBtn.on('click', handleGenerateContent);

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

        $.ajax({
            url: aicwAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'aicw_save_draft',
                nonce: aicwAjax.nonce,
                title: 'AI Generated Content',
                content: content
            },
            dataType: 'json',
            success: function (response) {
                if (response && response.success) {
                    alert(response.data.message);
                    if (response.data.edit_url) {
                        window.open(response.data.edit_url, '_blank');
                    }
                } else {
                    const msg = response?.data?.message || 'Failed to save draft.';
                    alert(msg);
                }
            },
            error: function (xhr) {
                console.error("❌ Save draft error:", xhr.responseText);
                alert('Error saving draft.');
            }
        });
    });

    // Initialize
    handleContentTypeChange();
});
