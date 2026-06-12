<?php
/**
 * Cron System Handler
 */

defined('ABSPATH') || exit;

class WebPenter_ABM_Cron
{
  const CRON_HOOK = 'webpenter_abm_cron_generate';

  public static function init()
  {
    add_filter('cron_schedules', array(__CLASS__, 'add_custom_schedules'));
    add_action(self::CRON_HOOK, array(__CLASS__, 'process_cron'));
    add_action('admin_post_webpenter_abm_generate_now', array(__CLASS__, 'handle_manual_run'));
    add_action('update_option_webpenter_abm_settings', array(__CLASS__, 'reschedule_event'));
  }

  public static function add_custom_schedules($schedules)
  {
    $settings = WebPenter_ABM_Settings::get_settings();
    $custom_seconds = isset($settings['custom_interval_seconds']) ? absint($settings['custom_interval_seconds']) : 60;
    if ($custom_seconds < 60) $custom_seconds = 60;

    $schedules['webpenter_abm_custom_interval'] = array(
      'interval' => $custom_seconds,
      'display'  => sprintf(__('Custom (%d seconds)', 'webpenter-ai-blog-master'), $custom_seconds)
    );

    $schedules['minutely'] = array(
      'interval' => 60,
      'display'  => __('Every Minute', 'webpenter-ai-blog-master')
    );

    return $schedules;
  }

  public static function schedule_event()
  {
    $settings = WebPenter_ABM_Settings::get_settings();
    if ($settings['automation_status'] !== 'enabled') {
        self::clear_scheduled_event();
        return;
    }

    $frequency = $settings['schedule_frequency'];
    $cron_schedule = '';

    if ($frequency === 'custom') {
        $cron_schedule = 'webpenter_abm_custom_interval';
    } elseif ($frequency === 'minutely') {
        $cron_schedule = 'minutely';
    } elseif ($frequency === 'hourly') {
        $cron_schedule = 'hourly';
    } elseif ($frequency === 'daily') {
        $cron_schedule = 'daily';
    } else {
        $cron_schedule = 'daily';
    }

    if (!wp_next_scheduled(self::CRON_HOOK)) {
      wp_schedule_event(time() + 10, $cron_schedule, self::CRON_HOOK);
    }
  }

  public static function clear_scheduled_event()
  {
    $timestamp = wp_next_scheduled(self::CRON_HOOK);
    if ($timestamp) {
      wp_unschedule_event($timestamp, self::CRON_HOOK);
    }
  }

  public static function reschedule_event()
  {
    self::clear_scheduled_event();
    self::schedule_event();
  }

  public static function process_cron()
  {
    $settings = WebPenter_ABM_Settings::get_settings();
    if ($settings['automation_status'] === 'enabled') {
      WebPenter_ABM_Generator::generate_batch();
    }
  }

  public static function handle_manual_run()
  {
    if (!current_user_can('manage_options')) return;
    check_admin_referer('webpenter_abm_generate_now');

    $initial_errors = count(WebPenter_ABM_Settings::get_recent_errors());

    WebPenter_ABM_Generator::generate_batch();
    
    $final_errors = count(WebPenter_ABM_Settings::get_recent_errors());

    // Check if new errors occurred
    if ($final_errors > $initial_errors) {
        wp_safe_redirect(add_query_arg(
          array('page' => 'webpenter-ai-blog-master-settings', 'generation_result' => 'error', 'generation_message' => urlencode('Batch execution finished, but there were errors. Check Logs.')),
          admin_url('admin.php')
        ));
    } else {
        wp_safe_redirect(add_query_arg(
          array('page' => 'webpenter-ai-blog-master-settings', 'generation_result' => 'success', 'generation_message' => urlencode('Batch execution completed successfully!')),
          admin_url('admin.php')
        ));
    }
    exit;
  }

  public static function get_cron_info()
  {
    $timestamp = wp_next_scheduled(self::CRON_HOOK);
    
    if ($timestamp) {
      return array(
        'scheduled' => true,
        'next_run' => $timestamp,
        'next_run_formatted' => get_date_from_gmt(date('Y-m-d H:i:s', $timestamp), 'F j, Y g:i a'),
        'frequency_description' => 'Will run next'
      );
    }

    return array(
      'scheduled' => false,
      'next_run' => 0,
      'next_run_formatted' => '',
      'frequency_description' => ''
    );
  }
}
