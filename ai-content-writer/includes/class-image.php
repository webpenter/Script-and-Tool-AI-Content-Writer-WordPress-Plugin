<?php
/**
 * class-image.php
 * Final version: Imagen → Unsplash → Local Placeholder fallback.
 */

if (!defined('ABSPATH')) exit;

/**
 * LOCAL PLACEHOLDER FALLBACK
 * Works even without internet access.
 */
function aicw_get_placeholder_id($prompt, $status = 'placeholder_fallback', $message = 'Image generation failed: Using local placeholder as final fallback.') {
    error_log("AICW DEBUG: Using LOCAL Placeholder for prompt: {$prompt}");

    // Make sure you have this image in your plugin folder under /assets/
    $local_placeholder_path = plugin_dir_path(__FILE__) . 'assets/placeholder.jpg';
   print_r($local_placeholder_path);
    $local_placeholder_url  = plugin_dir_url(__FILE__) . 'assets/placeholder.jpg';

    if (!file_exists($local_placeholder_path)) {
        error_log("AICW ERROR: Local placeholder not found at {$local_placeholder_path}");
        return [
            'image_id' => 0,
            'image_status' => 'critical_error',
            'image_message' => '❌ Local placeholder image missing in /assets/.'
        ];
    }

    // Sideload the local placeholder image into Media Library
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $file_array = [
        'name'     => 'placeholder-' . sanitize_title($prompt) . '.jpg',
        'tmp_name' => $local_placeholder_path,
    ];

    $media_id = media_handle_sideload($file_array, 0, 'Local Placeholder for: ' . $prompt);

    if (is_wp_error($media_id)) {
        error_log("AICW DEBUG: Local Placeholder FAILED to sideload. Error: {$media_id->get_error_message()}");
        return [
            'image_id' => 0,
            'image_status' => 'critical_error',
            'image_message' => '❌ Failed to use local placeholder: ' . $media_id->get_error_message()
        ];
    }

    error_log("AICW DEBUG: Local Placeholder SUCCESS, ID: {$media_id}");

    return [
        'image_id' => (int)$media_id,
        'image_status' => $status,
        'image_message' => $message
    ];
}


/**
 * UNSPLASH FALLBACK
 */
function aicw_generate_unsplash_image($prompt) {
    error_log("AICW DEBUG: Attempting Unsplash fallback for prompt: {$prompt}");

    $unsplash_api_key = 'Qo9rVlWp_6e5g8x4p7z3o2q1b0aY4j0d9M8I1x3k'; // Replace with your key from https://unsplash.com/developers

    $search_query = urlencode($prompt);
    $url = "https://api.unsplash.com/search/photos?query={$search_query}&per_page=1&orientation=landscape&client_id={$unsplash_api_key}";

    $resp = wp_remote_get($url, ['timeout' => 20]);

    if (is_wp_error($resp)) {
        error_log("AICW DEBUG: Unsplash API FAILED. Error: {$resp->get_error_message()}");
        return aicw_get_placeholder_id($prompt);
    }

    $code = wp_remote_retrieve_response_code($resp);
    $data = json_decode(wp_remote_retrieve_body($resp), true);

    if ($code !== 200 || empty($data['results'][0]['urls']['regular'])) {
        error_log("AICW DEBUG: Unsplash Search FAILED (Code: {$code}). No image found.");
        return aicw_get_placeholder_id($prompt);
    }

    $image_url = $data['results'][0]['urls']['regular'];
    $desc = 'Unsplash Image for: ' . $prompt;

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $media_id = media_sideload_image($image_url, 0, $desc, 'id');

    if (is_wp_error($media_id)) {
        error_log("AICW DEBUG: Unsplash Sideload FAILED. Error: {$media_id->get_error_message()}");
        return aicw_get_placeholder_id($prompt);
    }

    error_log("AICW DEBUG: Unsplash SUCCESS, ID: {$media_id}");

    return [
        'image_id'      => (int)$media_id,
        'image_status'  => 'unsplash_success',
        'image_message' => '✅ Featured Image set using Unsplash Fallback.'
    ];
}


/**
 * IMAGEN API (PRIMARY)
 */
function aicw_generate_image_url_openai($prompt) {
    error_log("AICW DEBUG: STARTING image generation for: {$prompt}");

    $key = aicw_get_decrypted_key('aicw_gemini_api_key');

    if (empty($key)) {
        error_log("AICW DEBUG: No API Key found, switching to Unsplash.");
        return aicw_generate_unsplash_image($prompt);
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-002:predict?key=" . rawurlencode($key);

    $body = wp_json_encode([
        'instances' => [
            'prompt' => sanitize_text_field($prompt) . ", high quality, 4K, realistic photography, featured image style"
        ],
        'parameters' => [
            'sampleCount' => 1,
            'aspectRatio' => '4:3'
        ]
    ]);

    $resp = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ],
        'body'      => $body,
        'timeout'   => 40,
        'sslverify' => false,
    ]);

    if (is_wp_error($resp)) {
        error_log("AICW DEBUG: Imagen API FAILED. Error: {$resp->get_error_message()}");
        return aicw_generate_unsplash_image($prompt);
    }

    $code = wp_remote_retrieve_response_code($resp);
    $raw  = wp_remote_retrieve_body($resp);
    $data = json_decode($raw, true);

    if ($code !== 200 || empty($data['predictions'][0]['bytesBase64Encoded'])) {
        $msg = $data['error']['message'] ?? 'Imagen API error';
        error_log("AICW DEBUG: Imagen API FAILED (HTTP {$code}). Message: {$msg}");
        return aicw_generate_unsplash_image($prompt);
    }

    $base64 = $data['predictions'][0]['bytesBase64Encoded'];
    $image_data = base64_decode($base64);
    $upload_dir = wp_upload_dir();
    $filename   = 'ai-image-' . sanitize_title($prompt) . '-' . time() . '.png';
    $filepath   = $upload_dir['path'] . '/' . $filename;

    if (file_put_contents($filepath, $image_data) === false) {
        error_log("AICW DEBUG: Disk write failed, using Unsplash fallback.");
        return aicw_generate_unsplash_image($prompt);
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = [
        'guid'           => $upload_dir['url'] . '/' . $filename,
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_title($prompt),
        'post_content'   => '',
        'post_status'    => 'inherit'
    ];

    $attach_id = wp_insert_attachment($attachment, $filepath);

    if (is_wp_error($attach_id)) {
        @unlink($filepath);
        error_log("AICW DEBUG: wp_insert_attachment FAILED. Error: {$attach_id->get_error_message()}");
        return aicw_generate_unsplash_image($prompt);
    }

    $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
    wp_update_attachment_metadata($attach_id, $attach_data);

    error_log("AICW DEBUG: Imagen SUCCESS, Attachment ID: {$attach_id}");

    return [
        'image_id'      => $attach_id,
        'image_status'  => 'success',
        'image_message' => '✅ Featured Image generated by Imagen.'
    ];
}
