<?php
/**
 * class-image.php
 * Pixabay-only image generation - No Gemini API calls
 */

if (!defined('ABSPATH')) exit;

/**
 * Generate image from Pixabay based on post title
 */
function aicw_generate_image_for_prompt($prompt) {
    error_log("AICW DEBUG: Starting Pixabay image generation for: {$prompt}");
    
    // Clean and prepare the prompt
    $clean_prompt = sanitize_text_field($prompt);
    
    if (empty($clean_prompt)) {
        $clean_prompt = 'beautiful landscape';
    }
    
    // Extract keywords from title (first 3-4 words work best for Pixabay)
    $words = explode(' ', $clean_prompt);
    $keywords = array_slice($words, 0, 4);
    $search_query = implode(' ', $keywords);
    
    error_log("AICW DEBUG: Searching Pixabay for: {$search_query}");
    
    // Pixabay API key (yours)
    $pixabay_key = '53594356-1b8baf8d7188f5b88fea5b566';
    
    if (empty($pixabay_key)) {
        error_log("AICW DEBUG: Pixabay API key is missing");
        return [
            'image_id' => 0,
            'image_status' => 'error',
            'image_message' => '❌ Pixabay API key is not configured.'
        ];
    }
    
    // Build Pixabay API URL
    $encoded_query = urlencode($search_query);
    $api_url = "https://pixabay.com/api/?key={$pixabay_key}&q={$encoded_query}&image_type=photo&orientation=horizontal&per_page=10&safesearch=true";
    
    error_log("AICW DEBUG: Calling Pixabay API: " . str_replace($pixabay_key, 'HIDDEN', $api_url));
    
    // Make API request
    $response = wp_remote_get($api_url, [
        'timeout' => 30,
        'user-agent' => 'WordPress AI Content Writer Plugin'
    ]);
    
    if (is_wp_error($response)) {
        $error_msg = $response->get_error_message();
        error_log("AICW DEBUG: Pixabay API request failed: {$error_msg}");
        return [
            'image_id' => 0,
            'image_status' => 'error',
            'image_message' => '❌ Pixabay API error: ' . $error_msg
        ];
    }
    
    $http_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    error_log("AICW DEBUG: Pixabay HTTP Code: {$http_code}");
    
    if ($http_code !== 200) {
        error_log("AICW DEBUG: Pixabay returned error code: {$http_code}");
        return [
            'image_id' => 0,
            'image_status' => 'error',
            'image_message' => '❌ Pixabay API returned error code: ' . $http_code
        ];
    }
    
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("AICW DEBUG: JSON decode error: " . json_last_error_msg());
        return [
            'image_id' => 0,
            'image_status' => 'error',
            'image_message' => '❌ Failed to parse Pixabay response.'
        ];
    }
    
    if (!isset($data['hits']) || empty($data['hits'])) {
        error_log("AICW DEBUG: No images found for query: {$search_query}");
        
        // Try with simpler query (first 2 words)
        if (count($keywords) > 2) {
            $simpler_query = implode(' ', array_slice($keywords, 0, 2));
            error_log("AICW DEBUG: Trying simpler query: {$simpler_query}");
            
            $encoded_simple = urlencode($simpler_query);
            $api_url_simple = "https://pixabay.com/api/?key={$pixabay_key}&q={$encoded_simple}&image_type=photo&orientation=horizontal&per_page=5&safesearch=true";
            
            $response_simple = wp_remote_get($api_url_simple, ['timeout' => 20]);
            
            if (!is_wp_error($response_simple) && wp_remote_retrieve_response_code($response_simple) === 200) {
                $body_simple = wp_remote_retrieve_body($response_simple);
                $data_simple = json_decode($body_simple, true);
                
                if (!empty($data_simple['hits'])) {
                    $data = $data_simple;
                    error_log("AICW DEBUG: Found images with simpler query");
                }
            }
        }
        
        if (empty($data['hits'])) {
            return [
                'image_id' => 0,
                'image_status' => 'error',
                'image_message' => '❌ No images found for: ' . $search_query
            ];
        }
    }
    
    // Choose the best image (first one with largeImageURL)
    $selected_image = null;
    foreach ($data['hits'] as $image) {
        if (isset($image['largeImageURL']) && !empty($image['largeImageURL'])) {
            $selected_image = $image;
            break;
        }
    }
    
    if (!$selected_image || empty($selected_image['largeImageURL'])) {
        error_log("AICW DEBUG: No suitable image URL found in results");
        return [
            'image_id' => 0,
            'image_status' => 'error',
            'image_message' => '❌ No suitable image found in search results.'
        ];
    }
    
    $image_url = $selected_image['largeImageURL'];
    $image_title = 'Pixabay Image for: ' . $clean_prompt;
    
    error_log("AICW DEBUG: Selected image URL: {$image_url}");
    
    // Download and upload the image to WordPress media library
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    // Use media_sideload_image (simplest method)
    $media_id = media_sideload_image($image_url, 0, $image_title, 'id');
    
    if (is_wp_error($media_id)) {
        error_log("AICW DEBUG: media_sideload_image failed: " . $media_id->get_error_message());
        
        // Fallback: Use download_url method
        $temp_file = download_url($image_url, 30);
        
        if (is_wp_error($temp_file)) {
            error_log("AICW DEBUG: Download failed: " . $temp_file->get_error_message());
            return [
                'image_id' => 0,
                'image_status' => 'error',
                'image_message' => '❌ Failed to download image: ' . $temp_file->get_error_message()
            ];
        }
        
        $file_array = [
            'name' => 'pixabay-' . sanitize_file_name($clean_prompt) . '.jpg',
            'tmp_name' => $temp_file,
            'error' => 0,
            'size' => filesize($temp_file)
        ];
        
        $media_id = media_handle_sideload($file_array, 0, $image_title);
        
        // Clean up temp file
        @unlink($temp_file);
    }
    
    if (is_wp_error($media_id)) {
        $error_msg = $media_id->get_error_message();
        error_log("AICW DEBUG: All upload methods failed: {$error_msg}");
        return [
            'image_id' => 0,
            'image_status' => 'error',
            'image_message' => '❌ Failed to upload to media library: ' . $error_msg
        ];
    }
    
    // Set alt text
    update_post_meta($media_id, '_wp_attachment_image_alt', $clean_prompt . ' - Pixabay image');
    
    // Get the image URL
    $image_attachment_url = wp_get_attachment_url($media_id);
    
    error_log("AICW DEBUG: SUCCESS! Media ID: {$media_id}, URL: {$image_attachment_url}");
    
    return [
        'image_id' => (int)$media_id,
        'image_status' => 'success',
        'image_message' => '✅ Image generated from Pixabay!',
        'image_url' => $image_attachment_url
    ];
}

// Alias for backward compatibility
function aicw_generate_image_url($prompt) {
    return aicw_generate_image_for_prompt($prompt);
}