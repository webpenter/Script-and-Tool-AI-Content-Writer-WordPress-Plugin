<?php
/**
 * AI Content Writer Meta Box Template
 * Used in Post/Page Edit Screen
 */

// If API key is missing, show warning
if (empty($api_key)):
    printf(
        '<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
        esc_html__('Please configure your Gemini API key in the settings.', 'ai-content-writer'),
        esc_url(admin_url('admin.php?page=ai-content-writer-settings')),
        esc_html__('Go to Settings', 'ai-content-writer')
    );
endif;
?>

<div class="aicw-meta-box-container aicw-form-section">
    <!-- 1. Content Type -->
    <div class="aicw-input-group">
        <label for="aicw_mb_content_type"><?php _e('Content Type', 'ai-content-writer'); ?></label>
        <select id="aicw_mb_content_type">
            <option value="blog_post"><?php _e('Blog Post', 'ai-content-writer'); ?></option>
            <option value="product_description"><?php _e('Product Description', 'ai-content-writer'); ?></option>
            <option value="faq"><?php _e('FAQs', 'ai-content-writer'); ?></option>
            <option value="social_media"><?php _e('Social Media Post', 'ai-content-writer'); ?></option>
            <option value="email"><?php _e('Email', 'ai-content-writer'); ?></option>
            <option value="rewrite"><?php _e('Content Rewriter', 'ai-content-writer'); ?></option>
            <option value="summary"><?php _e('Summary', 'ai-content-writer'); ?></option>
        </select>
    </div>

    <!-- 2. Writing Tone -->
    <div class="aicw-input-group">
        <label for="aicw_mb_tone"><?php _e('Writing Tone', 'ai-content-writer'); ?></label>
        <select id="aicw_mb_tone">
            <option value="friendly"><?php _e('Friendly', 'ai-content-writer'); ?></option>
            <option value="professional"><?php _e('Professional', 'ai-content-writer'); ?></option>
            <option value="witty"><?php _e('Witty', 'ai-content-writer'); ?></option>
            <option value="formal"><?php _e('Formal', 'ai-content-writer'); ?></option>
            <option value="casual"><?php _e('Casual', 'ai-content-writer'); ?></option>
        </select>
    </div>

    <!-- 3. Output Language -->
    <div class="aicw-input-group">
        <label for="aicw_mb_language"><?php _e('Output Language', 'ai-content-writer'); ?></label>
        <select id="aicw_mb_language">
            <option value="en">English</option>
            <option value="es">Español</option>
            <option value="fr">Français</option>
            <option value="de">Deutsch</option>
            <option value="hi">हिन्दी</option>
            <option value="ur">اردو</option>
        </select>
    </div>

    <!-- 4. SEO Keywords -->
    <div class="aicw-input-group" id="aicw_mb_keywords_wrap">
        <label for="aicw_mb_keywords"><?php _e('SEO Keywords (Optional)', 'ai-content-writer'); ?></label>
        <textarea id="aicw_mb_keywords" rows="2" placeholder="<?php esc_attr_e('Comma-separated list', 'ai-content-writer'); ?>"></textarea>
    </div>

    <!-- 5. Existing content (rewrite/summary mode only) -->
    <div class="aicw-input-group" id="aicw_mb_rewrite_wrap" style="display:none;">
        <label for="aicw_mb_existing"><?php _e('Paste existing content to improve / summarise', 'ai-content-writer'); ?></label>
        <textarea id="aicw_mb_existing" rows="4" placeholder="Paste here..."></textarea>
    </div>

    <!-- 6. Topic -->
    <div class="aicw-input-group" id="aicw_mb_topic_wrap">
        <label for="aicw_mb_topic"><?php _e('Topic or Prompt', 'ai-content-writer'); ?></label>
        <textarea id="aicw_mb_topic" rows="3" placeholder="<?php esc_attr_e('Enter your topic...', 'ai-content-writer'); ?>"></textarea>
    </div>

    <!-- 7. Optional image -->
    <label><input type="checkbox" id="aicw_mb_with_image"> <?php _e('Also create featured image', 'ai-content-writer'); ?></label>
    
    <hr style="margin: 15px 0;">

    <!-- 8. Generate button -->
    <button id="aicw_mb_generate_btn" class="button button-primary aicw-generate-button" <?php disabled(empty($api_key)); ?>>
        <span class="aicw-button-text"><?php _e('Generate Content', 'ai-content-writer'); ?></span>
        <span class="aicw-button-icon">✨</span>
    </button>

    <div class="aicw-spinner" id="aicw_mb_spinner" style="display:none;">
        <div class="aicw-spinner-animation"></div>
        <span class="aicw-spinner-text"><?php _e('Generating...', 'ai-content-writer'); ?></span>
    </div>

    <!-- Result / Status -->
    <div class="aicw-result-section" id="aicw_mb_result_section" style="display:none;">
        <div class="aicw-result-header" style="flex-direction: column; align-items: flex-start;">
            <h4 style="margin-bottom: 5px;"><?php _e('Generated Content', 'ai-content-writer'); ?></h4>
        </div>
        <textarea id="aicw_mb_result" rows="8" style="width: 100%; min-height: 150px;" readonly></textarea>
        
        <div style="margin-top: 10px; display: flex; gap: 8px;">
            <button id="aicw_mb_insert_btn" class="button button-secondary"><?php _e('Insert into Editor', 'ai-content-writer'); ?></button>
            <button id="aicw_mb_copy_btn" class="button"><?php _e('Copy', 'ai-content-writer'); ?></button>
        </div>
    </div>
    
    <div class="aicw-image-status" id="aicw_mb_image_status" style="margin-top: 10px; display: none;"></div>

    <div class="aicw-error-message" id="aicw_mb_error_message" style="display:none;"></div>
</div>
