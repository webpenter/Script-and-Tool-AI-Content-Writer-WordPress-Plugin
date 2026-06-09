<?php

/**
 * Plugin Name: Script-and-Tool-AI-Content-Writer-WordPress-Plugin
 * Plugin URI: https://webpenter.com/
 * Description: Automatically generates and publishes SEO-optimized blog posts using Google Gemini API & Pixabay API. Custom built by Fayyaz Ahmad.
 * Version: 99.9.9
 * Author: Fayyaz Ahmad @ WebPenter
 * Author URI: https://webpenter.com/
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
define('WEBPENTER_ABA_VERSION', '99.9.9');
define('WEBPENTER_ABA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEBPENTER_ABA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WEBPENTER_ABA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class WebPenter_ABA_Main
{

  /**
   * Single instance of the class
   *
   * @var WebPenter_ABA_Main
   */
  private static $instance = null;

  /**
   * Get single instance
   *
   * @return WebPenter_ABA_Main
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
    require_once WEBPENTER_ABA_PLUGIN_DIR . 'includes/class-blog-automator-settings.php';
    require_once WEBPENTER_ABA_PLUGIN_DIR . 'includes/class-blog-automator-generator.php';
    require_once WEBPENTER_ABA_PLUGIN_DIR . 'includes/class-blog-automator-cron.php';
  }

  /**
   * Initialize WordPress hooks
   */
  private function init_hooks()
  {
    // Initialize settings page
    add_action('admin_menu', array('WebPenter_ABA_Settings', 'add_admin_menu'));
    add_action('admin_menu', array('WebPenter_ABA_Settings', 'add_logs_menu'), 25);
    add_action('admin_init', array('WebPenter_ABA_Settings', 'register_settings'));
    WebPenter_ABA_Settings::init_error_handlers();
    add_action('admin_enqueue_scripts', array('WebPenter_ABA_Settings', 'enqueue_admin_styles'));

    // Register Custom Post Type "Blog"
    add_action('init', array($this, 'register_blog_post_type'));

    // Initialize cron system
    WebPenter_ABA_Cron::init();

    // Add settings link to plugins page
    add_filter('plugin_action_links_' . WEBPENTER_ABA_PLUGIN_BASENAME, array($this, 'add_settings_link'));

    // Intercept template for "blog" post type
    add_filter('single_template', array($this, 'custom_blog_template'));
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
      admin_url('admin.php?page=ai-blog-automator-settings'),
      __('Settings', 'ai-blog-automator')
    );
    array_unshift($links, $settings_link);
    return $links;
  }

  /**
   * Register Blog Custom Post Type
   */
  public function register_blog_post_type()
  {
    if (post_type_exists('blog')) {
        return; // Avoid conflicts if already registered by theme
    }

    $labels = array(
        'name'                  => _x('Blogs', 'Post type general name', 'ai-blog-automator'),
        'singular_name'         => _x('Blog', 'Post type singular name', 'ai-blog-automator'),
        'menu_name'             => _x('Blogs', 'Admin Menu text', 'ai-blog-automator'),
        'name_admin_bar'        => _x('Blog', 'Add New on Toolbar', 'ai-blog-automator'),
        'add_new'               => __('Add New', 'ai-blog-automator'),
        'add_new_item'          => __('Add New Blog', 'ai-blog-automator'),
        'new_item'              => __('New Blog', 'ai-blog-automator'),
        'edit_item'             => __('Edit Blog', 'ai-blog-automator'),
        'view_item'             => __('View Blog', 'ai-blog-automator'),
        'all_items'             => __('All Blogs', 'ai-blog-automator'),
        'search_items'          => __('Search Blogs', 'ai-blog-automator'),
        'parent_item_colon'     => __('Parent Blogs:', 'ai-blog-automator'),
        'not_found'             => __('No blogs found.', 'ai-blog-automator'),
        'not_found_in_trash'    => __('No blogs found in Trash.', 'ai-blog-automator'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'blog'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5, // Below Posts
        'menu_icon'          => 'dashicons-welcome-write-blog',
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
        'taxonomies'         => array('category', 'post_tag'),
        'show_in_rest'       => true,
    );

    register_post_type('blog', $args);
  }

  /**
   * Use custom template for single blog CPT posts
   */
  public function custom_blog_template($single_template)
  {
    global $post;
    if ($post && $post->post_type === 'blog') {
        // 1. Check if the theme already has single-blog.php
        $theme_template = locate_template(array('single-blog.php'));
        if ($theme_template) {
            return $theme_template;
        }

        // 2. Fallback to the plugin's built-in modern template
        $custom_path = WEBPENTER_ABA_PLUGIN_DIR . 'includes/templates/single-blog.php';
        if (file_exists($custom_path)) {
            return $custom_path;
        }
    }
    return $single_template;
  }
}

/**
 * Plugin activation hook
 */
function webpenter_aba_activate()
{
  $defaults = array(
    'gemini_api_key' => '',
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

  $existing_options = get_option('webpenter_aba_settings');
  if (false === $existing_options) {
    add_option('webpenter_aba_settings', $defaults);
  } else {
      // Merge with new defaults
      update_option('webpenter_aba_settings', wp_parse_args($existing_options, $defaults));
  }

  // Initialize post counter
  if (false === get_option('webpenter_aba_posts_count')) {
    add_option('webpenter_aba_posts_count', 0);
  }

  // Schedule cron event
  WebPenter_ABA_Cron::schedule_event();

  if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Script-and-Tool-AI-Content-Writer-WordPress-Plugin: Custom version activated.');
  }

  // Register post type and flush rewrite rules immediately so 404 errors don't occur
  $plugin = WebPenter_ABA_Main::get_instance();
  $plugin->register_blog_post_type();
  flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'webpenter_aba_activate');

/**
 * Plugin deactivation hook
 */
function webpenter_aba_deactivate()
{
  WebPenter_ABA_Cron::clear_scheduled_event();
  if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Script-and-Tool-AI-Content-Writer-WordPress-Plugin: Plugin deactivated.');
  }
}
register_deactivation_hook(__FILE__, 'webpenter_aba_deactivate');

/**
 * Initialize the plugin
 */
function webpenter_aba_init()
{
  return WebPenter_ABA_Main::get_instance();
}

// Start the plugin
webpenter_aba_init();
