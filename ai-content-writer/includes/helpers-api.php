<?php
/**
 * helpers-api.php
 * Safe Gemini API calls & AI image generation helpers
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Decrypt stored key
 */
function aicw_get_decrypted_key($option) {
    $enc = get_option($option, '');
    return $enc ? base64_decode($enc) : '';
}

/**
 * ✅ Validate Gemini API key
 */
function aicw_validate_gemini_key($key) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . rawurlencode($key);

    $body = wp_json_encode([
        'contents' => [
            ['parts' => [['text' => 'OK']]]
        ]
    ]);

    $resp = wp_remote_post($url, [
        'headers'    => [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ],
        'body'       => $body,
        'timeout'    => 60,
        'sslverify'  => false,
    ]);

    if (is_wp_error($resp)) {
        return ['success' => false, 'message' => __('Connection error: ', 'ai-content-writer') . $resp->get_error_message()];
    }

    $code = wp_remote_retrieve_response_code($resp);
    $raw  = wp_remote_retrieve_body($resp);
    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        @file_put_contents(AICW_PLUGIN_DIR . 'includes/debug-gemini-validate.log', $raw);
        return ['success' => false, 'message' => __('Invalid response from Gemini (not JSON).', 'ai-content-writer')];
    }

    if ($code === 200) {
        return ['success' => true, 'message' => __('✅ Gemini API key is valid!', 'ai-content-writer')];
    }

    $msg = $data['error']['message'] ?? __('Invalid key or request', 'ai-content-writer');
    return ['success' => false, 'message' => '❌ ' . $msg];
}

/**
 * ✅ Generate content via Gemini
 */
function aicw_call_gemini($key, $prompt) {
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . rawurlencode($key);

    $body = wp_json_encode([
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ]
    ]);

    $resp = wp_remote_post($api_url, [
        'headers'    => [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ],
        'body'       => $body,
        'timeout'    => 60,
        'sslverify'  => false,
    ]);

    if (is_wp_error($resp)) {
        return new WP_Error('api_error', 'Connection failed: ' . $resp->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($resp);
    $raw  = wp_remote_retrieve_body($resp);
    @file_put_contents(AICW_PLUGIN_DIR . 'includes/debug-gemini-response.log', $raw);

    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('api_error', 'Invalid JSON returned from Gemini.');
    }

    if ($code !== 200) {
        $msg = $data['error']['message'] ?? 'Gemini API error (HTTP ' . $code . ')';
        return new WP_Error('api_error', $msg);
    }

    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return trim($data['candidates'][0]['content']['parts'][0]['text']);
    }

    return new WP_Error('api_error', 'No content returned from Gemini.');
}
?>
