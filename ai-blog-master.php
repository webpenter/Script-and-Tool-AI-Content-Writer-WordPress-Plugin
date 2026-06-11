<?php

/**
 * Plugin Name: AI Blog Master
 * Plugin URI: https://webpenter.com/
 * Description: Automatically generates and publishes SEO-optimized blog posts using AI (Google Gemini or Groq) & Pixabay API. Custom built by Fayyaz Ahmad.
 * Version: 1.0.7
 * Author: Fayyaz Ahmad @ WebPenter
 * Author URI: https://webpenter.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-blog-master
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Define plugin constants
define('WEBPENTER_ABM_VERSION', '1.0.7');
define('WEBPENTER_ABM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEBPENTER_ABM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WEBPENTER_ABM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class WebPenter_ABM_Main
{

  /**
   * Single instance of the class
   *
   * @var WebPenter_ABM_Main
   */
  private static $instance = null;

  /**
   * Get single instance
   *
   * @return WebPenter_ABM_Main
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
    require_once WEBPENTER_ABM_PLUGIN_DIR . 'includes/class-blog-automator-settings.php';
    require_once WEBPENTER_ABM_PLUGIN_DIR . 'includes/class-blog-automator-generator.php';
    require_once WEBPENTER_ABM_PLUGIN_DIR . 'includes/class-blog-automator-cron.php';
  }

  /**
   * Initialize WordPress hooks
   */
  private function init_hooks()
  {
    // Initialize settings page
    add_action('admin_menu', array('WebPenter_ABM_Settings', 'add_admin_menu'));
    add_action('admin_menu', array('WebPenter_ABM_Settings', 'add_logs_menu'), 25);
    add_action('admin_init', array('WebPenter_ABM_Settings', 'register_settings'));
    WebPenter_ABM_Settings::init_error_handlers();
    add_action('admin_enqueue_scripts', array('WebPenter_ABM_Settings', 'enqueue_admin_styles'));

    // Register Custom Post Type "Blog"
    add_action('init', array($this, 'register_blog_post_type'));

    // Initialize cron system
    WebPenter_ABM_Cron::init();

    // Add settings link to plugins page
    add_filter('plugin_action_links_' . WEBPENTER_ABM_PLUGIN_BASENAME, array($this, 'add_settings_link'));

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
      admin_url('admin.php?page=ai-blog-master-settings'),
      __('Settings', 'ai-blog-master')
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
        'name'                  => _x('Blogs', 'Post type general name', 'ai-blog-master'),
        'singular_name'         => _x('Blog', 'Post type singular name', 'ai-blog-master'),
        'menu_name'             => _x('Blogs', 'Admin Menu text', 'ai-blog-master'),
        'name_admin_bar'        => _x('Blog', 'Add New on Toolbar', 'ai-blog-master'),
        'add_new'               => __('Add New', 'ai-blog-master'),
        'add_new_item'          => __('Add New Blog', 'ai-blog-master'),
        'new_item'              => __('New Blog', 'ai-blog-master'),
        'edit_item'             => __('Edit Blog', 'ai-blog-master'),
        'view_item'             => __('View Blog', 'ai-blog-master'),
        'all_items'             => __('All Blogs', 'ai-blog-master'),
        'search_items'          => __('Search Blogs', 'ai-blog-master'),
        'parent_item_colon'     => __('Parent Blogs:', 'ai-blog-master'),
        'not_found'             => __('No blogs found.', 'ai-blog-master'),
        'not_found_in_trash'    => __('No blogs found in Trash.', 'ai-blog-master'),
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
        $custom_path = WEBPENTER_ABM_PLUGIN_DIR . 'includes/templates/single-blog.php';
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
function webpenter_abm_activate()
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

  $existing_options = get_option('webpenter_abm_settings');
  if (false === $existing_options) {
    add_option('webpenter_abm_settings', $defaults);
  } else {
      // Merge with new defaults
      update_option('webpenter_abm_settings', wp_parse_args($existing_options, $defaults));
  }

  // Initialize post counter
  if (false === get_option('webpenter_abm_posts_count')) {
    add_option('webpenter_abm_posts_count', 0);
  }

  // Schedule cron event
  WebPenter_ABM_Cron::schedule_event();

  if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('AI Blog Master: Custom version activated.');
  }

  // Register post type and flush rewrite rules immediately so 404 errors don't occur
  $plugin = WebPenter_ABM_Main::get_instance();
  $plugin->register_blog_post_type();
  flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'webpenter_abm_activate');

/**
 * Plugin deactivation hook
 */
function webpenter_abm_deactivate()
{
  WebPenter_ABM_Cron::clear_scheduled_event();
  if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('AI Blog Master: Plugin deactivated.');
  }
}
register_deactivation_hook(__FILE__, 'webpenter_abm_deactivate');

/**
 * Initialize the plugin
 */
function webpenter_abm_init()
{
  return WebPenter_ABM_Main::get_instance();
}

// Start the plugin
webpenter_abm_init();
