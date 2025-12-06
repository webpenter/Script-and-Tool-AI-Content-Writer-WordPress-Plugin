<?php
class AICW_Shortcode {
	public static function init() {
		add_shortcode('ai_content_writer', [__CLASS__, 'render']);
	}

	public static function render($atts) {
		if (!is_user_logged_in()) {
			return '<div class="aicw-login-required"><p>' . __('Please log in to use the AI Content Writer.', 'ai-content-writer') . '</p></div>';
		}
		ob_start();
		include AICW_PLUGIN_DIR . 'templates/generator-page.php';
		return ob_get_clean();
	}
}