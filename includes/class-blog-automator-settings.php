<?php

/**
 * Settings Page Handler
 */

defined('ABSPATH') || exit;

class WebPenter_ABM_Settings
{
  const OPTION_NAME = 'webpenter_abm_settings';
  const ERRORS_OPTION = 'webpenter_abm_errors';
  const LOGS_PAGE_SLUG = 'ai-blog-master-logs';

  public static function init_error_handlers()
  {
    add_action('admin_post_webpenter_abm_clear_errors', array(__CLASS__, 'handle_clear_errors'));
    add_action('wp_ajax_abm_test_api', array(__CLASS__, 'handle_test_api'));
  }

  public static function handle_test_api()
  {
    check_ajax_referer('abm_test_api_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error('Unauthorized');
    }

    $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : 'gemini';
    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

    if (empty($api_key)) {
      wp_send_json_error('API Key is empty');
    }

    $result = WebPenter_ABM_Generator::test_api_connection($provider, $api_key);

    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
    } else {
      wp_send_json_success('Connection successful!');
    }
  }

  public static function get_recent_errors()
  {
    $errors = get_option(self::ERRORS_OPTION, array());
    return is_array($errors) ? $errors : array();
  }

  public static function get_logs_page_url()
  {
    return admin_url('admin.php?page=' . self::LOGS_PAGE_SLUG);
  }

  public static function add_error($message)
  {
      $errors = self::get_recent_errors();
      array_unshift($errors, array(
          'time' => current_time('mysql'),
          'message' => $message
      ));
      // Keep only last 100 errors
      if (count($errors) > 100) {
          $errors = array_slice($errors, 0, 100);
      }
      update_option(self::ERRORS_OPTION, $errors);
  }

  public static function handle_clear_errors()
  {
    if (!current_user_can('manage_options')) return;
    check_admin_referer('webpenter_abm_clear_errors');
    delete_option(self::ERRORS_OPTION);
    wp_safe_redirect(add_query_arg(array('page' => self::LOGS_PAGE_SLUG, 'errors_cleared' => '1'), admin_url('admin.php')));
    exit;
  }

  public static function add_admin_menu()
  {
    add_menu_page(
      __('Script-and-Tool-AI-Content-Writer-WordPress-Plugin', 'ai-blog-master'),
      __('Script-and-Tool-AI-Content-Writer-WordPress-Plugin', 'ai-blog-master'),
      'manage_options',
      'ai-blog-master-settings',
      array(__CLASS__, 'render_settings_page'),
      'dashicons-edit',
      30
    );
  }

  public static function add_logs_menu()
  {
    $error_count = count(self::get_recent_errors());
    $logs_menu_title = __('Logs', 'ai-blog-master');
    if ($error_count > 0) {
      $logs_menu_title .= sprintf(' <span class="awaiting-mod"><span class="pending-count">%d</span></span>', $error_count);
    }
    
    add_submenu_page(
      'ai-blog-master-settings',
      __('Logs & Debug', 'ai-blog-master'),
      $logs_menu_title,
      'manage_options',
      self::LOGS_PAGE_SLUG,
      array(__CLASS__, 'render_logs_page')
    );
  }

  public static function render_logs_page()
  {
    if (!current_user_can('manage_options')) return;
    include WEBPENTER_ABM_PLUGIN_DIR . 'includes/templates/logs-page.php';
  }

  public static function register_settings()
  {
    register_setting('webpenter_abm_settings_group', self::OPTION_NAME, array(__CLASS__, 'sanitize_settings'));
  }

  public static function enqueue_admin_styles($hook)
  {
    $allowed_hooks = array(
      'toplevel_page_ai-blog-master-settings',
      'ai-blog-master_page_' . self::LOGS_PAGE_SLUG,
    );
    if (!in_array($hook, $allowed_hooks, true)) return;

    wp_enqueue_style('webpenter-abm-admin', WEBPENTER_ABM_PLUGIN_URL . 'assets/admin.css', array(), WEBPENTER_ABM_VERSION);
    wp_enqueue_script('webpenter-abm-admin', WEBPENTER_ABM_PLUGIN_URL . 'assets/admin.js', array('jquery'), WEBPENTER_ABM_VERSION, true);

    wp_localize_script('webpenter-abm-admin', 'abm_vars', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce('abm_test_api_nonce')
    ));
  }

  public static function render_settings_page()
  {
    if (!current_user_can('manage_options')) return;
    include WEBPENTER_ABM_PLUGIN_DIR . 'includes/templates/settings-page.php';
  }

  public static function get_settings()
  {
    $defaults = array(
      'ai_provider' => 'gemini',
      'gemini_api_key' => '',
      'groq_api_key' => '',
      'pixabay_api_key' => '',
      'automation_status' => 'disabled',
      'post_type' => 'blog',
      'posts_per_batch' => 2,
      'schedule_frequency' => 'custom',
      'custom_interval_seconds' => 60,
      'topics' => '',
      'word_count' => 300,
      'affiliate_code' => '',
      'last_run' => '',
      'total_posts' => 0
    );

    $settings = get_option(self::OPTION_NAME, $defaults);
    return wp_parse_args($settings, $defaults);
  }

  public static function update_setting($key, $value)
  {
      $settings = self::get_settings();
      $settings[$key] = $value;
      update_option(self::OPTION_NAME, $settings);
  }

  public static function sanitize_settings($input)
  {
    $sanitized = array();
    $current = self::get_settings();
    
    $sanitized['ai_provider'] = isset($input['ai_provider']) && $input['ai_provider'] === 'groq' ? 'groq' : 'gemini';
    $sanitized['gemini_api_key'] = isset($input['gemini_api_key']) ? sanitize_text_field($input['gemini_api_key']) : '';
    $sanitized['groq_api_key'] = isset($input['groq_api_key']) ? sanitize_text_field($input['groq_api_key']) : '';
    $sanitized['pixabay_api_key'] = isset($input['pixabay_api_key']) ? sanitize_text_field($input['pixabay_api_key']) : '';
    $sanitized['automation_status'] = isset($input['automation_status']) && $input['automation_status'] === 'enabled' ? 'enabled' : 'disabled';
    $sanitized['post_type'] = isset($input['post_type']) ? sanitize_text_field($input['post_type']) : 'blog';
    $sanitized['posts_per_batch'] = isset($input['posts_per_batch']) ? absint($input['posts_per_batch']) : 2;
    $sanitized['schedule_frequency'] = isset($input['schedule_frequency']) ? sanitize_text_field($input['schedule_frequency']) : 'custom';
    $sanitized['custom_interval_seconds'] = isset($input['custom_interval_seconds']) ? max(1, absint($input['custom_interval_seconds'])) : 60;
    
    if (isset($input['topics'])) {
        $sanitized['topics'] = sanitize_textarea_field($input['topics']);
    }
    
    $sanitized['word_count'] = isset($input['word_count']) ? absint($input['word_count']) : 300;
    
    if (isset($input['affiliate_code'])) {
        // Allow HTML for affiliate code
        $sanitized['affiliate_code'] = wp_kses_post($input['affiliate_code']);
    }

    $sanitized['last_run'] = $current['last_run'];
    $sanitized['total_posts'] = $current['total_posts'];

    return $sanitized;
  }
}
