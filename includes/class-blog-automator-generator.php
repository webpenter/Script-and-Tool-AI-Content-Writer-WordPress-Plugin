<?php
/**
 * Post Generator Class
 */

defined('ABSPATH') || exit;

class WebPenter_ABM_Generator
{
  public static function get_posts_count()
  {
    return (int) get_option('webpenter_abm_posts_count', 0);
  }

  public static function increment_posts_count()
  {
    $count = self::get_posts_count();
    update_option('webpenter_abm_posts_count', $count + 1);
  }

  public static function generate_batch()
  {
    $settings = WebPenter_ABM_Settings::get_settings();
    $batch_size = (int) $settings['posts_per_batch'];
    if ($batch_size < 1) $batch_size = 1;

    for ($i = 0; $i < $batch_size; $i++) {
      $result = self::generate_single_post();
      if (is_wp_error($result)) {
        WebPenter_ABM_Settings::add_error('Generation Failed: ' . $result->get_error_message());
      }
    }
    
    // Update last run time
    WebPenter_ABM_Settings::update_setting('last_run', current_time('mysql', true));
  }

  public static function generate_single_post()
  {
    $settings = WebPenter_ABM_Settings::get_settings();

    // Check API Keys based on provider
    $provider = isset($settings['ai_provider']) ? $settings['ai_provider'] : 'gemini';
    if ($provider === 'groq') {
      if (empty($settings['groq_api_key'])) {
        return new WP_Error('missing_api', 'Groq API Key is missing. Get one free at https://console.groq.com');
      }
    } else {
      if (empty($settings['gemini_api_key'])) {
        return new WP_Error('missing_api', 'Gemini API Key is missing.');
      }
    }

    // Pick a topic
    $topics_raw = $settings['topics'];
    if (empty(trim($topics_raw))) {
      return new WP_Error('missing_topics', 'No topics provided in Content Strategy settings.');
    }
    $topics_array = array_filter(array_map('trim', explode("\n", $topics_raw)));
    if (empty($topics_array)) {
      return new WP_Error('invalid_topics', 'No valid topics found.');
    }
    $topic = $topics_array[array_rand($topics_array)];

    // Prepare Prompt
    $word_count = (int) $settings['word_count'];
    $prompt = "Write a comprehensive, engaging, and SEO-optimized blog post about: '{$topic}'.\n";
    $prompt .= "The article must be approximately {$word_count} words long.\n";
    $prompt .= "Use proper HTML formatting (<h1> for main title, <h2> for sections, <h3> for subsections, <p> for paragraphs, <ul>/<li> for lists).\n";
    $prompt .= "DO NOT wrap the output in ```html blocks, just return raw HTML.\n";
    $prompt .= "IMPORTANT: The very first line MUST be the title wrapped in <h1> tags.\n";
    $prompt .= "At the very end of the article, add a line containing 3 to 5 comma-separated tags for this post, formatted exactly like this: [TAGS: tag1, tag2, tag3]\n";
    $prompt .= "On the very last line, provide exactly 1 or 2 simple, highly-searchable broad English words (e.g. 'technology', 'finance', 'politics', 'business') that represent the core subject of the title for a stock photo search. Format EXACTLY like this: [IMAGE_KEYWORDS: keyword1 keyword2]";

    // 1. Call AI API based on provider
    if ($provider === 'groq') {
      $ai_content = self::call_groq_api($prompt, $settings['groq_api_key']);
    } else {
      $ai_content = self::call_gemini_api($prompt, $settings['gemini_api_key']);
    }
    if (is_wp_error($ai_content)) return $ai_content;

    // Parse Title and Content
    $title = '';
    $content = $ai_content;
    
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $ai_content, $matches)) {
        $title = wp_strip_all_tags($matches[1]);
        $content = preg_replace('/<h1[^>]*>.*?<\/h1>/is', '', $ai_content, 1);
    } else {
        $title = $topic . ' - ' . date('Y-m-d');
    }

    // Extract tags
    $tags = array();
    if (preg_match('/\[TAGS:\s*(.*?)\]/is', $content, $tag_matches)) {
        $tags_raw = $tag_matches[1];
        $tags = array_filter(array_map('trim', explode(',', $tags_raw)));
        $content = preg_replace('/\[TAGS:\s*.*?\]/is', '', $content);
    }

    // Extract image keywords
    $image_keywords = '';
    if (preg_match('/\[IMAGE_KEYWORDS:\s*(.*?)\]/is', $content, $img_matches)) {
        $image_keywords = trim(str_replace(array('[', ']', '*'), '', $img_matches[1]));
        $content = preg_replace('/\[IMAGE_KEYWORDS:\s*.*?\]/is', '', $content);
    }
    
    // Fallback if AI didn't provide keywords
    if (empty($image_keywords)) {
        // Fallback: extract the first two words of the topic
        $topic_clean = preg_replace('/[^a-zA-Z0-9\s]/', '', $topic);
        $topic_words = array_filter(explode(' ', $topic_clean));
        $image_keywords = implode(' ', array_slice($topic_words, 0, 2));
    }
    // Clean up keywords for better search
    $image_keywords = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $image_keywords));
    if (empty($image_keywords)) $image_keywords = 'nature'; // ultimate fallback

    // Append Affiliate Code
    if (!empty($settings['affiliate_code'])) {
        $content .= "\n\n<div class='webpenter-abm-affiliate-box'>" . $settings['affiliate_code'] . "</div>";
    }

    // 2. Insert Post
    $post_data = array(
        'post_title'    => wp_strip_all_tags($title),
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_type'     => sanitize_text_field($settings['post_type']),
        'post_author'   => 1
    );

    $post_id = wp_insert_post($post_data, true);
    if (is_wp_error($post_id)) return $post_id;

    self::increment_posts_count();

    // Attach tags
    if (!empty($tags)) {
        wp_set_post_tags($post_id, $tags);
    }

    // 3. Fetch and attach Featured Image
    $image_source = isset($settings['image_source']) ? $settings['image_source'] : 'pixabay';
    $image_url = false;

    if ($image_source === 'pixabay' && !empty($settings['pixabay_api_key'])) {
        $image_url = self::call_pixabay_api($image_keywords, $settings['pixabay_api_key']);
    } elseif ($image_source === 'unsplash' && !empty($settings['unsplash_api_key'])) {
        $image_url = self::call_unsplash_api($image_keywords, $settings['unsplash_api_key']);
    } elseif ($image_source === 'huggingface' && !empty($settings['huggingface_api_key'])) {
        $style = isset($settings['ai_image_style']) ? $settings['ai_image_style'] : 'photorealistic';
        // For AI, we can use the main topic or the exact title for better context
        $ai_prompt = wp_strip_all_tags($title) . ', ' . $image_keywords; 
        $image_url = self::call_huggingface_api($ai_prompt, $style, $settings['huggingface_api_key']);
    }

    if ($image_url && !is_wp_error($image_url)) {
        $attach_id = self::upload_image_to_media_library($image_url, $post_id, $image_keywords);
        if (!is_wp_error($attach_id)) {
            set_post_thumbnail($post_id, $attach_id);
        } else {
            WebPenter_ABM_Settings::add_error('Image upload failed: ' . $attach_id->get_error_message());
        }
    } else if (is_wp_error($image_url)) {
        WebPenter_ABM_Settings::add_error(ucfirst($image_source) . ' Image Error: ' . $image_url->get_error_message());
    }

    // Determine category based on topic logic if needed, or leave to default.
    // We can check if a category matching the topic exists, if so attach it.
    if ($settings['post_type'] === 'post' || taxonomy_exists('category')) {
        $cat_name = sanitize_text_field($topic);
        $term = term_exists($cat_name, 'category');
        
        if ($term === 0 || $term === null) {
            $term = wp_insert_term($cat_name, 'category');
        }
        
        if (!is_wp_error($term) && isset($term['term_id'])) {
            wp_set_post_categories($post_id, array((int)$term['term_id']));
        }
    }

    return $post_id;
  }

  public static function test_api_connection($provider, $api_key)
  {
    $prompt = "Test connection. Reply with only the word 'OK'.";
    if ($provider === 'groq') {
      return self::call_groq_api($prompt, $api_key);
    } elseif ($provider === 'pixabay') {
      return self::call_pixabay_api('nature', $api_key);
    } elseif ($provider === 'unsplash') {
      return self::call_unsplash_api('nature', $api_key);
    } elseif ($provider === 'huggingface') {
      return self::call_huggingface_api('a beautiful nature landscape', 'photorealistic', $api_key, true);
    } else {
      return self::call_gemini_api($prompt, $api_key);
    }
  }

  private static function call_gemini_api($prompt, $api_key)
  {
    $models = array('gemini-2.0-flash');

    foreach ($models as $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;
        
        $body = array(
          'contents' => array(
            array(
              'parts' => array(
                array('text' => $prompt)
              )
            )
          )
        );

        $response = wp_remote_post($url, array(
          'headers' => array('Content-Type' => 'application/json'),
          'body'    => wp_json_encode($body),
          'timeout' => 60
        ));

        if (is_wp_error($response)) {
          return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body_json = wp_remote_retrieve_body($response);
        $data = json_decode($body_json, true);

        if ($status_code === 200 && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
          $text = $data['candidates'][0]['content']['parts'][0]['text'];
          $text = preg_replace('/```html\s*/', '', $text);
          $text = preg_replace('/```\s*$/', '', $text);
          return $text;
        }

        $err = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown Gemini error';
        return new WP_Error('gemini_error', "HTTP $status_code ($model): $err");
    }

    return new WP_Error('gemini_parsing', 'Could not parse Gemini response.');
  }

  private static function call_groq_api($prompt, $api_key)
  {
    $url = 'https://api.groq.com/openai/v1/chat/completions';

    $body = array(
      'model' => 'llama-3.1-8b-instant',
      'messages' => array(
        array(
          'role' => 'user',
          'content' => $prompt
        )
      ),
      'temperature' => 0.7,
      'max_tokens' => 3000
    );

    $response = wp_remote_post($url, array(
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $api_key
      ),
      'body'    => wp_json_encode($body),
      'timeout' => 60
    ));

    if (is_wp_error($response)) {
      return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body_json = wp_remote_retrieve_body($response);
    $data = json_decode($body_json, true);

    if ($status_code === 200 && isset($data['choices'][0]['message']['content'])) {
      $text = $data['choices'][0]['message']['content'];
      $text = preg_replace('/```html\s*/', '', $text);
      $text = preg_replace('/```\s*$/', '', $text);
      return $text;
    }

    $err = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown Groq error';
    return new WP_Error('groq_error', "HTTP $status_code: $err");
  }

  private static function call_pixabay_api($query, $api_key)
  {
    $query = urlencode(substr($query, 0, 100)); // limit query length
    $url = "https://pixabay.com/api/?key={$api_key}&q={$query}&image_type=photo&orientation=horizontal&per_page=20";
    
    $response = wp_remote_get($url, array('timeout' => 15));
    if (is_wp_error($response)) return $response;

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) return new WP_Error('pixabay_error', "HTTP $status_code");

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['hits']) && count($body['hits']) > 0) {
        // Pick a random image from the top results
        $hit = $body['hits'][array_rand($body['hits'])];
        return $hit['largeImageURL'];
    }

    return new WP_Error('pixabay_no_results', 'No images found for query: ' . $query);
  }

  private static function call_unsplash_api($query, $api_key)
  {
    $query = urlencode(substr($query, 0, 100));
    $url = "https://api.unsplash.com/search/photos?query={$query}&orientation=landscape&per_page=10";
    
    $response = wp_remote_get($url, array(
      'timeout' => 15,
      'headers' => array(
        'Authorization' => 'Client-ID ' . $api_key
      )
    ));
    
    if (is_wp_error($response)) return $response;

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) return new WP_Error('unsplash_error', "HTTP $status_code");

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['results']) && count($body['results']) > 0) {
        $hit = $body['results'][array_rand($body['results'])];
        return $hit['urls']['regular'];
    }

    return new WP_Error('unsplash_no_results', 'No images found for query: ' . $query);
  }

  private static function call_huggingface_api($query, $style, $api_key, $is_test = false)
  {
    $query = substr($query, 0, 300);
    
    // Add style modifiers to prompt
    $prompt_suffix = '';
    if ($style === 'photorealistic') {
      $prompt_suffix = ', highly detailed, photorealistic, 8k, professional photography';
    } elseif ($style === 'digital-art') {
      $prompt_suffix = ', trending on artstation, digital art, vibrant colors';
    } elseif ($style === 'cinematic') {
      $prompt_suffix = ', cinematic lighting, dramatic, epic scale, 35mm lens';
    } elseif ($style === 'anime') {
      $prompt_suffix = ', studio ghibli style, anime art, cel shaded';
    }

    $final_query = $query . $prompt_suffix;
    
    // Using Stable Diffusion v1.5 or XL
    $url = "https://api-inference.huggingface.co/models/stabilityai/stable-diffusion-xl-base-1.0";
    
    $body = wp_json_encode(array("inputs" => $final_query));

    $response = wp_remote_post($url, array(
      'timeout' => 60, // Image generation takes time
      'headers' => array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type'  => 'application/json'
      ),
      'body' => $body
    ));
    
    if (is_wp_error($response)) return $response;
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body_content = wp_remote_retrieve_body($response);

    if ($status_code !== 200) {
        $error_data = json_decode($body_content, true);
        $err_msg = isset($error_data['error']) ? $error_data['error'] : "HTTP $status_code";
        
        // HF sometimes returns 503 "Model is loading"
        if ($status_code === 503 && isset($error_data['estimated_time'])) {
            return new WP_Error('huggingface_loading', "Model is loading. Please try again in {$error_data['estimated_time']} seconds.");
        }
        
        return new WP_Error('huggingface_error', $err_msg);
    }

    if ($is_test) {
        return true; // We don't need to save the image for a test
    }

    // Hugging face returns raw binary image data (JPEG)
    // We need to save this to a temporary file locally and return the file path or URL
    $upload_dir = wp_upload_dir();
    $temp_filename = 'hf-img-' . time() . '-' . wp_rand(1000, 9999) . '.jpg';
    $temp_file_path = $upload_dir['path'] . '/' . $temp_filename;
    
    $put_res = file_put_contents($temp_file_path, $body_content);
    if ($put_res === false) {
        return new WP_Error('file_write_error', 'Failed to save generated image to disk.');
    }
    
    // Return the local URL so upload_image_to_media_library can sideload it 
    // (upload_image_to_media_library uses download_url which works with local URLs too)
    return $upload_dir['url'] . '/' . $temp_filename;
  }

  private static function upload_image_to_media_library($image_url, $post_id, $desc)
  {
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Download file to temp dir
    $tmp = download_url($image_url);
    if (is_wp_error($tmp)) return $tmp;

    $file_array = array(
      'name' => 'auto-gen-' . wp_generate_password(8, false) . '.jpg',
      'tmp_name' => $tmp
    );

    $id = media_handle_sideload($file_array, $post_id, $desc);
    
    // Remove temp file if error
    if (is_wp_error($id)) {
        @unlink($file_array['tmp_name']);
    }

    return $id;
  }
}
