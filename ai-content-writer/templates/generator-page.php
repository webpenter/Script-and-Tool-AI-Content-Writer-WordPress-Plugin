<?php
/**
 * Admin generator-page template
 *
 * @var string $api_key  The decrypted Gemini key (already supplied by class-main.php)
 */
if (empty($api_key)):
    printf(
        '<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
        esc_html__('Please configure your Gemini API key in the settings.', 'ai-content-writer'),
        esc_url(admin_url('admin.php?page=ai-content-writer-settings')),
        esc_html__('Go to Settings', 'ai-content-writer')
    );
endif;
?>

<div class="wrap aicw-generator-wrap">
    <h1><?php esc_html_e('AI Content Generator', 'ai-content-writer'); ?></h1>

    <div class="aicw-generator-container">
        <div class="aicw-form-section">

            <!-- 1. Content Type -->
            <div class="aicw-input-group">
                <label for="aicw_content_type"><?php _e('Content Type', 'ai-content-writer'); ?></label>
                <select id="aicw_content_type">
                    <option value="blog_post"><?php _e('Blog Post', 'ai-content-writer'); ?></option>
                    <option value="product_description"><?php _e('Product Description', 'ai-content-writer'); ?></option>
                    <option value="faq"><?php _e('FAQs', 'ai-content-writer'); ?></option>
                    <option value="social_media"><?php _e('Social Media Post', 'ai-content-writer'); ?></option>
                    <option value="email"><?php _e('Email', 'ai-content-writer'); ?></option>
                    <option value="rewrite"><?php _e('Content Rewriter', 'ai-content-writer'); ?></option>
                </select>
            </div>

            <!-- 2. Writing Tone -->
            <div class="aicw-input-group">
                <label for="aicw_tone"><?php _e('Writing Tone', 'ai-content-writer'); ?></label>
                <select id="aicw_tone">
                    <option value="friendly"><?php _e('Friendly', 'ai-content-writer'); ?></option>
                    <option value="professional"><?php _e('Professional', 'ai-content-writer'); ?></option>
                    <option value="witty"><?php _e('Witty', 'ai-content-writer'); ?></option>
                    <option value="formal"><?php _e('Formal', 'ai-content-writer'); ?></option>
                    <option value="casual"><?php _e('Casual', 'ai-content-writer'); ?></option>
                </select>
            </div>

            <!-- 3. Language -->
            <div class="aicw-input-group">
                <label for="aicw_language"><?php _e('Output Language', 'ai-content-writer'); ?></label>
                <select id="aicw_language">
                    <option value="en">English</option>
                    <option value="es">Español</option>
                    <option value="fr">Français</option>
                    <option value="de">Deutsch</option>
                    <option value="hi">हिन्दी</option>
                    <option value="ur">اردو</option>
                </select>
            </div>

            <!-- 4. SEO Keywords -->
            <div class="aicw-input-group" id="aicw_keywords_wrap">
                <label for="aicw_keywords"><?php _e('SEO Keywords (Optional)', 'ai-content-writer'); ?></label>
                <textarea id="aicw_keywords" rows="2" placeholder="<?php esc_attr_e('Comma-separated list (e.g., AI, content, generator)', 'ai-content-writer'); ?>"></textarea>
            </div>

            <!-- 5. Existing content (rewrite mode only) -->
            <div class="aicw-input-group" id="aicw_rewrite_wrap" style="display:none;">
                <label for="aicw_existing"><?php _e('Paste existing content to improve / summarise', 'ai-content-writer'); ?></label>
                <textarea id="aicw_existing" rows="5" placeholder="Paste here..."></textarea>
            </div>

            <!-- 6. Topic -->
            <div class="aicw-input-group" id="aicw_topic_wrap">
                <label for="aicw_topic"><?php _e('Topic or Prompt', 'ai-content-writer'); ?></label>
                <textarea id="aicw_topic" rows="4" placeholder="<?php esc_attr_e('Enter your topic...', 'ai-content-writer'); ?>"></textarea>
            </div>

            <!-- 7. Optional image -->
            <label><input type="checkbox" id="aicw_with_image"> <?php _e('Also create featured image', 'ai-content-writer'); ?></label>

            <!-- 8. Generate button -->
            <button id="aicw_generate_btn" class="aicw-generate-button" <?php disabled(empty($api_key)); ?>>
                <span class="aicw-button-text"><?php _e('Generate Content', 'ai-content-writer'); ?></span>
                <span class="aicw-button-icon">✨</span>
            </button>

            <div class="aicw-spinner" style="display:none;">
                <div class="aicw-spinner-animation"></div>
                <span class="aicw-spinner-text"><?php _e('Generating...', 'ai-content-writer'); ?></span>
            </div>
        </div>

        <!-- Result -->
        <div class="aicw-result-section" style="display:none;">
            <div class="aicw-result-header">
                <h3><?php _e('Generated Content', 'ai-content-writer'); ?></h3>
                <div class="aicw-result-meta">
                    <button id="aicw_copy_btn" class="aicw-copy-button"><span>📋</span> <?php _e('Copy', 'ai-content-writer'); ?></button>
                    <button id="aicw_save_draft_btn" class="button button-primary"><span>💾</span> <?php _e('Save as Draft', 'ai-content-writer'); ?></button>
                    <button id="aicw_export_txt_btn" class="button"><span>🗎</span> .txt</button>
                    <button id="aicw_export_docx_btn" class="button"><span>🗏</span> .docx</button>
                </div>
            </div>
            <div id="aicw_image_preview_wrap" class="aicw-image-preview-wrap" style="display:none; margin-bottom: 20px; border: 1px solid #e0e6ed; padding: 10px; border-radius: 8px;">
                <h4><?php _e('Generated Featured Image', 'ai-content-writer'); ?></h4>
                <img id="aicw_image_preview" src="" style="max-width: 100%; height: auto; border-radius: 6px; display: block;" alt="<?php esc_attr_e('AI Generated Image', 'ai-content-writer'); ?>">
                <p id="aicw_image_status" style="margin-top: 10px;"></p>
            </div>
            <textarea id="aicw_result" rows="15" style="width: 100%;" readonly></textarea>
            <div class="aicw-copy-message" style="display:none;"><span>✅</span> <?php _e('Copied!', 'ai-content-writer'); ?></div>
        </div>

        <div class="aicw-error-message" style="display:none;"></div>
    </div>
</div>
