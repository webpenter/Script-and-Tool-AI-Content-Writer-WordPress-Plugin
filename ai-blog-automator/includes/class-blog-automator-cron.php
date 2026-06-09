<?php

/**
 * Cron System
 *
 * Manages scheduled post generation using WordPress Cron
 *
 * @package AI_Blog_Automator
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Cron System Class
 */
class Bluteem_ABA_Cron
{

  /**
   * Cron hook name
   */
  const CRON_HOOK = 'bluteem_aba_generate_post';

  /**
   * Initialize cron system
   */
  public static function init()
  {
    // Register the cron action
    add_action(self::CRON_HOOK, array(__CLASS__, 'execute_generation'));

    // Add custom cron schedules
    add_filter('cron_schedules', array(__CLASS__, 'add_custom_schedules'));

    // Add admin action for manual generation
    add_action('admin_post_bluteem_aba_generate_now', array(__CLASS__, 'manual_generation'));
  }

  /**
   * Add custom cron schedules
   *
   * @param array $schedules Existing schedules
   * @return array Modified schedules
   */
  public static function add_custom_schedules($schedules)
  {
    // Add weekly schedule if not exists
    if (!isset($schedules['weekly'])) {
      $schedules['weekly'] = array(
        'interval' => 604800, // 7 days in seconds
        'display' => __('Once Weekly', 'ai-blog-automator')
      );
    }

    return $schedules;
  }

