<?php

/**
 * Post Generator
 *
 * Handles AI API communication and WordPress post creation
 *
 * @package AI_Blog_Automator
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Post Generator Class
 */
class Bluteem_ABA_Generator
{

  /**
   * Check if user can generate more posts (unlimited for all users)
   *
   * @return bool
   */
  public static function can_generate_post()
  {
    // All users have unlimited posts
    return true;
  }

  /**
   * Record a successful post generation (updates all counters and last run time).
   */
  private static function record_post_generation()
  {
    $posts_count = intval(get_option('bluteem_aba_posts_count', 0));
    update_option('bluteem_aba_posts_count', $posts_count + 1, false);

    $settings = get_option('bluteem_aba_settings', array());
    $settings['total_posts'] = isset($settings['total_posts']) ? intval($settings['total_posts']) + 1 : 1;
    $settings['last_run'] = current_time('mysql');
    update_option('bluteem_aba_settings', $settings);
  }

  /**
   * Get remaining free posts (unlimited)
   *
   * @return int
   */
  public static function get_remaining_posts()
  {
    // Unlimited posts for all users
    return 999;
  }

  /**
   * Get total posts generated
   *
   * @return int
   */
  public static function get_posts_count()
  {
    $count = intval(get_option('bluteem_aba_posts_count', 0));
    $settings = get_option('bluteem_aba_settings', array());
    $total_posts = isset($settings['total_posts']) ? intval($settings['total_posts']) : 0;

    return max($count, $total_posts);
  }

  /**
   * Generate and create a blog post
   *
   * @param string $override_keyword Optional keyword to use instead of selecting from settings
   * @return array Result with success status and message
   */
  public static function generate_post($override_keyword = null)
  {
    // Get settings
    $settings = Bluteem_ABA_Settings::get_settings();

    // Validate required settings
    $validation = self::validate_settings($settings);
    if (!$validation['valid']) {
      return array(
        'success' => false,
        'message' => $validation['message']
      );
    }

    // Get keyword to use
    if (!empty($override_keyword)) {
      $keyword = trim($override_keyword);
    } else {
      $keyword = self::get_keyword($settings['base_keywords']);
    }

    if (empty($keyword)) {
      return array(
        'success' => false,
        'message' => __('No keywords configured.', 'ai-blog-automator')
      );
    }

    // Get word count based on post length (Pro can override with custom lengths)
    $word_count = Bluteem_ABA_Settings::get_word_count($settings['post_length']);
    $word_count = apply_filters('bluteem_aba_word_count', $word_count, $settings['post_length']);

    // Prepare prompt
    $prompt = self::prepare_prompt($settings['prompt_template'], $keyword, $word_count);

    // Call AI API
    $ai_response = self::call_ai_api($prompt, $settings);

    if (!$ai_response['success']) {
      self::log_error('AI API call failed: ' . $ai_response['message']);
      return $ai_response;
    }

    // Parse AI response
    $parsed_content = self::parse_ai_response($ai_response['content']);

    if (!$parsed_content['success']) {
      self::log_error('Failed to parse AI response');
      return $parsed_content;
    }

    // Create WordPress post
    $post_result = self::create_wordpress_post(
      $parsed_content['title'],
      $parsed_content['content'],
      $settings,
      $keyword
    );

    return $post_result;
  }

  /**
   * Validate settings before generating post
   *
   * @param array $settings Plugin settings
   * @return array Validation result
   */
  private static function validate_settings($settings)
  {
    // Always require API key
    if (empty($settings['api_key'])) {
      return array(
        'valid' => false,
        'message' => __('Please add your API key to start generating posts. Get a free API key from Groq: https://console.groq.com/', 'ai-blog-automator')
      );
    }

    // All users have unlimited posts (no restrictions)

    if (empty($settings['base_keywords'])) {
      return array(
        'valid' => false,
        'message' => __('Base keywords are not configured.', 'ai-blog-automator')
      );
    }

    if (empty($settings['prompt_template'])) {
      return array(
        'valid' => false,
        'message' => __('Prompt template is not configured.', 'ai-blog-automator')
      );
    }

    return array('valid' => true);
  }

