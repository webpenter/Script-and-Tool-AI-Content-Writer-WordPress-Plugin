<?php
if (!defined('ABSPATH')) {
    exit;
}

class AICW_Ajax_Handlers {
    public static function init() {
        add_action('wp_ajax_aicw_generate_content',        [__CLASS__, 'generate']);
        add_action('wp_ajax_nopriv_aicw_generate_content', [__CLASS__, 'generate']);
        add_action('wp_ajax_aicw_validate_api_key',        [__CLASS__, 'validate']);
        add_action('wp_ajax_aicw_generate_image',          [__CLASS__, 'generate_image']);
        add_action('wp_ajax_aicw_save_draft',              [__CLASS__, 'save_draft']);
    }
public static function generate() {
    check_ajax_referer('aicw_generate_nonce', 'nonce');

    while (ob_get_level()) {
        ob_end_clean();
    }

    $topic    = sanitize_text_field($_POST['topic'] ?? '');
    $existing = sanitize_textarea_field($_POST['existing'] ?? '');
    $type     = sanitize_text_field($_POST['content_type'] ?? 'blog_post');
    $tone     = sanitize_text_field($_POST['tone'] ?? 'friendly');
    $lang     = sanitize_text_field($_POST['language'] ?? 'en');
    $keywords = sanitize_text_field($_POST['keywords'] ?? '');
    $with_img = !empty($_POST['with_image']);
    $post_id  = !empty($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    $prompt_input = ($type === 'rewrite' && $existing) ? $existing : $topic;

    if (empty($prompt_input)) {
        wp_send_json_error(['message' => __('Please enter a topic or text to rewrite.', 'ai-content-writer')]);
    }

    $key = aicw_get_decrypted_key('aicw_gemini_api_key');
    if (empty($key)) {
        wp_send_json_error(['message' => __('API key missing.', 'ai-content-writer')]);
    }

    $full_prompt = aicw_build_prompt($prompt_input, $type, $tone, $lang, $keywords, $existing);
    error_log("AICW DEBUG: Generating content for prompt: " . substr($full_prompt, 0, 200) . "...");
    
    $content = aicw_call_gemini($key, $full_prompt);

    if (is_wp_error($content)) {
        wp_send_json_error(['message' => $content->get_error_message()]);
    }

    global $wpdb;
    $history_table = $wpdb->prefix . 'aicw_history';

    $wpdb->insert($history_table, [
        'user_id'     => get_current_user_id(),
        'prompt'      => $prompt_input,
        'content'     => $content,
        'type'        => $type,
        'tone'        => $tone,
        'language'    => $lang,
        'keywords'    => $keywords,
        'created_at'  => current_time('mysql'),
    ]);

    $history_id = $wpdb->insert_id;

    $response = [
        'content'    => $content,
        'history_id' => $history_id,
    ];

    /** IMAGE GENERATION */
    if ($with_img && function_exists('aicw_generate_image_for_prompt')) {
        $image_prompt = !empty($topic) ? $topic : $existing;

        if (!empty($image_prompt)) {
            $image_result = aicw_generate_image_for_prompt($image_prompt);

            if (is_array($image_result)) {
                $media_id = intval($image_result['image_id'] ?? 0);

                $response['image_id']      = $media_id;
                $response['image_status']  = sanitize_text_field($image_result['image_status'] ?? 'error');
                $response['image_message'] = sanitize_text_field($image_result['image_message'] ?? '');

                if ($media_id > 0) {
                    // Get full image data for frontend
                    $image_url = wp_get_attachment_url($media_id);
                    if ($image_url) {
                        $response['image_url'] = esc_url($image_url);
                        
                        // Get image metadata for frontend
                        $attachment = get_post($media_id);
                        if ($attachment) {
                            $response['image_data'] = [
                                'id' => $media_id,
                                'url' => $image_url,
                                'alt' => get_post_meta($media_id, '_wp_attachment_image_alt', true),
                                'title' => $attachment->post_title,
                                'caption' => $attachment->post_excerpt,
                                'description' => $attachment->post_content,
                            ];
                        }
                    }

                    // Set as featured image if we have a valid post ID
                    if ($post_id > 0) {
                        $post = get_post($post_id);
                        if ($post) {
                            $old_thumbnail_id = get_post_thumbnail_id($post_id);
                            $set_featured = set_post_thumbnail($post_id, $media_id);
                            $response['featured_set'] = $set_featured ? 'yes' : 'no';
                            
                            error_log("AICW DEBUG: Setting featured image. Old: {$old_thumbnail_id}, New: {$media_id}, Success: " . ($set_featured ? 'Yes' : 'No'));
                            
                            if ($set_featured) {
                                $response['image_message'] .= ' Featured image set!';
                                
                                // Return data that WordPress Gutenberg expects
                                $response['featured_image_data'] = [
                                    'id' => $media_id,
                                    'url' => $image_url,
                                    'alt' => get_post_meta($media_id, '_wp_attachment_image_alt', true),
                                ];
                            } else {
                                $response['image_message'] .= ' Could not set featured image.';
                            }
                        } else {
                            $response['featured_set'] = 'post_not_found';
                            error_log("AICW DEBUG: Post {$post_id} not found for featured image");
                        }
                    }
                }
            } else {
                $response['image_status']  = 'critical_error';
                $response['image_message'] = 'Image generation returned unexpected data.';
                $response['image_id']      = 0;
            }
        } else {
            $response['image_status']  = 'error';
            $response['image_message'] = 'No prompt provided for image generation.';
            $response['image_id']      = 0;
        }
    }

    wp_send_json_success($response);
}

    private static function get_language_name($code) {
        $map = [
            'en' => 'English', 'es' => 'Spanish', 'fr' => 'French',
            'de' => 'German', 'hi' => 'Hindi', 'ur' => 'Urdu',
            'it' => 'Italian', 'ar' => 'Arabic', 'zh' => 'Chinese',
            'pt' => 'Portuguese', 'ru' => 'Russian', 'ja' => 'Japanese',
            'ko' => 'Korean',
        ];
        return $map[$code] ?? ucfirst($code);
    }
public static function validate() {

    check_ajax_referer('aicw_validate_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => __('Permission denied.', 'ai-content-writer')
        ]);
    }