  /**
   * Schedule the cron event
   *
   * @param string|null $frequency Optional frequency override
   */
  public static function schedule_event($frequency = null)
  {
    // Clear any existing scheduled event first
    self::clear_scheduled_event();

    // Get frequency from settings or use override
    if (null === $frequency) {
      $frequency = Bluteem_ABA_Settings::get_setting('frequency', 'daily');
    }

    // Validate frequency - use same validation as settings (free: daily, twicedaily, weekly)
    $allowed_frequencies = array('daily', 'twicedaily', 'weekly');

    // Allow Pro to add custom frequencies via filter
    $allowed_frequencies = apply_filters('bluteem_aba_allowed_frequencies', $allowed_frequencies);

    // Also check if it's a valid WordPress cron schedule (for any custom schedules registered)
    $cron_schedules = wp_get_schedules();
    if (!in_array($frequency, $allowed_frequencies, true) && !isset($cron_schedules[$frequency])) {
      $frequency = 'daily';
    }

    // Schedule the event
    $scheduled = wp_schedule_event(time(), $frequency, self::CRON_HOOK);

    if (false === $scheduled) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log('AI Blog Automator: Failed to schedule cron event');
      }
    } else {
      if (defined('WP_DEBUG') && WP_DEBUG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log("AI Blog Automator: Cron event scheduled with frequency: $frequency");
      }
    }
  }

  /**
   * Clear scheduled cron event
   */
  public static function clear_scheduled_event()
  {
    $timestamp = wp_next_scheduled(self::CRON_HOOK);

    if ($timestamp) {
      wp_unschedule_event($timestamp, self::CRON_HOOK);

      if (defined('WP_DEBUG') && WP_DEBUG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log('AI Blog Automator: Cron event unscheduled');
      }
    }
  }

  /**
   * Reschedule event with new frequency
   *
   * @param string $new_frequency New frequency
   */
  public static function reschedule_event($new_frequency)
  {
    self::schedule_event($new_frequency);

    if (defined('WP_DEBUG') && WP_DEBUG) {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      error_log("AI Blog Automator: Cron event rescheduled to: $new_frequency");
    }
  }

  /**
   * Execute post generation (called by cron)
   */
  public static function execute_generation()
  {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
      error_log('AI Blog Automator: Cron job started');
    }

    // Generate the post
    $result = Bluteem_ABA_Generator::generate_post();

    if ($result['success']) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log('AI Blog Automator: Cron job completed successfully - ' . $result['message']);
      }
    } else {
      if (defined('WP_DEBUG') && WP_DEBUG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log('AI Blog Automator: Cron job failed - ' . $result['message']);
      }
    }

    // Send admin notification email if enabled
    self::maybe_send_notification($result);
  }

  /**
   * Manual generation triggered by admin
   */
  public static function manual_generation()
  {
    // Check permissions
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'ai-blog-automator'));
    }

    // Verify nonce - sanitize input since wp_verify_nonce is pluggable
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'bluteem_aba_generate_now')) {
      wp_die(esc_html__('Security check failed.', 'ai-blog-automator'));
    }

    // Generate post
    $result = Bluteem_ABA_Generator::generate_post();

    // Prepare redirect URL with message
    $redirect_url = add_query_arg(
      array(
        'page' => 'ai-blog-automator',
        'generation_result' => $result['success'] ? 'success' : 'error',
        'generation_message' => urlencode($result['message']),
        '_wpnonce' => wp_create_nonce('bluteem_aba_generation_result')
      ),
      admin_url('admin.php')
    );

    // Add edit link if post was created
    if ($result['success'] && isset($result['post_id'])) {
      $redirect_url = add_query_arg('post_id', $result['post_id'], $redirect_url);
    }

    // Redirect back to settings page
    wp_safe_redirect($redirect_url);
    exit;
  }

  /**
   * Send email notification to admin
   *
   * @param array $result Generation result
   */
  private static function maybe_send_notification($result)
  {
    // Check if notifications are enabled (could be a future setting)
    $send_notifications = apply_filters('bluteem_aba_send_notifications', false);

    if (!$send_notifications) {
      return;
    }

    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');

    if ($result['success']) {
      $subject = sprintf(
        /* translators: %s: Site name */
        __('[%s] New AI-Generated Post Published', 'ai-blog-automator'),
        $site_name
      );

      $message = sprintf(
        /* translators: 1: Result message, 2: Admin posts URL */
        __('A new blog post has been automatically generated and published on your website.

%1$s

View all posts: %2$s', 'ai-blog-automator'),
        $result['message'],
        admin_url('edit.php')
      );
    } else {
      $subject = sprintf(
        /* translators: %s: Site name */
        __('[%s] AI Blog Automator Error', 'ai-blog-automator'),
        $site_name
      );

      $message = sprintf(
        /* translators: 1: Error message, 2: Settings page URL */
        __('An error occurred while trying to generate a blog post:

%1$s

Please check your settings: %2$s', 'ai-blog-automator'),
        $result['message'],
        admin_url('admin.php?page=ai-blog-automator')
      );
    }

    wp_mail($admin_email, $subject, $message);
  }

  /**
   * Get next scheduled run time
   *
   * @return string|null Formatted date string or null if not scheduled
   */
  public static function get_next_run_time()
  {
    $timestamp = wp_next_scheduled(self::CRON_HOOK);

    if (!$timestamp) {
      return null;
    }

    return get_date_from_gmt(gmdate('Y-m-d H:i:s', $timestamp), 'F j, Y g:i a');
  }

  /**
   * Check if cron is scheduled
   *
   * @return bool True if scheduled
   */
  public static function is_scheduled()
  {
    return (bool) wp_next_scheduled(self::CRON_HOOK);
  }

  /**
   * Get cron schedule interval in human-readable format
   *
   * @return string Schedule description
   */
  public static function get_schedule_description()
  {
    $frequency = Bluteem_ABA_Settings::get_setting('frequency', 'daily');

    $descriptions = array(
      'hourly' => __('Every hour', 'ai-blog-automator'),
      'twicedaily' => __('Twice daily', 'ai-blog-automator'),
      'daily' => __('Once daily', 'ai-blog-automator'),
      'weekly' => __('Once weekly', 'ai-blog-automator')
    );

    return isset($descriptions[$frequency]) ? $descriptions[$frequency] : $frequency;
  }

  /**
   * Get all scheduled AI Blog Automator cron events
   *
   * @return array Cron event details
   */
  public static function get_cron_info()
  {
    $timestamp = wp_next_scheduled(self::CRON_HOOK);

    if (!$timestamp) {
      return array(
        'scheduled' => false,
        'next_run' => null,
        'frequency' => null
      );
    }

    $crons = _get_cron_array();
    $schedule = null;

    if (isset($crons[$timestamp][self::CRON_HOOK])) {
      $event = reset($crons[$timestamp][self::CRON_HOOK]);
      $schedule = isset($event['schedule']) ? $event['schedule'] : null;
    }

    return array(
      'scheduled' => true,
      'next_run' => $timestamp,
      'next_run_formatted' => self::get_next_run_time(),
      'frequency' => $schedule,
      'frequency_description' => self::get_schedule_description()
    );
  }
}
