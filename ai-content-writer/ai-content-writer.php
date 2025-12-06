<?php
/**
 * Plugin Name: AI Content Writer
 * Plugin URI:  https://fayyazahmed.com/ai-content-writer/
 * Description: Generate AI-powered content using Google Gemini API
 * Version:     2.1.0
 * Author:      Fayyaz Ahmed
 * Author URI:  https://fayyazahmed.com
 * Text Domain: ai-content-writer
 */

if (!defined('ABSPATH')) exit;

if ( ! defined( 'AICW_PLUGIN_URL' ) ) {
	define( 'AICW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'AICW_PLUGIN_DIR' ) ) {
	define( 'AICW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AICW_VERSION' ) ) {
	define( 'AICW_VERSION', '1.0.0' );
}

/* ---------- autoload our classes ---------- */
require_once AICW_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
require_once AICW_PLUGIN_DIR . 'includes/helpers-api.php';
require_once AICW_PLUGIN_DIR . 'includes/helpers-prompt.php';
require_once AICW_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once AICW_PLUGIN_DIR . 'includes/class-main.php';
require_once AICW_PLUGIN_DIR . 'includes/class-image.php';
require_once AICW_PLUGIN_DIR . 'includes/class-export.php';

/* ---------- fire it up ---------- */
add_action('plugins_loaded', 'aicw_boot');
function aicw_boot() {
    // Main plugin initialization
    AI_Content_Writer_Pro::get_instance();

    // ✅ Initialize AJAX handlers
    if (class_exists('AICW_Ajax_Handlers')) {
        AICW_Ajax_Handlers::init();
    }
}
