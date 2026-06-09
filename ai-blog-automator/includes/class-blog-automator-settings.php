<?php

/**
 * Settings Page Handler
 *
 * Manages the admin settings page and configuration options
 *
 * @package AI_Blog_Automator
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Settings Page Class
 */
class Bluteem_ABA_Settings
{

  /**
   * Option name in database
   */
  const OPTION_NAME = 'bluteem_aba_settings';

  /**
   * Option storing recent error log entries.
   */
  const ERRORS_OPTION = 'bluteem_aba_errors';

  /**
   * Logs submenu slug.
   */
  const LOGS_PAGE_SLUG = 'ai-blog-automator-logs';

  /**
   * Register admin handler for clearing the error log.
   */
  public static function init_error_handlers()
  {
    add_action('admin_post_bluteem_aba_clear_errors', array(__CLASS__, 'handle_clear_errors'));
  }

  /**
   * Get stored error log entries.
   *
   * @return array
   */
  public static function get_recent_errors()
  {
    $errors = get_option(self::ERRORS_OPTION, array());

    return is_array($errors) ? $errors : array();
  }

  /**
   * URL for the Logs admin page.
   *
   * @return string
   */
  public static function get_logs_page_url()
  {
    return admin_url('admin.php?page=' . self::LOGS_PAGE_SLUG);
  }

