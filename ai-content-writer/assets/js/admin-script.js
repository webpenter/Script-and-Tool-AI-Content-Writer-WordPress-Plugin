jQuery(document).ready(function($) {
    // === ADMIN GENERATOR PAGE LOGIC (Original - Global Generator Page) ===
    
    // Generate content
    $('#aicw_generate_btn').on('click', function() {
        var topic = $('#aicw_topic').val().trim();
        var contentType = $('#aicw_content_type').val();
        
        if (!topic) {
            alert('Please enter a topic or prompt.');
            return;
        }
        
        $('.aicw-result-section').hide();
        $('.aicw-error-message').hide();
        $('.aicw-spinner').show();
        $('#aicw_generate_btn').prop('disabled', true);
        
        // Note: Full data collection for the main generator page is now handled by frontend-script.js
        $.ajax({
            url: aicwAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'aicw_generate_content',
                nonce: aicwAjax.nonce,
                topic: topic,
                content_type: contentType
            },
            success: function(response) {
                $('.aicw-spinner').hide();
                $('#aicw_generate_btn').prop('disabled', false);
                
                if (response.success) {
                    $('#aicw_result').val(response.data.content);
                    $('.aicw-result-section').show();
                    // Image status logic for the main generator page is in frontend-script.js
                } else {
                    $('.aicw-error-message').text(response.data.message).show();
                }
            },
            error: function(xhr, status, error) {
                $('.aicw-spinner').hide();
                $('#aicw_generate_btn').prop('disabled', false);
                $('.aicw-error-message').text('An error occurred. Please try again. Error: ' + error).show();
            }
        });
    });
    
    // Copy to clipboard (Main Generator Page)
    $('#aicw_copy_btn').on('click', function() {
        var content = $('#aicw_result').val();
        if (!content) return;
        
        navigator.clipboard.writeText(content).then(function() {
            $('.aicw-copy-message').fadeIn().delay(2000).fadeOut();
        }).catch(function() {
            // Fallback for older browsers
            var textarea = document.createElement('textarea');
            textarea.value = content;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            $('.aicw-copy-message').fadeIn().delay(2000).fadeOut();
        });
    });
    

    // Validate API Key
    $('.aicw-validate-key').off('click').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $input = $('#aicw_gemini_api_key');
        var apiKey = $input.val().trim();

        if (!apiKey) {
            alert('Please enter an API key for validation.');
            return;
        }

        $button.prop('disabled', true).text('Validating...');
        $('#aicw_validation_results').html('<div class="notice notice-info"><p>Validating API key...</p></div>');

        $.ajax({
            url: aicwAjax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'aicw_validate_api_key',
                nonce: aicwAjax.validate_nonce,
                api_key: apiKey
            },
            success: function(response) {
                console.log(response); // Debug

                var message = response.data && response.data.message ? response.data.message : 'No message returned';

                if (response.success) {
                    $('#aicw_validation_results').html('<div class="notice notice-success"><p>✅ ' + message + '</p></div>');
                } else {
                    $('#aicw_validation_results').html('<div class="notice notice-error"><p>❌ ' + message + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr, status, error);
                $('#aicw_validation_results').html('<div class="notice notice-error"><p>❌ Validation request failed. Please try again.</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Validate API Key');
            }
        });
    });

    // Show/hide API key
    if (!$('#aicw_gemini_api_key').next().hasClass('aicw-password-toggle')) {
        $('#aicw_gemini_api_key').after('<button type="button" class="button button-small aicw-password-toggle" style="margin-left: 5px;">Show</button>');
    }

    $(document).off('click', '.aicw-password-toggle').on('click', '.aicw-password-toggle', function() {
        var $input = $('#aicw_gemini_api_key');
        var $button = $(this);

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $button.text('Hide');
        } else {
            $input.attr('type', 'password');
            $button.text('Show');
        }
    });

    // === META BOX LOGIC (Post/Page Editor) ===
    
    const $mbContentType = $('#aicw_mb_content_type');
    const $mbTopicWrap = $('#aicw_mb_topic_wrap');
    const $mbRewriteWrap = $('#aicw_mb_rewrite_wrap');
    
    // Helper to get content from main editor (Classic/Gutenberg)
    function getEditorContent() {
        if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
            // Classic Editor (TinyMCE)
            return tinymce.get('content').getContent();
        } else if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            // Gutenberg (Block Editor)
            const blocks = wp.data.select('core/editor').getBlocks();
            // Simple approach: serialize all blocks to HTML
            return blocks.length ? wp.blocks.serialize(blocks) : '';
        } else {
            // Fallback for non-standard editors
            return $('#content').val() || '';
        }
    }

    // Content Type Change Handler for Meta Box
    $mbContentType.on('change', function() {
        const selected = $mbContentType.val();
        const label = $('label[for="aicw_mb_topic"]');
        
        if (selected === 'rewrite' || selected === 'summary') {
            label.text('Topic or Prompt (Optional)');
            $mbTopicWrap.hide();
            $mbRewriteWrap.slideDown();
        } else {
            label.text('Topic or Prompt');
            $mbTopicWrap.show();
            $mbRewriteWrap.slideUp();
            $('#aicw_mb_existing').val('');
        }
    }).trigger('change');
    
    // Helper function to collect Meta Box data
    function getMetaBoxData() {
        let existingContent = $('#aicw_mb_existing').val().trim();
        const contentType = $mbContentType.val();
        
        if ((contentType === 'rewrite' || contentType === 'summary') && !existingContent) {
            existingContent = getEditorContent().trim();
        }
        
        return {
            action: 'aicw_generate_content',
            nonce: aicwAjax.nonce,
            content_type: contentType || '',
            tone: $('#aicw_mb_tone').val() || 'friendly',
            language: $('#aicw_mb_language').val() || 'en',
            keywords: $('#aicw_mb_keywords').val().trim() || '',
            topic: $('#aicw_mb_topic').val().trim() || '',
            existing: existingContent,
            with_image: $('#aicw_mb_with_image').is(':checked') ? 1 : 0
        };
    }

    // Helper function to set the Post Title
    function setPostTitle(content) {
        if (!content) return;
        
        // Find the first markdown heading (# or ##)
        let match = content.match(/^#+\s*(.*)\s*$/m);
        let title = match ? match[1].trim() : content.substring(0, 50).trim() + '...';
        
        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
            // Gutenberg Title Setter
            wp.data.dispatch('core/editor').editPost({ title: title });
        } else {
            // Classic/Fallback Title Setter
            $('#title').val(title);
        }
    }

    // Helper function to set the Featured Image