    $key = sanitize_text_field($_POST['api_key'] ?? '');

    if (empty($key)) {
        wp_send_json_error([
            'message' => __('Please enter an API key.', 'ai-content-writer')
        ]);
    }

    $result = aicw_validate_gemini_key($key);

    if (!empty($result['success'])) {
        wp_send_json_success([
            'message' => $result['message']
        ]);
    }

    wp_send_json_error([
        'message' => $result['message']
    ]);
}


    public static function generate_image() {
        check_ajax_referer('aicw_generate_nonce', 'nonce');

        while (ob_get_level()) {
            ob_end_clean();
        }

        $topic = sanitize_text_field($_POST['topic'] ?? '');

        if (empty($topic)) {
            wp_send_json_error(['message' => __('Please enter a topic for image generation.', 'ai-content-writer')]);
        }

        if (!function_exists('aicw_generate_image_for_prompt')) {
            wp_send_json_error(['message' => __('Image generation function missing.', 'ai-content-writer')]);
        }

        $image_result = aicw_generate_image_for_prompt($topic);

        if (is_array($image_result) && in_array($image_result['image_status'], ['success', 'pixabay_success'])) {
            wp_send_json_success([
                'image_id'  => intval($image_result['image_id']),
                'message'   => sanitize_text_field($image_result['image_message']),
                'image_url' => !empty($image_result['image_url']) ? esc_url($image_result['image_url']) : '',
            ]);
        } else {
            $msg = is_array($image_result)
                ? sanitize_text_field($image_result['image_message'] ?? 'Image generation failed.')
                : 'Image generation error.';
            wp_send_json_error(['message' => $msg]);
        }
    }

 public static function save_draft() {
    check_ajax_referer('aicw_generate_nonce', 'nonce');

    // Clear all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Sanitize inputs
    $title    = sanitize_text_field($_POST['title'] ?? 'AI Generated Content');
    $content  = wp_kses_post($_POST['content'] ?? '');
    $image_id = !empty($_POST['image_id']) ? intval($_POST['image_id']) : 0;

    error_log("AICW DEBUG: Saving draft - Title: '{$title}', Image ID: {$image_id}, Content length: " . strlen($content));

    // Validate content
    if (empty($content)) {
        error_log("AICW ERROR: No content provided for draft");
        wp_send_json_error(['message' => __('No content to save.', 'ai-content-writer')]);
        wp_die();
    }

    // Prepare post data
    $post_data = [
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'draft',
        'post_author'  => get_current_user_id(),
        'post_type'    => 'post',
    ];

    // Insert post (DO NOT use true as second parameter - it returns WP_Error directly)
    $post_id = wp_insert_post($post_data);

    // Check for errors
    if (is_wp_error($post_id)) {
        $error_msg = $post_id->get_error_message();
        error_log("AICW ERROR: Failed to create post - " . $error_msg);
        wp_send_json_error(['message' => __('Failed to create post: ', 'ai-content-writer') . $error_msg]);
        wp_die();
    }

    if ($post_id === 0) {
        error_log("AICW ERROR: wp_insert_post returned 0");
        wp_send_json_error(['message' => __('Failed to create post. Please try again.', 'ai-content-writer')]);
        wp_die();
    }

    error_log("AICW DEBUG: Post created successfully with ID: {$post_id}");

    // Set featured image if provided
    $featured_set = false;
    if ($image_id > 0) {
        // Verify the image exists in media library
        $attachment = get_post($image_id);
        if ($attachment && $attachment->post_type === 'attachment') {
            $set_result = set_post_thumbnail($post_id, $image_id);
            $featured_set = (bool)$set_result;
            
            error_log("AICW DEBUG: Set featured image {$image_id} for post {$post_id}. Success: " . ($featured_set ? 'Yes' : 'No'));
            
            if (!$featured_set) {
                error_log("AICW DEBUG: set_post_thumbnail returned false or 0");
            }
        } else {
            error_log("AICW DEBUG: Image ID {$image_id} is not a valid attachment");
        }
    }

    // Get edit URL
    $edit_url = get_edit_post_link($post_id);
    if (empty($edit_url)) {
        $edit_url = admin_url('post.php?post=' . $post_id . '&action=edit');
    }

    // Prepare response
    $response_data = [
        'message'      => __('Draft saved successfully.', 'ai-content-writer'),
        'edit_url'     => esc_url($edit_url),
        'post_id'      => $post_id,
        'featured_set' => $featured_set ? 'yes' : 'no',
    ];

    // Add additional info if needed
    if ($image_id > 0 && !$featured_set) {
        $response_data['image_note'] = __('Note: Image exists in media library but was not set as featured.', 'ai-content-writer');
    }

    error_log("AICW DEBUG: Sending success response for post ID: {$post_id}");
    
    wp_send_json_success($response_data);
    wp_die();
}
} 