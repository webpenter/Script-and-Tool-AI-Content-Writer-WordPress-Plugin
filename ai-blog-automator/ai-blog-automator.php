<?php

/**
 * Plugin Name: AI Blog Automator
 * Plugin URI: https://bluteem.com/ai-blog-automator/
 * Description: Automatically generates and publishes SEO-optimized blog posts using an AI API with customizable scheduling and settings.
 * Version: 1.0.7
 * Author: Bluteem LLC
 * Author URI: https://bluteem.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-blog-automator
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Define plugin constants
define('BLUTEEM_ABA_VERSION', '1.0.7');
define('BLUTEEM_ABA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BLUTEEM_ABA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BLUTEEM_ABA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Bluteem_ABA_Main
{

  /**
   * Single instance of the class
   *
   * @var Bluteem_ABA_Main
   */
  private static $instance = null;

  /**
   * Get single instance
   *
   * @return Bluteem_ABA_Main
   */
  public static function get_instance()
  {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Constructor
   */
  private function __construct()
  {
    $this->load_dependencies();
    $this->init_hooks();
  }

  /**
   * Load required dependencies
   */
  private function load_dependencies()
  {
    require_once BLUTEEM_ABA_PLUGIN_DIR . 'includes/class-blog-automator-settings.php';
    require_once BLUTEEM_ABA_PLUGIN_DIR . 'includes/class-blog-automator-generator.php';
    require_once BLUTEEM_ABA_PLUGIN_DIR . 'includes/class-blog-automator-cron.php';
    require_once BLUTEEM_ABA_PLUGIN_DIR . 'includes/class-blog-automator-premium.php';
  }

  /**
   * Initialize WordPress hooks
   */
  private function init_hooks()
  {
    // Initialize settings page
    add_action('admin_menu', array('Bluteem_ABA_Settings', 'add_admin_menu'));
    add_action('admin_menu', array('Bluteem_ABA_Settings', 'add_logs_menu'), 25);
    add_action('admin_init', array('Bluteem_ABA_Settings', 'register_settings'));
    Bluteem_ABA_Settings::init_error_handlers();
    add_action('admin_enqueue_scripts', array('Bluteem_ABA_Settings', 'enqueue_admin_styles'));
    add_action('admin_enqueue_scripts', array('Bluteem_ABA_Premium', 'enqueue_premium_styles'));

    // Initialize cron system
    Bluteem_ABA_Cron::init();

    // Initialize premium features
    Bluteem_ABA_Premium::add_premium_tab();

    // Add settings link to plugins page
    add_filter('plugin_action_links_' . BLUTEEM_ABA_PLUGIN_BASENAME, array($this, 'add_settings_link'));

    // Add upgrade link to plugins page
    add_filter('plugin_action_links_' . BLUTEEM_ABA_PLUGIN_BASENAME, array($this, 'add_upgrade_link'));
  }

  /**
   * Add settings link to plugin actions
   *
   * @param array $links Plugin action links
   * @return array Modified links
   */
  public function add_settings_link($links)
  {
    $settings_link = sprintf(
      '<a href="%s">%s</a>',
      admin_url('admin.php?page=ai-blog-automator'),
      __('Settings', 'ai-blog-automator')
    );
    array_unshift($links, $settings_link);
    return $links;
  }

  /**
   * Add upgrade link to plugin actions
   *
   * @param array $links Plugin action links
   * @return array Modified links
   */
  public function add_upgrade_link($links)
  {
    if (!Bluteem_ABA_Premium::is_active()) {
      $upgrade_link = sprintf(
        '<a href="%s" style="color: #46b450; font-weight: 700;" target="_blank">%s</a>',
        Bluteem_ABA_Premium::get_upgrade_url('plugins_page'),
        __('Upgrade to Pro', 'ai-blog-automator')
      );
      $links[] = $upgrade_link;
    }
    return $links;
  }
}

/**
 * Plugin activation hook
 */
function bluteem_aba_activate()
{
  // Set default options - keywords must be manually configured by user
  $defaults = array(
    'api_key' => '',
    'base_keywords' => '', // User must configure keywords manually
    'post_length' => 'medium',
    'frequency' => 'daily',
    'auto_publish' => 'publish', // Auto-publish for full automation
    'prompt_template' => "Write a comprehensive blog post about {keyword}.\n\nLength: {length} words\n\nStructure:\n- Engaging introduction with a hook\n- 3-5 main sections with subheadings\n- Each section should provide valuable, actionable information\n- Conclusion with key takeaways\n\nTone: Informative, conversational, and SEO-friendly\nStyle: Human-like writing with natural flow\nSEO: Include relevant keywords naturally throughout the content\n\nPlease format the output as follows:\nTitle: [Your engaging title here]\n\n[Rest of the content]",
    'api_endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
    'last_run' => '',
    'total_posts' => 0
  );

  $existing_options = get_option('bluteem_aba_settings');
  if (false === $existing_options) {
    add_option('bluteem_aba_settings', $defaults);
  }

  // Initialize post counter (for statistics)
  if (false === get_option('bluteem_aba_posts_count')) {
    add_option('bluteem_aba_posts_count', 0);
  }

  // Schedule cron event (but don't generate a post immediately)
  Bluteem_ABA_Cron::schedule_event();

  // Log activation
  if (defined('WP_DEBUG') && WP_DEBUG) {
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    error_log('AI Blog Automator: Plugin activated successfully');
  }
}
register_activation_hook(__FILE__, 'bluteem_aba_activate');

/**
 * Plugin deactivation hook
 */
function bluteem_aba_deactivate()
{
  // Clear scheduled cron event
  Bluteem_ABA_Cron::clear_scheduled_event();

  // Log deactivation
  if (defined('WP_DEBUG') && WP_DEBUG) {
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    error_log('AI Blog Automator: Plugin deactivated successfully');
  }
}
register_deactivation_hook(__FILE__, 'bluteem_aba_deactivate');

/**
 * Initialize the plugin
 */
function bluteem_aba_init()
{
  return Bluteem_ABA_Main::get_instance();
}

// Start the plugin
bluteem_aba_init();