  /**
   * Clear all stored errors.
   */
  public static function handle_clear_errors()
  {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'ai-blog-automator'));
    }

    check_admin_referer('bluteem_aba_clear_errors');

    delete_option(self::ERRORS_OPTION);

    wp_safe_redirect(
      add_query_arg(
        array(
          'page' => self::LOGS_PAGE_SLUG,
          'errors_cleared' => '1',
          '_wpnonce' => wp_create_nonce('bluteem_aba_errors_feedback'),
        ),
        admin_url('admin.php')
      )
    );
    exit;
  }

  /**
   * Add admin menu
   */
  public static function add_admin_menu()
  {
    // Load SVG icon and encode it for WordPress menu
    $icon_path = BLUTEEM_ABA_PLUGIN_DIR . 'assets/icon.svg';
    $icon_svg = file_exists($icon_path) ? file_get_contents($icon_path) : '';

    if ($icon_svg) {
      // WordPress admin menu icons should use fill color that matches the admin color scheme
      // Replace black fill with currentColor for proper theme integration
      $icon_svg = str_replace('fill="#000000"', 'fill="currentColor"', $icon_svg);
      $icon_svg = str_replace('fill="#000"', 'fill="currentColor"', $icon_svg);

      // Encode SVG for use as data URI
      $icon_data = 'data:image/svg+xml;base64,' . base64_encode($icon_svg);
    } else {
      // Fallback to dashicon if SVG not found
      $icon_data = 'dashicons-edit';
    }

    add_menu_page(
      __('AI Blog Automator', 'ai-blog-automator'),        // Page title
      __('AI Blog Automator', 'ai-blog-automator'),        // Menu title
      'manage_options',                                     // Capability
      'ai-blog-automator',                                  // Menu slug
      array(__CLASS__, 'render_settings_page'),            // Callback
      $icon_data,                                           // Icon (custom SVG)
      30                                                    // Position (after Comments)
    );
  }

  /**
   * Add Logs submenu (runs after Pro submenu items).
   */
  public static function add_logs_menu()
  {
    $error_count = count(self::get_recent_errors());
    $logs_menu_title = sprintf(
      '<span class="dashicons dashicons-clipboard bluteem-aba-menu-icon" aria-hidden="true"></span> %s',
      esc_html__('Logs', 'ai-blog-automator')
    );

    if ($error_count > 0) {
      $logs_menu_title .= sprintf(
        ' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>',
        $error_count
      );
    }

    add_submenu_page(
      'ai-blog-automator',
      __('AI Blog Automator - Logs', 'ai-blog-automator'),
      $logs_menu_title,
      'manage_options',
      self::LOGS_PAGE_SLUG,
      array(__CLASS__, 'render_logs_page')
    );
  }

  /**
   * Render the error logs page.
   */
  public static function render_logs_page()
  {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'ai-blog-automator'));
    }

    $template_path = BLUTEEM_ABA_PLUGIN_DIR . 'includes/templates/logs-page.php';
    if (file_exists($template_path)) {
      include $template_path;
    }
  }

  /**
   * Register settings
   */
  public static function register_settings()
  {
    register_setting(
      'bluteem_aba_settings_group',
      self::OPTION_NAME,
      array(__CLASS__, 'sanitize_settings')
    );
  }

  /**
   * Enqueue admin styles
   *
   * @param string $hook Current admin page hook
   */
  public static function enqueue_admin_styles($hook)
  {
    $allowed_hooks = array(
      'toplevel_page_ai-blog-automator',
      'ai-blog-automator_page_' . self::LOGS_PAGE_SLUG,
    );

    if (!in_array($hook, $allowed_hooks, true)) {
      return;
    }

    // Enqueue admin CSS file
    wp_enqueue_style(
      'bluteem-aba-admin',
      BLUTEEM_ABA_PLUGIN_URL . 'assets/admin.css',
      array(),
      BLUTEEM_ABA_VERSION
    );

    wp_enqueue_script(
      'bluteem-aba-admin',
      BLUTEEM_ABA_PLUGIN_URL . 'assets/admin.js',
      array(),
      BLUTEEM_ABA_VERSION,
      true
    );

    wp_localize_script(
      'bluteem-aba-admin',
      'bluteemAbaAdmin',
      array(
        'generatingLabel' => __('Generating...', 'ai-blog-automator'),
      )
    );
  }

  /**
   * Render settings page
   */
  public static function render_settings_page()
  {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'ai-blog-automator'));
    }

    $template_path = BLUTEEM_ABA_PLUGIN_DIR . 'includes/templates/settings-page.php';
    if (file_exists($template_path)) {
      include $template_path;
    }
  }

  /**
   * Get all settings with defaults
   *
   * @return array Settings array
   */
  public static function get_settings()
  {
    $defaults = array(
      'api_key' => '',
      'unsplash_api_key' => '',
      'base_keywords' => '',
      'post_length' => 'medium',
      'frequency' => 'daily',
      'auto_publish' => 'publish',
      'prompt_template' => '',
      'api_endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
      'last_run' => '',
      'total_posts' => 0
    );

    $settings = get_option(self::OPTION_NAME, $defaults);
    $settings = wp_parse_args($settings, $defaults);

    // Free version: Always use Groq endpoint (by design, not a locked feature)
    if (!Bluteem_ABA_Premium::is_active()) {
      $settings['api_endpoint'] = 'https://api.groq.com/openai/v1/chat/completions';
    }

    // Ensure default prompt template is set if empty (for all users)
    if (empty($settings['prompt_template'])) {
      $settings['prompt_template'] = "Write a comprehensive blog post about {keyword}.\n\nLength: {length} words\n\nStructure:\n- Engaging introduction with a hook\n- 3-5 main sections with subheadings\n- Each section should provide valuable, actionable information\n- Conclusion with key takeaways\n\nTone: Informative, conversational, and SEO-friendly\nStyle: Human-like writing with natural flow\nSEO: Include relevant keywords naturally throughout the content\n\nPlease format the output as follows:\nTitle: [Your engaging title here]\n\n[Rest of the content]";
    }

    // Allow Pro features to override settings (e.g., bulk generation)
    $settings = apply_filters('bluteem_aba_override_settings', $settings);

    return $settings;
  }

  /**
   * Analyze existing content and generate smart keywords
   * 
   * @return string Comma-separated keywords
   */
  public static function generate_smart_keywords()
  {
    $keywords = array();

    // Get categories
    $categories = get_categories(array(
      'hide_empty' => false,
      'number' => 10,
      'orderby' => 'count',
      'order' => 'DESC'
    ));

    foreach ($categories as $category) {
      if ($category->slug !== 'uncategorized') {
        $keywords[] = $category->name;
      }
    }

    // Get tags
    $tags = get_tags(array(
      'hide_empty' => false,
      'number' => 10,
      'orderby' => 'count',
      'order' => 'DESC'
    ));

    foreach ($tags as $tag) {
      $keywords[] = $tag->name;
    }

    // Analyze recent post titles for common topics
    $recent_posts = get_posts(array(
      'numberposts' => 20,
      'post_status' => 'publish',
      'orderby' => 'date',
      'order' => 'DESC'
    ));

    // Extract common words from titles (excluding stop words)
    $stop_words = array('the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'is', 'it', 'this', 'that', 'by', 'from', 'as', 'are', 'was', 'were', 'be', 'been', 'has', 'have', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should');
    $word_frequency = array();

    foreach ($recent_posts as $post) {
      $title = strtolower($post->post_title);
      $words = preg_split('/\s+/', $title);

      foreach ($words as $word) {
        $word = trim($word, '.,!?;:()[]{}');
        if (strlen($word) > 3 && !in_array($word, $stop_words) && !is_numeric($word)) {
          if (!isset($word_frequency[$word])) {
            $word_frequency[$word] = 0;
          }
          $word_frequency[$word]++;
        }
      }
    }

    // Get top 5 frequent words
    arsort($word_frequency);
    $top_words = array_slice(array_keys($word_frequency), 0, 5);
    foreach ($top_words as $word) {
      $keywords[] = ucfirst($word);
    }

    // Remove duplicates and limit to 15 keywords
    $keywords = array_unique($keywords);
    $keywords = array_slice($keywords, 0, 15);

    // If no keywords found, provide defaults based on site niche
    if (empty($keywords)) {
      $site_name = get_bloginfo('name');
      $site_description = get_bloginfo('description');

      // Try to detect niche from site info
      if (
        stripos($site_name . ' ' . $site_description, 'tech') !== false ||
        stripos($site_name . ' ' . $site_description, 'technology') !== false
      ) {
        $keywords = array('Technology trends', 'Tech tips', 'Innovation', 'Digital transformation');
      } elseif (
        stripos($site_name . ' ' . $site_description, 'food') !== false ||
        stripos($site_name . ' ' . $site_description, 'recipe') !== false
      ) {
        $keywords = array('Recipes', 'Cooking tips', 'Food trends', 'Healthy eating');
      } elseif (stripos($site_name . ' ' . $site_description, 'travel') !== false) {
        $keywords = array('Travel destinations', 'Travel tips', 'Adventure', 'Tourism');
      } elseif (stripos($site_name . ' ' . $site_description, 'business') !== false) {
        $keywords = array('Business strategy', 'Entrepreneurship', 'Marketing', 'Growth');
      } else {
        // Generic defaults
        $keywords = array('Tips and tricks', 'Best practices', 'How to guide', 'Latest trends');
      }
    }

    return implode(', ', $keywords);
  }

  /**
   * Get specific setting
   *
   * @param string $key Setting key
   * @param mixed $default Default value
   * @return mixed Setting value
   */
  public static function get_setting($key, $default = '')
  {
    $settings = self::get_settings();
    return isset($settings[$key]) ? $settings[$key] : $default;
  }

  /**
   * Update specific setting
   *
   * @param string $key Setting key
   * @param mixed $value Setting value
   * @return bool True on success
   */
  public static function update_setting($key, $value)
  {
    $settings = self::get_settings();
    $settings[$key] = $value;
    return update_option(self::OPTION_NAME, $settings);
  }

  /**
   * Sanitize settings before saving
   *
   * @param array $input Raw input data
   * @return array Sanitized data
   */
  public static function sanitize_settings($input)
  {
    $sanitized = array();

    // Allow Pro features to add additional sanitization
    $sanitized = apply_filters('bluteem_aba_sanitize_settings', $sanitized, $input);

    // API Key - sanitize as text
    if (isset($input['api_key'])) {
      $sanitized['api_key'] = sanitize_text_field($input['api_key']);
    }

    // Unsplash API Key - sanitize as text
    if (isset($input['unsplash_api_key'])) {
      $sanitized['unsplash_api_key'] = sanitize_text_field($input['unsplash_api_key']);
    }

    // Base Keywords - sanitize as textarea
    if (isset($input['base_keywords'])) {
      $sanitized['base_keywords'] = sanitize_textarea_field($input['base_keywords']);
    }

    // Post Length - validate against allowed values
    $allowed_lengths = array('short', 'medium', 'long');

    // Pro users can use custom length
    if (Bluteem_ABA_Premium::is_active()) {
      $allowed_lengths[] = 'custom';
    }

    if (isset($input['post_length']) && in_array($input['post_length'], $allowed_lengths, true)) {
      // Validate against whitelist, then sanitize for extra safety
      $sanitized['post_length'] = sanitize_text_field($input['post_length']);
    } else {
      $sanitized['post_length'] = 'medium';
    }

    // Custom Word Count - only for Pro users
    if (Bluteem_ABA_Premium::is_active() && isset($input['custom_word_count'])) {
      // Validate and sanitize: ensure it's a number, then clamp to valid range
      $custom_count = absint($input['custom_word_count']);
      // Clamp to valid range (300-5000)
      $sanitized['custom_word_count'] = max(300, min(5000, $custom_count));
    }

    // Frequency - validate against allowed values
    // Free version: daily, twicedaily, weekly (all shown in UI must work)
    $allowed_frequencies = array('daily', 'twicedaily', 'weekly');

    // Premium users get additional frequencies via filter
    if (Bluteem_ABA_Premium::is_active()) {
      $allowed_frequencies = array_merge($allowed_frequencies, array(
        'hourly',
        'every_two_hours',
        'every_three_hours',
        'every_six_hours'
      ));
    }

    // Allow premium plugin to add custom frequencies
    $allowed_frequencies = apply_filters('bluteem_aba_allowed_frequencies', $allowed_frequencies);

    if (isset($input['frequency']) && in_array($input['frequency'], $allowed_frequencies, true)) {
      // Validate against whitelist, then sanitize for extra safety
      $sanitized['frequency'] = sanitize_text_field($input['frequency']);
    } else {
      $sanitized['frequency'] = 'daily';
    }

    // Auto Publish - validate against allowed values
    $allowed_statuses = array('publish', 'draft');
    if (isset($input['auto_publish']) && in_array($input['auto_publish'], $allowed_statuses, true)) {
      // Validate against whitelist, then sanitize for extra safety
      $sanitized['auto_publish'] = sanitize_text_field($input['auto_publish']);
    } else {
      $sanitized['auto_publish'] = 'publish';
    }


    // Prompt Template - Pro users can edit via textarea, free users preserve via hidden field
    if (isset($input['prompt_template'])) {
      $sanitized['prompt_template'] = wp_kses_post($input['prompt_template']);
    } else {
      // If not provided, preserve existing value
      $current_settings = self::get_settings();
      $sanitized['prompt_template'] = $current_settings['prompt_template'];
    }

    // API Endpoint - Free: always Groq. Pro: the settings form does not post
    // api_endpoint for preset/custom providers; Bluteem_ABA_Pro_AI_Providers sets it
    // in bluteem_aba_sanitize_settings. Do not overwrite that with Groq here.
    $current_settings = self::get_settings();
    if (!Bluteem_ABA_Premium::is_active()) {
      $sanitized['api_endpoint'] = 'https://api.groq.com/openai/v1/chat/completions';
    } elseif (isset($input['api_endpoint']) && $input['api_endpoint'] !== '') {
      $sanitized['api_endpoint'] = esc_url_raw($input['api_endpoint']);
    } elseif (!empty($sanitized['api_endpoint'])) {
      // Kept: already set by Pro filter from provider preset or custom_ai_endpoint.
    } elseif (!empty($current_settings['api_endpoint'])) {
      $sanitized['api_endpoint'] = esc_url_raw($current_settings['api_endpoint']);
    } else {
      $sanitized['api_endpoint'] = 'https://api.groq.com/openai/v1/chat/completions';
    }

    // Preserve system fields
    $sanitized['last_run'] = $current_settings['last_run'];
    $sanitized['total_posts'] = $current_settings['total_posts'];

    // Reschedule cron if frequency changed
    if (isset($input['frequency']) && $input['frequency'] !== $current_settings['frequency']) {
      Bluteem_ABA_Cron::reschedule_event($input['frequency']);
    }

    return $sanitized;
  }

  /**
   * Get post length in words
   *
   * @param string $length Length identifier (short/medium/long)
   * @return int Word count
   */
  public static function get_word_count($length)
  {
    $word_counts = array(
      'short' => 500,
      'medium' => 1000,
      'long' => 2000
    );

    return isset($word_counts[$length]) ? $word_counts[$length] : 1000;
  }

  /**
   * Get available categories
   *
   * @return array Categories
   */
  public static function get_categories()
  {
    $categories = get_categories(array(
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC'
    ));

    return $categories;
  }

  /**
   * Test API connection
   *
   * @return array Result with success status and message
   */
  public static function test_api_connection()
  {
    $api_key = self::get_setting('api_key');

    if (empty($api_key)) {
      return array(
        'success' => false,
        'message' => __('API key is required.', 'ai-blog-automator')
      );
    }

    // This is a placeholder - actual implementation depends on the API being used
    return array(
      'success' => true,
      'message' => __('API configuration saved. Test the connection by generating a post.', 'ai-blog-automator')
    );
  }
}