  /**
   * Get a keyword from the configured keywords list
   * Analyzes recent posts to select the most different topic
   *
   * @param string $keywords_string Comma-separated keywords
   * @return string Single keyword
   */
  private static function get_keyword($keywords_string)
  {
    $keywords = array_map('trim', explode(',', $keywords_string));
    $keywords = array_filter($keywords); // Remove empty values

    if (empty($keywords)) {
      return '';
    }

    // If only one keyword, return it
    if (count($keywords) === 1) {
      return $keywords[0];
    }

    // Get recent posts to analyze topic diversity
    $recent_posts = get_posts(array(
      'numberposts' => 20,
      'post_status' => 'publish',
      'orderby' => 'date',
      'order' => 'DESC'
    ));

    // If no posts exist yet, return random keyword
    if (empty($recent_posts)) {
      return $keywords[array_rand($keywords)];
    }

    // Extract all words from recent post titles
    $recent_words = array();
    foreach ($recent_posts as $post) {
      $title = strtolower($post->post_title);
      // Remove common words and split into individual words
      $words = preg_split('/[\s\-_]+/', $title);
      foreach ($words as $word) {
        $word = trim($word, '.,!?;:()[]{}"\'"');
        if (strlen($word) > 2) { // Only words with 3+ characters
          $recent_words[] = $word;
        }
      }
    }

    // Score each keyword based on how different it is from recent posts
    $keyword_scores = array();
    foreach ($keywords as $keyword) {
      $keyword_lower = strtolower($keyword);
      $keyword_words = preg_split('/[\s\-_]+/', $keyword_lower);

      // Start with base score
      $score = 100;

      // Check how many times this keyword's words appear in recent titles
      foreach ($keyword_words as $kw_word) {
        $kw_word = trim($kw_word);
        if (strlen($kw_word) > 2) {
          $occurrences = 0;
          foreach ($recent_words as $recent_word) {
            // Check for exact match or partial match
            if ($recent_word === $kw_word || strpos($recent_word, $kw_word) !== false || strpos($kw_word, $recent_word) !== false) {
              $occurrences++;
            }
          }
          // Reduce score based on how many times it appears (more appearances = lower score)
          $score -= ($occurrences * 10);
        }
      }

      $keyword_scores[$keyword] = max(0, $score); // Ensure score doesn't go negative
    }

    // Sort by score (highest = most different from recent posts)
    arsort($keyword_scores);

    // Get top 3 most different keywords
    $top_keywords = array_slice(array_keys($keyword_scores), 0, 3);

    // Randomly select from the top 3 to add some variety
    $selected_keyword = $top_keywords[array_rand($top_keywords)];

    return $selected_keyword;
  }

  /**
   * Prepare the prompt with placeholders replaced
   *
   * @param string $template Prompt template
   * @param string $keyword Keyword to use
   * @param int $word_count Target word count
   * @return string Prepared prompt
   */
  private static function prepare_prompt($template, $keyword, $word_count)
  {
    $replacements = array(
      '{keyword}' => $keyword,
      '{length}' => $word_count,
      '{date}' => current_time('F j, Y'),
      '{year}' => current_time('Y')
    );

    return str_replace(
      array_keys($replacements),
      array_values($replacements),
      $template
    );
  }

  /**
   * Call AI API to generate content
   *
   * EXTERNAL SERVICE NOTICE:
   * This method sends data to an external AI service.
   * 
   * - Default endpoint: https://api.groq.com/openai/v1/chat/completions (Groq - free)
   * - Or: https://api.openai.com/v1/chat/completions (OpenAI - paid)
   * - Or custom endpoint configured by user
   * - Data transmitted: prompt text including keywords
   * - Groq Privacy: https://groq.com/privacy-policy/
   * - Groq Terms: https://groq.com/terms-of-use/
   * - OpenAI Privacy: https://openai.com/policies/privacy-policy
   * - OpenAI Terms: https://openai.com/policies/terms-of-use
   *
   * @param string $prompt The prepared prompt
   * @param array $settings Plugin settings
   * @return array API response with success status and content
   */
  private static function call_ai_api($prompt, $settings)
  {
    // Allow Pro (or other extensions) to handle AI calls through provider adapters.
    // Expected return format: ['success' => bool, 'content' => string] or ['success' => false, 'message' => string]
    $external_ai_response = apply_filters('bluteem_aba_ai_response', null, $prompt, $settings);
    if (is_array($external_ai_response) && isset($external_ai_response['success'])) {
      return $external_ai_response;
    }

    $api_key = $settings['api_key'];
    $api_endpoint = $settings['api_endpoint'];

    // Determine model and provider - Pro users can select, free users use Groq
    $provider = 'groq';
    if (class_exists('Bluteem_ABA_Pro_AI_Providers') && Bluteem_ABA_Premium::is_active()) {
      $provider = isset($settings['ai_provider']) ? $settings['ai_provider'] : 'groq';
      $model = Bluteem_ABA_Pro_AI_Providers::get_current_model();
    } elseif (strpos($api_endpoint, 'groq.com') !== false) {
      $model = 'llama-3.3-70b-versatile'; // Free Groq model (updated from deprecated 3.1)
    } else {
      $model = 'gpt-3.5-turbo'; // Default fallback
    }

    // Handle Anthropic/Claude API (different format)
    if ($provider === 'anthropic' || strpos($api_endpoint, 'anthropic.com') !== false) {
      return self::call_anthropic_api($prompt, $api_key, $model);
    }

    // Prepare request body for OpenAI-compatible API (OpenAI, Groq, custom)
    $body = array(
      'model' => $model,
      'messages' => array(
        array(
          'role' => 'system',
          'content' => 'You are a professional blog writer who creates engaging, SEO-optimized content.'
        ),
        array(
          'role' => 'user',
          'content' => $prompt
        )
      ),
      'temperature' => 0.7,
      'max_tokens' => 3000
    );

    // Prepare request arguments
    $args = array(
      'method' => 'POST',
      'timeout' => 60,
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $api_key
      ),
      'body' => wp_json_encode($body)
    );