function setFeaturedImage(attachmentId) {
    if (!attachmentId || attachmentId === 0) return;

    // Gutenberg Editor
    if (
        typeof wp !== 'undefined' &&
        wp.data &&
        wp.data.dispatch &&
        wp.data.select('core/editor')
    ) {
        wp.data.dispatch('core/editor').editPost({
            featured_media: attachmentId
        });

        // console.log('AICW: Featured image set in Gutenberg:', attachmentId);
    }
    // Classic Editor fallback
    else {
        $('#_thumbnail_id').val(attachmentId);

        // Visual feedback for Classic Editor
        if ($('#postimagediv').length) {
            $('#postimagediv .inside').prepend(
                '<div class="notice notice-success inline"><p>Featured image set successfully.</p></div>'
            );
        }

        // console.log('AICW: Featured image set in Classic Editor:', attachmentId);
    }
}

    // Insert Content into Editor Logic
    function insertContentIntoEditor(content) {
        if (!content) return;
        content = marked.parse(content); // Convert markdown to HTML
        // Use setTimeout to ensure the editor is ready/focused
        setTimeout(function() {
            let inserted = false;
            
            // Markdown to HTML conversion
            const htmlContent = typeof marked === 'function' ? marked.parse(content) : content;

            // 1. Classic Editor (TinyMCE)
            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                const editor = tinymce.get('content');
                editor.focus();
                editor.execCommand('mceInsertContent', false, '\n\n' + htmlContent + '\n\n');
                inserted = true;
            } 
            
            // 2. Gutenberg (Block Editor) - Use insertBlocks for stability
            else if (typeof wp !== 'undefined' && wp.data && wp.blocks && wp.data.select('core/editor')) {
                // Parse HTML content into Gutenberg blocks
                const blocks = wp.blocks.rawHandler({ HTML: htmlContent });
                wp.data.dispatch('core/editor').insertBlocks(blocks);
                inserted = true;
            } 
            
            // 3. Fallback (Direct Textarea)
            else {
                const $contentField = $('#content');
                $contentField.val($contentField.val() + '\n\n' + content + '\n\n');
                inserted = true;
            }
            
            if (!inserted) {
                 console.error('AICW: Content insertion failed.');
            }
        }, 500); // 500ms delay for editor readiness
    }


    // Generate Content button click handler for Meta Box (Now auto-inserts)
    $('#aicw_mb_generate_btn').on('click', function(e) {
        e.preventDefault();
        const data = getMetaBoxData();
        const isRewriteOrSummary = data.content_type === 'rewrite' || data.content_type === 'summary';

        // Basic Validation
        if (!isRewriteOrSummary && !data.topic) {
            alert('Please enter a topic or prompt.');
            return;
        } else if (isRewriteOrSummary && !data.topic && !data.existing) {
             alert('Please enter a topic OR paste/enter content to rewrite/summarize.');
             return;
        }

        // UI Updates
        $('#aicw_mb_error_message').hide();
        $('#aicw_mb_image_status').hide().html('');
        $('#aicw_mb_spinner').show();
        $(this).prop('disabled', true);

        $.ajax({
            url: aicwAjax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                $('#aicw_mb_spinner').hide();
                $('#aicw_mb_generate_btn').prop('disabled', false);
                
                if (response.success && response.data.content) {
                    const content = response.data.content;
                    
                    // 🎯 STEP 1: Set Post Title (must happen before content insert)
                    setPostTitle(content);
                    
                    // 🎯 STEP 2: Insert content automatically
                    insertContentIntoEditor(content);
                    
                    // 🎯 STEP 3: Handle Image and Featured Image Setting
                    if (response.data.image_id && response.data.image_status !== 'critical_error') {
                        setFeaturedImage(response.data.image_id);
                    }
                    
                    // 🎯 STEP 4: Display Image Status/Message
                    if (response.data.image_status) {
                        let statusHtml = '';
                        let icon = 'ℹ️';
                        let cssClass = 'notice-info'; 
                        
                        if (response.data.image_status === 'success') {
                            icon = '✅';
                            cssClass = 'notice-success';
                        } else if (response.data.image_status === 'placeholder_fallback' || response.data.image_status === 'critical_error') {
                            icon = '⚠️';
                            cssClass = 'notice-warning'; 
                        }

                        statusHtml = `<div class="notice ${cssClass} is-dismissible" style="margin: 0; padding: 10px; font-size: 13px;">${icon} ${response.data.image_message}</div>`;
                        $('#aicw_mb_image_status').html(statusHtml).show();
                    }

                } else {
                    const msg = response.data.message || 'Unknown error occurred during generation.';
                    $('#aicw_mb_error_message').text(msg).show();
                }
            },
            error: function(xhr, status, error) {
                $('#aicw_mb_spinner').hide();
                $('#aicw_mb_generate_btn').prop('disabled', false);
                $('#aicw_mb_error_message').text('An AJAX error occurred: ' + error).show();
            }
        });
    });

    // Removed: Insert into Editor button handler - now auto-inserted

    // Copy to clipboard (Meta Box) - Keep this for manual copy
    $('#aicw_mb_copy_btn').on('click', function() {
        var content = $('#aicw_mb_result').val();
        if (!content) return;
        
        var textarea = document.createElement('textarea');
        textarea.value = content;
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            alert('Content copied to clipboard!'); // Simple alert for meta box
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
        }
        document.body.removeChild(textarea);
    });
});
