<?php
/**
 * Settings Page Template
 */

defined('ABSPATH') || exit;

$settings = WebPenter_ABM_Settings::get_settings();
$cron_info = WebPenter_ABM_Cron::get_cron_info();
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
?>

<div class="wrap abm-modern-wrap">
  <div class="abm-header-banner">
    <div class="abm-header-content">
      <div class="abm-header-title">
        <h1>✨ AI Blog Master</h1>
        <span class="abm-badge">v1.0.8</span>
      </div>
      <p class="abm-header-subtitle">Created by <strong>Fayyaz Ahmad</strong> @ <strong>WebPenter</strong></p>
    </div>
  </div>

  <?php settings_errors(); ?>

  <?php if (isset($_GET['generation_result'])): ?>
    <div class="abm-alert abm-alert-<?php echo esc_attr($_GET['generation_result'] === 'success' ? 'success' : 'error'); ?>">
      <div class="abm-alert-icon">
        <?php echo $_GET['generation_result'] === 'success' ? '✅' : '⚠️'; ?>
      </div>
      <div class="abm-alert-text">
        <?php echo esc_html(urldecode($_GET['generation_message'])); ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="abm-container">
    
    <!-- Tab Navigation -->
    <div class="abm-nav-tabs">
      <a href="?page=ai-blog-master-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-admin-settings"></span> General
      </a>
      <a href="?page=ai-blog-master-settings&tab=automation" class="nav-tab <?php echo $active_tab == 'automation' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-update-alt"></span> Automation
      </a>
      <a href="?page=ai-blog-master-settings&tab=content" class="nav-tab <?php echo $active_tab == 'content' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-welcome-write-blog"></span> Content Strategy
      </a>
      <a href="?page=ai-blog-master-logs" class="nav-tab">
        <span class="dashicons dashicons-clipboard"></span> Logs
      </a>
    </div>

    <form method="post" action="options.php" id="abm-settings-form">
      <?php settings_fields('webpenter_abm_settings_group'); ?>

      <!-- GENERAL SETTINGS TAB -->
      <div id="tab-general" class="abm-tab-content <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
        <div class="abm-card-grid">
          <div class="abm-card">
            <div class="abm-card-icon">🤖</div>
            <h3 class="abm-card-title">AI Provider</h3>
            <p class="abm-desc">Choose which AI service to generate content.</p>
            <div class="abm-field">
              <label>Provider</label>
              <div class="abm-select-wrapper">
                <select name="webpenter_abm_settings[ai_provider]" id="abm-ai-provider">
                  <option value="gemini" <?php selected($settings['ai_provider'], 'gemini'); ?>>Google Gemini</option>
                  <option value="groq" <?php selected($settings['ai_provider'], 'groq'); ?>>Groq (Free - Llama 3)</option>
                </select>
              </div>
            </div>
          </div>

          <div class="abm-card" id="abm-gemini-card">
            <div class="abm-card-icon">🗝️</div>
            <h3 class="abm-card-title">Google Gemini API</h3>
            <p class="abm-desc">Powers the AI content generation engine.</p>
            <div class="abm-field">
              <label>API Key</label>
              <div class="abm-password-field">
                <input type="password" name="webpenter_abm_settings[gemini_api_key]" value="<?php echo esc_attr($settings['gemini_api_key']); ?>" placeholder="Enter Gemini API Key...">
                <button type="button" class="abm-toggle-password">Show</button>
              </div>
              <div class="abm-test-wrapper" style="margin-top: 10px;">
                <button type="button" class="abm-test-api button" data-provider="gemini">Test Connection</button>
                <span class="abm-test-result"></span>
              </div>
              <p class="abm-help"><a href="https://aistudio.google.com/app/apikey" target="_blank">Get your free key here →</a></p>
            </div>
          </div>

          <div class="abm-card" id="abm-groq-card" style="<?php echo $settings['ai_provider'] !== 'groq' ? 'display:none;' : ''; ?>">
            <div class="abm-card-icon">⚡</div>
            <h3 class="abm-card-title">Groq API (Free - No Billing Needed)</h3>
            <p class="abm-desc">Free AI generation using Llama 3 (30 req/min, no credit card).</p>
            <div class="abm-field">
              <label>API Key</label>
              <div class="abm-password-field">
                <input type="password" name="webpenter_abm_settings[groq_api_key]" value="<?php echo esc_attr($settings['groq_api_key']); ?>" placeholder="Enter Groq API Key...">
                <button type="button" class="abm-toggle-password">Show</button>
              </div>
              <div class="abm-test-wrapper" style="margin-top: 10px;">
                <button type="button" class="abm-test-api button" data-provider="groq">Test Connection</button>
                <span class="abm-test-result"></span>
              </div>
              <p class="abm-help"><a href="https://console.groq.com" target="_blank">Get your free key here →</a></p>
            </div>
          </div>

          <div class="abm-card">
            <div class="abm-card-icon">🖼️</div>
            <h3 class="abm-card-title">Pixabay API (Images)</h3>
            <p class="abm-desc">Automatically fetches related royalty-free images.</p>
            <div class="abm-field">
              <label>API Key</label>
              <div class="abm-password-field">
                <input type="password" name="webpenter_abm_settings[pixabay_api_key]" value="<?php echo esc_attr($settings['pixabay_api_key']); ?>" placeholder="Enter Pixabay API Key...">
                <button type="button" class="abm-toggle-password">Show</button>
              </div>
              <div class="abm-test-wrapper" style="margin-top: 10px;">
                <button type="button" class="abm-test-api button" data-provider="pixabay">Test Connection</button>
                <span class="abm-test-result"></span>
              </div>
              <p class="abm-help"><a href="https://pixabay.com/api/docs/" target="_blank">Get your free key here →</a></p>
            </div>
          </div>
        </div>
      </div>

      <!-- AUTOMATION TAB -->
      <div id="tab-automation" class="abm-tab-content <?php echo $active_tab == 'automation' ? 'active' : ''; ?>">
        
        <div class="abm-limits-banner">
          <div class="abm-banner-content">
            <div class="abm-banner-header">Google Gemini Free Tier Guide</div>
            <div class="abm-banner-stats">
              <div class="abm-stat">
                <div class="abm-stat-value">15</div>
                <div class="abm-stat-label">Requests / Min</div>
              </div>
              <div class="abm-stat">
                <div class="abm-stat-value">1-2</div>
                <div class="abm-stat-label">Posts / Batch</div>
              </div>
              <div class="abm-stat">
                <div class="abm-stat-value">60s</div>
                <div class="abm-stat-label">Min Interval</div>
              </div>
            </div>
          </div>
          <div class="abm-banner-footer">💡 Pro Tip: For optimal performance with the free tier, generate 2 posts every hour.</div>
        </div>

        <div class="abm-card-grid-3">
          <div class="abm-card">
            <h3 class="abm-card-title">Automation Control</h3>
            <div class="abm-field">
              <label>Engine Status</label>
              
              <label class="abm-switch">
                <input type="checkbox" name="webpenter_abm_settings[automation_status]" value="enabled" <?php checked($settings['automation_status'], 'enabled'); ?>>
                <span class="abm-slider round"></span>
              </label>
              <span class="abm-switch-label"><?php echo $settings['automation_status'] == 'enabled' ? 'Enabled (Running)' : 'Disabled (Paused)'; ?></span>
              
              <p class="abm-desc" style="margin-top: 15px;">Toggle to start or stop automatic blog creation.</p>
            </div>
          </div>

          <div class="abm-card">
            <h3 class="abm-card-title">Content Destination</h3>
            <div class="abm-field">
              <label>Post Type</label>
              <div class="abm-select-wrapper">
                <select name="webpenter_abm_settings[post_type]">
                  <?php
                  $post_types = get_post_types(array('public' => true), 'objects');
                  foreach ($post_types as $pt) {
                    echo '<option value="' . esc_attr($pt->name) . '" ' . selected($settings['post_type'], $pt->name, false) . '>' . esc_html($pt->label) . ' (' . esc_html($pt->name) . ')</option>';
                  }
                  ?>
                </select>
              </div>
              <p class="abm-desc">Where should AI posts be published?</p>
            </div>
          </div>

          <div class="abm-card">
            <h3 class="abm-card-title">Batch Configuration</h3>
            <div class="abm-field">
              <label>Posts per Batch</label>
              <input type="number" name="webpenter_abm_settings[posts_per_batch]" value="<?php echo esc_attr($settings['posts_per_batch']); ?>" min="1" max="10">
              <p class="abm-desc">Number of posts to generate at once (Keep at 1-2 for free APIs).</p>
            </div>
          </div>
        </div>

        <div class="abm-card">
          <h3 class="abm-card-title">🕒 Schedule Configuration</h3>
          <div class="abm-schedule-flex">
            <div class="abm-field">
              <label>Posting Frequency</label>
              <div class="abm-select-wrapper">
                <select name="webpenter_abm_settings[schedule_frequency]">
                  <option value="custom" <?php selected($settings['schedule_frequency'], 'custom'); ?>>⚙️ Custom Schedule</option>
                  <option value="minutely" <?php selected($settings['schedule_frequency'], 'minutely'); ?>>Every Minute</option>
                  <option value="hourly" <?php selected($settings['schedule_frequency'], 'hourly'); ?>>Hourly</option>
                  <option value="daily" <?php selected($settings['schedule_frequency'], 'daily'); ?>>Daily</option>
                </select>
              </div>
            </div>
            <div class="abm-field">
              <label>Custom Interval (Seconds)</label>
              <div class="abm-interval-inputs">
                <input type="number" name="webpenter_abm_settings[custom_interval_seconds]" value="<?php echo esc_attr($settings['custom_interval_seconds']); ?>" min="1">
                <span class="abm-unit">sec</span>
              </div>
            </div>
          </div>
          <div class="abm-notice-red">⚠️ Note: Setting below 60 seconds may cause API rate-limit bans from Google.</div>
        </div>

        <div class="abm-card abm-run-card">
          <div class="abm-run-info">
            <h4>NEXT SCHEDULED RUN</h4>
            <?php if ($settings['automation_status'] == 'enabled' && $cron_info['scheduled']): ?>
              <div class="abm-status-badge success">
                <span class="dashicons dashicons-clock"></span> <?php echo esc_html($cron_info['next_run_formatted']); ?>
              </div>
            <?php else: ?>
              <div class="abm-status-badge error">
                <span class="dashicons dashicons-warning"></span> Not Scheduled (Paused)
              </div>
            <?php endif; ?>
          </div>
          <div class="abm-run-action">
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=webpenter_abm_generate_now'), 'webpenter_abm_generate_now')); ?>" class="abm-btn abm-btn-run">▶ Run Manual Batch Now</a>
          </div>
        </div>

      </div>

      <!-- CONTENT STRATEGY TAB -->
      <div id="tab-content" class="abm-tab-content <?php echo $active_tab == 'content' ? 'active' : ''; ?>">
        
        <div class="abm-card-grid">
          <div class="abm-card">
            <h3 class="abm-card-title">📝 Topics Pool</h3>
            <p class="abm-desc">Enter your blog post topics, one per line. The AI will randomly select one topic for every new post it generates.</p>
            <div class="abm-field">
              <textarea name="webpenter_abm_settings[topics]" rows="10" placeholder="Tech News\nWordPress Development\nAI Tools..."><?php echo esc_textarea($settings['topics']); ?></textarea>
            </div>
          </div>

          <div class="abm-card">
            <h3 class="abm-card-title">⚙️ Generation Rules</h3>
            
            <div class="abm-field">
              <label>Target Word Count</label>
              <div class="abm-interval-inputs" style="max-width: 200px;">
                <input type="number" name="webpenter_abm_settings[word_count]" value="<?php echo esc_attr($settings['word_count']); ?>" min="100" max="5000">
                <span class="abm-unit">words</span>
              </div>
              <p class="abm-desc">Recommended: 800 - 1500 words for optimal SEO performance.</p>
            </div>

            <hr class="abm-divider">

            <h3 class="abm-card-title">💰 Monetization (Affiliate)</h3>
            <div class="abm-field">
              <label>HTML / Ad Code (Optional)</label>
              <textarea name="webpenter_abm_settings[affiliate_code]" rows="4" placeholder="<!-- Your Adsense or Affiliate Banner Code Here -->"><?php echo esc_textarea($settings['affiliate_code']); ?></textarea>
              <p class="abm-desc">This code will automatically be injected at the bottom of every AI-generated post.</p>
            </div>
          </div>
        </div>

      </div>

      <div class="abm-save-wrapper">
        <button type="submit" class="abm-btn abm-btn-primary">
          <span class="dashicons dashicons-saved"></span> Save All Settings
        </button>
      </div>

    </form>
  </div>
</div>