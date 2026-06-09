<?php
/**
 * Post Generator Class
 */

defined('ABSPATH') || exit;

class WebPenter_ABA_Generator
{
  public static function get_posts_count()
  {
    return (int) get_option('webpenter_aba_posts_count', 0);
  }

  public static function increment_posts_count()
  {
    $count = self::get_posts_count();
    update_option('webpenter_aba_posts_count', $count + 1);
  }

  public static function generate_batch()
  {
    $settings = WebPenter_ABA_Settings::get_settings();
    $batch_size = (int) $settings['posts_per_batch'];
    if ($batch_size < 1) $batch_size = 1;

    for ($i = 0; $i < $batch_size; $i++) {
      $result = self::generate_single_post();
      if (is_wp_error($result)) {
        WebPenter_ABA_Settings::add_error('Generation Failed: ' . $result->get_error_message());
      }
    }
    
    // Update last run time
    WebPenter_ABA_Settings::update_setting('last_run', current_time('mysql', true));
  }

  public static function generate_single_post()
  {
    $settings = WebPenter_ABA_Settings::get_settings();

    // Check API Keys
    if (empty($settings['gemini_api_key'])) {
      return new WP_Error('missing_api', 'Gemini API Key is missing.');
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
    $prompt .= "On the line below the tags, provide a few simple English keywords (e.g. 'laptop developer coding' or 'office programming code') that would be perfect to search for a high-quality featured stock photo for this specific article on Pixabay, formatted exactly like this: [IMAGE_KEYWORDS: keywords here]";

    // 1. Call Gemini
    $gemini_content = self::call_gemini_api($prompt, $settings['gemini_api_key']);
    if (is_wp_error($gemini_content)) return $gemini_content;

    // Parse Title and Content
    $title = '';
    $content = $gemini_content;
    
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $gemini_content, $matches)) {
        $title = wp_strip_all_tags($matches[1]);
        $content = preg_replace('/<h1[^>]*>.*?<\/h1>/is', '', $gemini_content, 1);
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
    $image_keywords = $topic;
    if (preg_match('/\[IMAGE_KEYWORDS:\s*(.*?)\]/is', $content, $img_matches)) {
        $image_keywords = trim($img_matches[1]);
        $content = preg_replace('/\[IMAGE_KEYWORDS:\s*.*?\]/is', '', $content);
    }

    // Append Affiliate Code
    if (!empty($settings['affiliate_code'])) {
        $content .= "\n\n<div class='aba-affiliate-box'>" . $settings['affiliate_code'] . "</div>";
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

    // 3. Fetch and attach Featured Image via Pixabay
    if (!empty($settings['pixabay_api_key'])) {
        $image_url = self::call_pixabay_api($image_keywords, $settings['pixabay_api_key']);
        if ($image_url && !is_wp_error($image_url)) {
            $attach_id = self::upload_image_to_media_library($image_url, $post_id, $image_keywords);
            if (!is_wp_error($attach_id)) {
                set_post_thumbnail($post_id, $attach_id);
            } else {
                WebPenter_ABA_Settings::add_error('Image upload failed: ' . $attach_id->get_error_message());
            }
        } else if (is_wp_error($image_url)) {
            WebPenter_ABA_Settings::add_error('Pixabay API Error: ' . $image_url->get_error_message());
        }
    }

    // Determine category based on topic logic if needed, or leave to default.
    // We can check if a category matching the topic exists, if so attach it.
    if ($settings['post_type'] === 'post' || taxonomy_exists('category')) {
        $cat_id = get_cat_ID($topic);
        if ($cat_id == 0) {
            $cat_id = wp_insert_category(array('cat_name' => $topic));
        }
        if (!is_wp_error($cat_id) && $cat_id > 0) {
            wp_set_post_categories($post_id, array($cat_id));
        }
    }

    return $post_id;
  }

  private static function call_gemini_api($prompt, $api_key)
  {
    $models = ['gemini-flash-latest', 'gemini-2.5-flash', 'gemini-pro-latest', 'gemini-3.5-flash'];
    $last_error = null;

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
          return $response; // Network error, return immediately
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
        $last_error = new WP_Error('gemini_error', "HTTP $status_code ($model): $err");

        // If it's 503 (High Demand) or 429 (Rate Limit), try the next model
        if ($status_code === 503 || $status_code === 429) {
            sleep(1); // Small delay before trying next model
            continue;
        }

        // If it's any other error (like 400 Bad Request or 403 Forbidden), don't retry
        break;
    }

    return $last_error ?: new WP_Error('gemini_parsing', 'Could not parse Gemini response.');
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
