<div class="aicw-settings-wrap">
    <!-- Left: Main Settings -->
    <div class="aicw-settings-left">
        <div class="aicw-header">
            <h1>🚀 AI Content Writer</h1>
            <p class="description">
                Generate SEO-friendly blog posts and featured images instantly using Google Gemini AI.
            </p>
        </div>

        <div class="aicw-card aicw-info-card">
            <h3>🎯 Google Gemini API</h3>
            <p>
                Connect your Google Gemini API key to start generating high-quality AI content.
            </p>
            <p class="aicw-note">
                🔹 Free tier available (limited requests)<br>
                🔹 For production use, enabling billing is recommended
            </p>
            <a href="https://aistudio.google.com/app/api-keys" target="_blank" class="button button-secondary">
                🔑 Get API Key
            </a>
        </div>

        <div class="aicw-card aicw-settings-card">
            <h2>⚙️ API Settings</h2>

            <form method="post">
                <?php wp_nonce_field('aicw_settings_action', 'aicw_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th>
                            <label for="aicw_gemini_api_key">Gemini API Key</label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="aicw_gemini_api_key"
                                name="aicw_gemini_api_key"
                                value="<?php echo esc_attr($api_key); ?>"
                                class="regular-text"
                                placeholder="AIza..."
                            />
                            <p class="description">
                                Your API key is stored securely and never shared.
                            </p>

                            <div class="aicw-actions">
                                <button type="button" class="button button-secondary aicw-validate-key">
                                    ✅ Validate API Key
                                </button>
                                <div id="aicw_validation_results"></div>
                            </div>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('💾 Save Settings', 'ai-content-writer'), 'primary', 'aicw_save_settings'); ?>
            </form>
        </div>
    </div>

    <!-- Right: Documentation -->
    <div class="aicw-settings-right" style="position: relative;top: 100px;">
        <div class="aicw-card aicw-docs-card">
            <h2>📖 Quick Start Guide</h2>
            <p>Welcome to <strong>AI Content Writer</strong>. Here's how to use the plugin:</p>

            <ul>
                <li><strong>Get API Key:</strong> Click <em>Get API Key</em> to create a Google Gemini API key.</li>
                <li><strong>Enter Key:</strong> Paste your API key and click <em>Validate API Key</em>.</li>
                <li><strong>Save Settings:</strong> Once validated, click <em>Save Settings</em> to store your key.</li>
                <li><strong>Generate Content:</strong> Go to <em>Posts → Add New</em> to create AI-powered content.</li>
            </ul>

            <h3>💡 Key Features</h3>
            <ul>
                <li>Auto-generate full blog posts from keywords</li>
                <li>Create featured images with DALL·E 3</li>
                <li>Extract content using CSS/XPath/REGEX</li>
                <li>Duplicate content detection</li>
                <li>WooCommerce & Amazon integration</li>
                <li>SEO-optimized output</li>
            </ul>

            <h3>🛠 Support</h3>
            <p>
                For issues or feature requests, visit <a href="https://scriptandtools.com/" target="_blank">WebPenter</a>
            </p>
        </div>
    </div>
</div>