<?php
class AI_Content_Writer_Pro {

	private static $instance = null;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action('admin_menu',        [$this, 'add_admin_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin']);
		
		// ✅ New: Add Meta Box hooks
		add_action('add_meta_boxes',      [$this, 'add_ai_generator_meta_box']);
		
		// sub-modules hook themselves up
		AICW_Ajax_Handlers::init();
		AICW_Shortcode::init();
	}

	/* ------------------------------------------------------------------
	 * Admin menus
	 * ------------------------------------------------------------------ */
	public function add_admin_menu() {
		add_menu_page(
			__('AI Content Writer ', 'ai-content-writer'),
			__('AI Content Writer', 'ai-content-writer'),
			'manage_options',
			'ai-content-writer',
			[$this, 'render_generator_page'],
			'dashicons-edit',
			30
		);
		add_submenu_page(
			'ai-content-writer',
			__('Content Generator', 'ai-content-writer'),
			__('Content Generator', 'ai-content-writer'),
			'manage_options',
			'ai-content-writer',
			[$this, 'render_generator_page']
		);
		add_submenu_page(
			'ai-content-writer',
			__('API Settings', 'ai-content-writer'),
			__('API Settings', 'ai-content-writer'),
			'manage_options',
			'ai-content-writer-settings',
			[$this, 'render_settings_page']
		);
	}
	
	/* ------------------------------------------------------------------
	 * Meta Box for Post/Page Editor
	 * ------------------------------------------------------------------ */
	public function add_ai_generator_meta_box() {
        // Add meta box to Post and Page editing screens
		add_meta_box(
			'aicw_content_generator_box',           // Unique ID
			__('AI Content Generator', 'ai-content-writer'), // Box title
			[$this, 'render_meta_box'],             // Content callback
			['post', 'page'],                       // Post types
			'side',                                 // Context (side column)
			'high'                                  // Priority
		);
	}

	public function render_meta_box($post) {
		// Use the same generator form template, but without the main wrap/header
		$api_key = aicw_get_decrypted_key('aicw_gemini_api_key');
		include AICW_PLUGIN_DIR . 'templates/generator-meta-box.php';
	}


	/* ------------------------------------------------------------------
	 * Assets
	 * ------------------------------------------------------------------ */
	public function enqueue_admin($hook) {
		// Check if we are on our own pages OR a post/page edit screen
		if (strpos($hook, 'ai-content-writer') === false && ('post.php' !== $hook) && ('post-new.php' !== $hook)) return;

		wp_enqueue_style('aicw-admin',  AICW_PLUGIN_URL . 'assets/css/admin-style.css', [], AICW_VERSION);
		wp_enqueue_script('aicw-admin', AICW_PLUGIN_URL . 'assets/js/admin-script.js',  ['jquery'], AICW_VERSION, true);
        wp_enqueue_style('aicw-frontend',  AICW_PLUGIN_URL . 'assets/css/frontend-style.css', [], AICW_VERSION);
		wp_enqueue_script('aicw-frontend', AICW_PLUGIN_URL . 'assets/js/frontend-script.js', ['jquery'], AICW_VERSION, true);
		wp_enqueue_script('marked', 'https://cdn.jsdelivr.net/npm/marked/marked.min.js', [], null, true);

		wp_localize_script('aicw-admin', 'aicwAjax', [
			'ajax_url'       => admin_url('admin-ajax.php'),
			'nonce'          => wp_create_nonce('aicw_generate_nonce'),
			'validate_nonce' => wp_create_nonce('aicw_validate_nonce'),
		]);
	}

	// public function enqueue_frontend() { ... }

	/* ------------------------------------------------------------------
	 * Render callbacks – just load the template
	 * ------------------------------------------------------------------ */
	public function render_settings_page() {
		$api_key = aicw_get_decrypted_key('aicw_gemini_api_key');

		if (isset($_POST['aicw_save_settings']) && check_admin_referer('aicw_settings_action', 'aicw_settings_nonce')) {
			$raw = sanitize_text_field($_POST['aicw_gemini_api_key']);
			update_option('aicw_gemini_api_key', base64_encode($raw));
			echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'ai-content-writer') . '</p></div>';
			$api_key = $raw;
		}

		include AICW_PLUGIN_DIR . 'templates/settings-page.php';
	}

	public function render_generator_page() {
		$api_key = aicw_get_decrypted_key('aicw_gemini_api_key');
		include AICW_PLUGIN_DIR . 'templates/generator-page.php';
	}
}