    // Make the API request
    $response = wp_remote_post($api_endpoint, $args);

    // Check for errors
    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
      $response_body = wp_remote_retrieve_body($response);
      self::log_error("API returned status code: $response_code. Response: $response_body");

      return array(
        'success' => false,
        'message' => sprintf(
          /* translators: %d: HTTP status code */
          __('API request failed with status code: %d', 'ai-blog-automator'),
          $response_code
        )
      );
    }

    // Parse response
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return array(
        'success' => false,
        'message' => __('Failed to parse API response.', 'ai-blog-automator')
      );
    }

    // Extract content from OpenAI response format
    if (isset($data['choices'][0]['message']['content'])) {
      $content = $data['choices'][0]['message']['content'];

      return array(
        'success' => true,
        'content' => $content
      );
    }

    return array(
      'success' => false,
      'message' => __('Unexpected API response format.', 'ai-blog-automator')
    );
  }

  /**
   * Call Anthropic/Claude API (different format than OpenAI)
   *
   * @param string $prompt User prompt
   * @param string $api_key API key
   * @param string $model Model name
   * @return array API response with success status and content
   */
  private static function call_anthropic_api($prompt, $api_key, $model)
  {
    $endpoint = 'https://api.anthropic.com/v1/messages';

    // Anthropic API uses different format - system message is separate, messages array only has user messages
    $body = array(
      'model' => $model,
      'max_tokens' => 4096,
      'system' => 'You are a professional blog writer who creates engaging, SEO-optimized content.',
      'messages' => array(
        array(
          'role' => 'user',
          'content' => $prompt
        )
      )
    );

    // Anthropic requires different headers
    $args = array(
      'method' => 'POST',
      'timeout' => 60,
      'headers' => array(
        'Content-Type' => 'application/json',
        'x-api-key' => $api_key,
        'anthropic-version' => '2023-06-01'
      ),
      'body' => wp_json_encode($body)
    );

    // Make the API request
    $response = wp_remote_post($endpoint, $args);

    // Check for errors
    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
      $response_body = wp_remote_retrieve_body($response);
      self::log_error("Anthropic API returned status code: $response_code. Response: $response_body");

      return array(
        'success' => false,
        'message' => sprintf(
          /* translators: %d: HTTP status code */
          __('Anthropic API request failed with status code: %d', 'ai-blog-automator'),
          $response_code
        )
      );
    }

    // Parse Anthropic response
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return array(
        'success' => false,
        'message' => __('Failed to parse Anthropic API response.', 'ai-blog-automator')
      );
    }

    // Extract content from Anthropic response format
    if (isset($data['content'][0]['text'])) {
      $content = $data['content'][0]['text'];

      return array(
        'success' => true,
        'content' => $content
      );
    }

    return array(
      'success' => false,
      'message' => __('Unexpected Anthropic API response format.', 'ai-blog-automator')
    );
  }

  /**
   * Parse AI-generated content to extract title and body
   *
   * @param string $content Raw AI response
   * @return array Parsed content with title and body
   */
  private static function parse_ai_response($content)
  {
    $title = '';
    $body_content = $content;

    // Try to extract title from various formats
    // Format 1: "Title: Something"
    if (preg_match('/^Title:\s*(.+?)$/im', $content, $matches)) {
      $title = trim($matches[1]);
      $body_content = preg_replace('/^Title:\s*.+?$/im', '', $content, 1);
    }
    // Format 2: "# Something" (Markdown heading)
    elseif (preg_match('/^#\s+(.+?)$/m', $content, $matches)) {
      $title = trim($matches[1]);
      $body_content = preg_replace('/^#\s+.+?$/m', '', $content, 1);
    }
    // Format 3: First line is the title
    else {
      $lines = explode("\n", $content);
      if (!empty($lines[0])) {
        $title = trim($lines[0]);
        array_shift($lines);
        $body_content = implode("\n", $lines);
      }
    }

    // Clean up title
    $title = trim($title);
    $title = str_replace(array('**', '__', '*', '_'), '', $title); // Remove markdown emphasis
    $title = preg_replace('/^["\']+|["\']+$/', '', $title); // Remove quotes

    // If no title found, generate one from first line
    if (empty($title)) {
      $title = self::generate_fallback_title($body_content);
    }

    // Clean up body content
    $body_content = trim($body_content);

    // Convert markdown to HTML if needed
    $body_content = self::convert_markdown_to_html($body_content);

    // Ensure we have both title and content
    if (empty($title) || empty($body_content)) {
      return array(
        'success' => false,
        'message' => __('Failed to extract title or content from AI response.', 'ai-blog-automator')
      );
    }

    return array(
      'success' => true,
      'title' => $title,
      'content' => $body_content
    );
  }

  /**
   * Generate a fallback title from content
   *
   * @param string $content Post content
   * @return string Generated title
   */
  private static function generate_fallback_title($content)
  {
    $words = explode(' ', wp_strip_all_tags($content));
    $title_words = array_slice($words, 0, 8);
    $title = implode(' ', $title_words);

    if (strlen($content) > strlen($title)) {
      $title .= '...';
    }

    return $title;
  }

  /**
   * Convert basic markdown to HTML
   *
   * @param string $content Markdown content
   * @return string HTML content
   */
  private static function convert_markdown_to_html($content)
  {
    // Convert headings
    $content = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $content);
    $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content);
    $content = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $content);

    // Convert bold
    $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
    $content = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $content);

    // Convert italic
    $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content);
    $content = preg_replace('/_(.+?)_/', '<em>$1</em>', $content);

    // Convert lists
    $content = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $content);
    $content = preg_replace('/^- (.+)$/m', '<li>$1</li>', $content);
    $content = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $content);

    // Convert line breaks to paragraphs
    $paragraphs = explode("\n\n", $content);
    $formatted_paragraphs = array();

    foreach ($paragraphs as $para) {
      $para = trim($para);
      if (!empty($para) && !preg_match('/^<[a-z]/i', $para)) {
        $para = '<p>' . nl2br($para) . '</p>';
      }
      $formatted_paragraphs[] = $para;
    }

    return implode("\n\n", $formatted_paragraphs);
  }

  /**
   * Intelligently determine or create the best category for a post
   *
   * @param string $title Post title
   * @param string $content Post content
   * @param string $keyword Original keyword used
   * @return int Category ID
   */
  private static function get_or_create_category($title, $content, $keyword)
  {
    // Get all existing categories
    $categories = get_categories(array(
      'hide_empty' => false,
      'orderby' => 'count',
      'order' => 'DESC'
    ));

    // Extract key terms from title and keyword
    $search_terms = strtolower($title . ' ' . $keyword);
    $search_terms = preg_replace('/[^a-z0-9\s]/', '', $search_terms);
    $search_words = array_unique(explode(' ', $search_terms));

    // Remove common stop words
    $stop_words = array('the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'is', 'it', 'this', 'that', 'how', 'what', 'why', 'when', 'where', 'who', 'which');
    $search_words = array_diff($search_words, $stop_words);
    $search_words = array_filter($search_words, function ($word) {
      return strlen($word) > 3; // Only words longer than 3 chars
    });

    // Try to find the best matching existing category
    $best_match_score = 0;
    $best_match_id = 0;

    foreach ($categories as $category) {
      if ($category->slug === 'uncategorized') {
        continue;
      }

      $cat_name_lower = strtolower($category->name);
      $cat_slug_lower = strtolower($category->slug);
      $score = 0;

      // Check for exact matches in category name/slug
      foreach ($search_words as $word) {
        if (strpos($cat_name_lower, $word) !== false || strpos($cat_slug_lower, $word) !== false) {
          $score += 10;
        }
        if ($cat_name_lower === $word || $cat_slug_lower === $word) {
          $score += 50; // High score for exact match
        }
      }

      if ($score > $best_match_score) {
        $best_match_score = $score;
        $best_match_id = $category->term_id;
      }
    }

    // If we found a good match (score > 10), use it
    if ($best_match_score > 10 && $best_match_id > 0) {
      return $best_match_id;
    }

    // No good match found - create a new category based on the keyword
    // Clean up keyword to make a good category name
    $category_name = ucwords(strtolower(trim($keyword)));
    $category_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $category_name);
    $category_name = trim($category_name);

    // Check if this category already exists
    $existing = get_term_by('name', $category_name, 'category');
    if ($existing) {
      return $existing->term_id;
    }

    // Create new category
    $new_category = wp_insert_term($category_name, 'category', array(
      'description' => sprintf(
        /* translators: %s: Category name */
        __('Posts about %s', 'ai-blog-automator'),
        $category_name
      ),
      'slug' => sanitize_title($category_name)
    ));

    if (is_wp_error($new_category)) {
      // If creation failed, use Uncategorized
      $uncategorized = get_term_by('slug', 'uncategorized', 'category');
      return $uncategorized ? $uncategorized->term_id : 1;
    }

    return $new_category['term_id'];
  }

  /**
   * Create WordPress post
   *
   * @param string $title Post title
   * @param string $content Post content
   * @param array $settings Plugin settings
   * @param string $keyword Keyword used for generation
   * @return array Result with success status and message
   */
  private static function create_wordpress_post($title, $content, $settings, $keyword = '')
  {
    // Intelligently determine the best category
    $category_id = self::get_or_create_category($title, $content, $keyword);

    // Prepare post data
    $post_data = array(
      'post_title' => wp_strip_all_tags($title),
      'post_content' => $content,
      'post_status' => $settings['auto_publish'],
      'post_author' => 1, // Default to admin user
      'post_category' => array($category_id),
      'post_type' => 'post',
      'comment_status' => 'open',
      'ping_status' => 'open'
    );

    // Insert post
    $post_id = wp_insert_post($post_data, true);

    // Check for errors
    if (is_wp_error($post_id)) {
      self::log_error('Failed to create post: ' . $post_id->get_error_message());

      return array(
        'success' => false,
        'message' => $post_id->get_error_message()
      );
    }

    // Add custom meta to track auto-generated posts
    add_post_meta($post_id, '_bluteem_aba_generated', true);
    add_post_meta($post_id, '_bluteem_aba_timestamp', current_time('mysql'));

    // Update counters immediately so manual generation is counted even if later steps are slow
    self::record_post_generation();

    // Add featured image
    $featured_image_result = self::add_featured_image($post_id, $keyword);
    if (!$featured_image_result['success']) {
      // Log but don't fail the post creation
      self::log_error('Featured image failed: ' . $featured_image_result['message']);
    }

    // Allow premium features to enhance the post (SEO, analytics, etc.)
    do_action('bluteem_aba_after_post_created', $post_id, $title, $content);

    $status_text = ($settings['auto_publish'] === 'publish')
      ? __('published', 'ai-blog-automator')
      : __('saved as draft', 'ai-blog-automator');

    self::log_success("Post created successfully: $title (ID: $post_id)");

    return array(
      'success' => true,
      'message' => sprintf(
        /* translators: 1: Post title, 2: Status text (published or saved as draft) */
        __('Post "%1$s" has been %2$s successfully!', 'ai-blog-automator'),
        $title,
        $status_text
      ),
      'post_id' => $post_id,
      'edit_link' => get_edit_post_link($post_id, 'raw')
    );
  }

  /**
   * Add featured image to post
   * Uses Unsplash API (free, unlimited, attribution required)
   *
   * @param int $post_id Post ID
   * @param string $keyword Keyword for image search
   * @return array Result with success status and message
   */
  private static function add_featured_image($post_id, $keyword)
  {
    // Use Unsplash API for all users (free, unlimited)
    return self::fetch_unsplash_image($post_id, $keyword);
  }

  /**
   * Fetch and set featured image from Unsplash
   *
   * EXTERNAL SERVICE NOTICE:
   * This method uses Unsplash API to fetch free stock photos.
   * - Service: Unsplash (https://unsplash.com)
   * - API Endpoint: https://api.unsplash.com/photos/random
   * - Data transmitted: Search keyword only
   * - Privacy: https://unsplash.com/privacy
   * - Terms: https://unsplash.com/terms (requires attribution)
   * - Requires: Free Unsplash API key from https://unsplash.com/developers
   *
   * @param int $post_id Post ID
   * @param string $keyword Keyword for image search
   * @return array Result with success status and message
   */
  private static function fetch_unsplash_image($post_id, $keyword)
  {
    // Get Unsplash API key from settings
    $settings = Bluteem_ABA_Settings::get_settings();
    $unsplash_api_key = isset($settings['unsplash_api_key']) ? trim($settings['unsplash_api_key']) : '';

    // No key: skip featured image quietly (key is optional in settings)
    if (empty($unsplash_api_key)) {
      return array(
        'success' => true,
        'message' => '',
      );
    }

    // Clean keyword for search
    $search_query = sanitize_text_field($keyword);

    // Unsplash API endpoint
    $api_url = 'https://api.unsplash.com/photos/random?query=' . urlencode($search_query) . '&orientation=landscape';

    // Make request to Unsplash with API key
    $response = wp_remote_get($api_url, array(
      'timeout' => 15,
      'headers' => array(
        'Accept' => 'application/json',
        'Authorization' => 'Client-ID ' . $unsplash_api_key
      )
    ));

    // Check for errors
    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
      return array(
        'success' => false,
        'message' => 'Unsplash API returned status ' . $response_code
      );
    }

    // Parse response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data['urls']['regular'])) {
      return array(
        'success' => false,
        'message' => 'No image URL in Unsplash response'
      );
    }

    $image_url = $data['urls']['regular'];
    $photographer_name = isset($data['user']['name']) ? $data['user']['name'] : 'Unknown';
    $photographer_link = isset($data['user']['links']['html']) ? $data['user']['links']['html'] : '';

    // Download image to WordPress media library
    $attachment_id = self::download_image_to_media_library($image_url, $post_id, $search_query);

    if (is_wp_error($attachment_id)) {
      return array(
        'success' => false,
        'message' => $attachment_id->get_error_message()
      );
    }

    // Set as featured image
    set_post_thumbnail($post_id, $attachment_id);

    // Add attribution in image caption (Unsplash terms requirement)
    wp_update_post(array(
      'ID' => $attachment_id,
      'post_excerpt' => sprintf(
        'Photo by %s on Unsplash',
        $photographer_name
      )
    ));

    return array(
      'success' => true,
      'message' => 'Featured image added successfully',
      'attachment_id' => $attachment_id
    );
  }

  /**
   * Download image from URL and add to media library
   *
   * @param string $image_url Image URL
   * @param int $post_id Post ID to attach to
   * @param string $filename Base filename
   * @return int|WP_Error Attachment ID or error
   */
  private static function download_image_to_media_library($image_url, $post_id, $filename)
  {
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Download image
    $tmp = download_url($image_url);

    if (is_wp_error($tmp)) {
      return $tmp;
    }

    // Prepare file array
    $file_array = array(
      'name' => sanitize_file_name($filename) . '-' . time() . '.jpg',
      'tmp_name' => $tmp
    );

    // Upload to media library
    $attachment_id = media_handle_sideload($file_array, $post_id, sprintf(
      /* translators: %s: Filename */
      __('Featured image for: %s', 'ai-blog-automator'),
      $filename
    ));

    // Clean up temp file
    if (file_exists($tmp)) {
      wp_delete_file($tmp);
    }

    return $attachment_id;
  }

  /**
   * Log error message
   *
   * @param string $message Error message
   */
  private static function log_error($message)
  {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      error_log('AI Blog Automator Error: ' . $message);
    }

    // Store in option for admin display
    $errors = get_option(Bluteem_ABA_Settings::ERRORS_OPTION, array());
    $errors[] = array(
      'message' => $message,
      'timestamp' => current_time('mysql')
    );

    // Keep only last 10 errors
    $errors = array_slice($errors, -10);
    update_option(Bluteem_ABA_Settings::ERRORS_OPTION, $errors);
  }

  /**
   * Log success message
   *
   * @param string $message Success message
   */
  private static function log_success($message)
  {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      error_log('AI Blog Automator Success: ' . $message);
    }
  }
}
