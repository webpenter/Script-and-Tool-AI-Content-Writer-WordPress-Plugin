<div class="wrap aicw-settings-wrap">
	<h1><?php _e('AI Content Writer – API Settings', 'ai-content-writer'); ?></h1>

	<div class="aicw-api-info">
		<h3>🎯 Google Gemini API</h3>
		<p>Free tier available (60 requests per minute) –
		   get your key from <a href="https://aistudio.google.com/app/api-keys" target="_blank">Google AI Studio</a></p>
	</div>

	<form method="post">
		<?php wp_nonce_field('aicw_settings_action', 'aicw_settings_nonce'); ?>
		<table class="form-table">
			<tr>
				<th><label for="aicw_gemini_api_key"><?php _e('Gemini API Key', 'ai-content-writer'); ?></label></th>
				<td>
					<input type="password" id="aicw_gemini_api_key" name="aicw_gemini_api_key"
						   value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="AIza...">
					<button type="button" class="button aicw-validate-key"><?php _e('Validate API Key', 'ai-content-writer'); ?></button>
				</td>
			</tr>
		</table>
		<?php submit_button(__('Save Settings', 'ai-content-writer'), 'primary', 'aicw_save_settings'); ?>
	</form>
	<div id="aicw_validation_results"></div>
</div>