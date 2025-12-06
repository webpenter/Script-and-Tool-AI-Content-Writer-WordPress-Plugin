<?php
if (!defined('ABSPATH')) {
    exit;
}

class AICW_Ajax_Handlers {

    public static function init() {
        add_action('wp_ajax_aicw_generate_content',        [__CLASS__, 'generate']);
        add_action('wp_ajax_nopriv_aicw_generate_content', [__CLASS__, 'generate']);
        add_action('wp_ajax_aicw_validate_api_key',        [__CLASS__, 'validate']);

        add_action('wp_ajax_aicw_generate_image', [__CLASS__, 'generate_image']); 
        add_action('wp_ajax_aicw_save_draft',     [__CLASS__, 'save_draft']);
    }

public static function generate() {
    check_ajax_referer('aicw_generate_nonce', 'nonce');

    if (ob_get_length()) ob_clean();

    $topic    = sanitize_text_field($_POST['topic'] ?? '');
    $existing = sanitize_textarea_field($_POST['existing'] ?? '');
    $type     = sanitize_text_field($_POST['content_type'] ?? 'blog_post');
    $tone     = sanitize_text_field($_POST['tone'] ?? 'friendly');
    $lang     = sanitize_text_field($_POST['language'] ?? 'en'); // language from AJAX
    $keywords = sanitize_text_field($_POST['keywords'] ?? '');
    $with_img = !empty($_POST['with_image']);

    // 隼 Determine main input
    $prompt_input = ($type === 'rewrite' && $existing) ? $existing : $topic;

    if (!$prompt_input) {
        wp_send_json_error(['message' => __('Please enter a topic or text to rewrite.', 'ai-content-writer')]);
        wp_die();
    }

    $key = aicw_get_decrypted_key('aicw_gemini_api_key');
    if (!$key) {
        wp_send_json_error(['message' => __('API key missing.', 'ai-content-writer')]);
        wp_die();
    }

    // 隼 Build prompt using selected language
    $full_prompt = aicw_build_prompt($prompt_input, $type, $tone, $lang, $keywords, $existing);

    // 隼 DEBUG: Log what prompt is sent
    error_log("AICW AJAX Prompt:\n" . $full_prompt);
    error_log("Selected Language: " . $lang);

    $content = aicw_call_gemini($key, $full_prompt);

    if (is_wp_error($content)) {
        wp_send_json_error(['message' => $content->get_error_message()]);
        wp_die();
    }

    global $wpdb;
    $history_table = $wpdb->prefix . 'aicw_history';
    $wpdb->insert($history_table, [
        'user_id'  => get_current_user_id(),
        'prompt'   => $prompt_input,
        'content'  => $content,
        'type'     => $type,
        'tone'     => $tone,
        'language' => $lang,
        'keywords' => $keywords,
    ], ['%d','%s','%s','%s','%s','%s','%s']);

    $history_id = $wpdb->insert_id;

    $response = [
        'content'    => $content,
        'history_id' => $history_id,
    ];

    // 隼 Image Generation Logic
    if ($with_img && function_exists('aicw_generate_image_url')) {
        $image_prompt = $topic ?: $existing; 
        $image_result = aicw_generate_image_url($image_prompt);
        
        // The image function now returns an array in all cases
        if (is_array($image_result)) {
            $response['image_id']      = $image_result['image_id'];
            $response['image_status']  = $image_result['image_status'];
            $response['image_message'] = $image_result['image_message'];
            // ✅ FIX: Retrieve the image URL for the frontend script to display
            if ($image_result['image_id'] > 0) {
                // Ensure WordPress functions are available (though they usually are in wp_ajax)
                if (function_exists('wp_get_attachment_url')) {
                    $response['image_url'] = wp_get_attachment_url($image_result['image_id']);
                }
            }
        } else {
             // Fallback if image function returns unexpected non-array value
            $response['image_status']  = 'critical_error';
            $response['image_message'] = 'Image generation encountered an unexpected internal error.';
            $response['image_id']      = 0;
        }
    }

    if (ob_get_length()) ob_clean();
    wp_send_json_success($response);
    wp_die();
}


// 隼 Add helper to convert short language codes to readable names
private static function get_language_name($code) {
    $map = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'hi' => 'Hindi',
        'ur' => 'Urdu',
        'it' => 'Italian',
        'ar' => 'Arabic',
        'zh' => 'Chinese'
    ];
    return $map[$code] ?? ucfirst($code);
}
    public static function validate() {
        check_ajax_referer('aicw_validate_nonce', 'nonce');

        if (ob_get_length()) ob_clean();

        $key = sanitize_text_field($_POST['api_key'] ?? '');
        if (!$key) {
            wp_send_json_error(['message' => __('Please enter an API key.', 'ai-content-writer')]);
            wp_die();
        }

        $out = aicw_validate_gemini_key($key);
        // wp_send_json accepts arrays like ['success'=>bool,'message'=>string]
        if (ob_get_length()) ob_clean();
        wp_send_json($out);
        wp_die();
    }

    public static function generate_image() {
        check_ajax_referer('aicw_generate_nonce', 'nonce');

        if (ob_get_length()) ob_clean();

        $topic = sanitize_text_field($_POST['topic'] ?? '');
        if (!$topic) {
            wp_send_json_error(['message' => __('Missing topic for image generation.', 'ai-content-writer')]);
            wp_die();
        }

        if (!function_exists('aicw_generate_image_url')) {
            wp_send_json_error(['message' => __('Image function unavailable.', 'ai-content-writer')]);
            wp_die();
        }

        $image_result = aicw_generate_image_url($topic);
        if (is_array($image_result) && $image_result['image_status'] === 'success') {
            wp_send_json_success(['image_id' => $image_result['image_id'], 'message' => $image_result['image_message']]);
        } else {
            $msg = $image_result['image_message'] ?? 'Image generation failed.';
            wp_send_json_error(['message' => $msg]);
        }
        wp_die();
    }

    public static function save_draft() {
        check_ajax_referer('aicw_generate_nonce', 'nonce');

        if (ob_get_length()) ob_clean();

        $title   = sanitize_text_field($_POST['title'] ?? 'AI Generated Content');
        $content = wp_kses_post($_POST['content'] ?? '');
        if (!$content) {
            wp_send_json_error(['message' => __('No content to save.', 'ai-content-writer')]);
            wp_die();
        }

        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'draft',
            'post_author'  => get_current_user_id(),
        ]);

        if (is_wp_error($post_id) || $post_id === 0) {
            wp_send_json_error(['message' => is_wp_error($post_id) ? $post_id->get_error_message() : __('Failed to create post.', 'ai-content-writer')]);
            wp_die();
        }

        if (ob_get_length()) ob_clean();
        wp_send_json_success(['message' => __('Draft saved successfully.', 'ai-content-writer'), 'edit_url' => get_edit_post_link($post_id)]);
        wp_die();
    }
}
