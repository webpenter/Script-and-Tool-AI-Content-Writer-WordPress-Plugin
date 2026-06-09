<?php

/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 * Cleans up all plugin data from the database.
 *
 * @package AI_Blog_Automator
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

// Security check - ensure this is a legitimate uninstall
if (!current_user_can('activate_plugins')) {
  return;
}

/**
 * Delete plugin options
 */
function webpenter_aba_uninstall_cleanup()
{
  // Delete main plugin settings
  delete_option('webpenter_aba_settings');

  // Delete error logs
  delete_option('webpenter_aba_errors');
  delete_option('webpenter_aba_errors_notice_id');

  // Delete post counter
  delete_option('webpenter_aba_posts_count');

  // Delete keyword tracking
  delete_option('webpenter_aba_recent_keywords');

  // For multisite installations, delete from all sites
  if (is_multisite()) {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
    $original_blog_id = get_current_blog_id();

    foreach ($blog_ids as $blog_id) {
      switch_to_blog($blog_id);

      // Delete options for this site
      delete_option('webpenter_aba_settings');
      delete_option('webpenter_aba_errors');

      // Delete post meta for generated posts
      webpenter_aba_delete_post_meta();
    }

    switch_to_blog($original_blog_id);
  } else {
    // Single site - delete post meta
    webpenter_aba_delete_post_meta();
  }

  // Clear any scheduled cron events
  $timestamp = wp_next_scheduled('webpenter_aba_generate_post');
  if ($timestamp) {
    wp_unschedule_event($timestamp, 'webpenter_aba_generate_post');
  }
}

/**
 * Delete post meta from all AI-generated posts
 *
 * Note: This only removes meta tags that identify posts as AI-generated.
 * The actual posts remain in the database.
 */
function webpenter_aba_delete_post_meta()
{
  global $wpdb;

  // Delete custom post meta
  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
  $wpdb->delete(
    $wpdb->postmeta,
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
    array('meta_key' => '_webpenter_aba_generated'),
    array('%s')
  );

  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
  $wpdb->delete(
    $wpdb->postmeta,
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
    array('meta_key' => '_webpenter_aba_timestamp'),
    array('%s')
  );
}

// Run the cleanup
webpenter_aba_uninstall_cleanup();

// Log uninstallation (if debug mode is enabled)
if (defined('WP_DEBUG') && WP_DEBUG) {
  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
  error_log('Script-and-Tool-AI-Content-Writer-WordPress-Plugin: Plugin uninstalled and all data cleaned up');
}
