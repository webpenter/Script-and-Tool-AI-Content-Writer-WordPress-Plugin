<?php
/**
 * Settings Page Template
 */

defined('ABSPATH') || exit;

$settings = WebPenter_ABA_Settings::get_settings();
$cron_info = WebPenter_ABA_Cron::get_cron_info();
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
?>

<div class="wrap aba-modern-wrap">
  <div class="aba-header-banner">
    <div class="aba-header-content">
      <div class="aba-header-title">
        <h1>✨ AI Blog Automator</h1>
        <span class="aba-badge">v99.9.9</span>
      </div>
      <p class="aba-header-subtitle">Created by <strong>Fayyaz Ahmad</strong> @ <strong>WebPenter</strong></p>
    </div>
  </div>

  <?php settings_errors(); ?>

  <?php if (isset($_GET['generation_result'])): ?>
    <div class="aba-alert aba-alert-<?php echo esc_attr($_GET['generation_result'] === 'success' ? 'success' : 'error'); ?>">
      <div class="aba-alert-icon">
        <?php echo $_GET['generation_result'] === 'success' ? '✅' : '⚠️'; ?>
      </div>
      <div class="aba-alert-text">
        <?php echo esc_html(urldecode($_GET['generation_message'])); ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="aba-container">
    
    <!-- Tab Navigation -->
    <div class="aba-nav-tabs">
      <a href="?page=ai-blog-automator-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-admin-settings"></span> General
      </a>
      <a href="?page=ai-blog-automator-settings&tab=automation" class="nav-tab <?php echo $active_tab == 'automation' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-update-alt"></span> Automation
      </a>
      <a href="?page=ai-blog-automator-settings&tab=content" class="nav-tab <?php echo $active_tab == 'content' ? 'active' : ''; ?>">
        <span class="dashicons dashicons-welcome-write-blog"></span> Content Strategy
      </a>
      <a href="?page=ai-blog-automator-logs" class="nav-tab">
        <span class="dashicons dashicons-clipboard"></span> Logs
      </a>
    </div>

    <form method="post" action="options.php" id="aba-settings-form">
      <?php settings_fields('webpenter_aba_settings_group'); ?>

      <!-- GENERAL SETTINGS TAB -->
      <div id="tab-general" class="aba-tab-content <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
        <div class="aba-card-grid">
          <div class="aba-card">
            <div class="aba-card-icon">🗝️</div>
            <h3 class="aba-card-title">Google Gemini API</h3>
            <p class="aba-desc">Powers the AI content generation engine.</p>
            <div class="aba-field">
              <label>API Key</label>
              <div class="aba-password-field">
                <input type="password" name="webpenter_aba_settings[gemini_api_key]" value="<?php echo esc_attr($settings['gemini_api_key']); ?>" placeholder="Enter Gemini API Key...">
                <button type="button" class="aba-toggle-password">Show</button>
              </div>
              <p class="aba-help"><a href="https://aistudio.google.com/app/apikey" target="_blank">Get your free key here →</a></p>
            </div>
          </div>

          <div class="aba-card">
            <div class="aba-card-icon">🖼️</div>
            <h3 class="aba-card-title">Pixabay API (Images)</h3>
            <p class="aba-desc">Automatically fetches related royalty-free images.</p>
            <div class="aba-field">
              <label>API Key</label>
              <div class="aba-password-field">
                <input type="password" name="webpenter_aba_settings[pixabay_api_key]" value="<?php echo esc_attr($settings['pixabay_api_key']); ?>" placeholder="Enter Pixabay API Key...">
                <button type="button" class="aba-toggle-password">Show</button>
              </div>
              <p class="aba-help"><a href="https://pixabay.com/api/docs/" target="_blank">Get your free key here →</a></p>
            </div>
          </div>
        </div>
      </div>

      <!-- AUTOMATION TAB -->
      <div id="tab-automation" class="aba-tab-content <?php echo $active_tab == 'automation' ? 'active' : ''; ?>">
        
        <div class="aba-limits-banner">
          <div class="aba-banner-content">
            <div class="aba-banner-header">Google Gemini Free Tier Guide</div>
            <div class="aba-banner-stats">
              <div class="aba-stat">
                <div class="aba-stat-value">15</div>
                <div class="aba-stat-label">Requests / Min</div>
              </div>
              <div class="aba-stat">
                <div class="aba-stat-value">1-2</div>
                <div class="aba-stat-label">Posts / Batch</div>
              </div>
              <div class="aba-stat">
                <div class="aba-stat-value">60s</div>
                <div class="aba-stat-label">Min Interval</div>
              </div>
            </div>
          </div>
          <div class="aba-banner-footer">💡 Pro Tip: For optimal performance with the free tier, generate 2 posts every hour.</div>
        </div>

        <div class="aba-card-grid-3">
          <div class="aba-card">
            <h3 class="aba-card-title">Automation Control</h3>
            <div class="aba-field">
              <label>Engine Status</label>
              
              <label class="aba-switch">
                <input type="checkbox" name="webpenter_aba_settings[automation_status]" value="enabled" <?php checked($settings['automation_status'], 'enabled'); ?>>
                <span class="aba-slider round"></span>
              </label>
              <span class="aba-switch-label"><?php echo $settings['automation_status'] == 'enabled' ? 'Enabled (Running)' : 'Disabled (Paused)'; ?></span>
              
              <p class="aba-desc" style="margin-top: 15px;">Toggle to start or stop automatic blog creation.</p>
            </div>
          </div>

          <div class="aba-card">
            <h3 class="aba-card-title">Content Destination</h3>
            <div class="aba-field">
              <label>Post Type</label>
              <div class="aba-select-wrapper">
                <select name="webpenter_aba_settings[post_type]">
                  <?php
                  $post_types = get_post_types(array('public' => true), 'objects');
                  foreach ($post_types as $pt) {
                    echo '<option value="' . esc_attr($pt->name) . '" ' . selected($settings['post_type'], $pt->name, false) . '>' . esc_html($pt->label) . ' (' . esc_html($pt->name) . ')</option>';
                  }
                  ?>
                </select>
              </div>
              <p class="aba-desc">Where should AI posts be published?</p>
            </div>
          </div>

          <div class="aba-card">
            <h3 class="aba-card-title">Batch Configuration</h3>
            <div class="aba-field">
              <label>Posts per Batch</label>
              <input type="number" name="webpenter_aba_settings[posts_per_batch]" value="<?php echo esc_attr($settings['posts_per_batch']); ?>" min="1" max="10">
              <p class="aba-desc">Number of posts to generate at once (Keep at 1-2 for free APIs).</p>
            </div>
          </div>
        </div>

        <div class="aba-card">
          <h3 class="aba-card-title">🕒 Schedule Configuration</h3>
          <div class="aba-schedule-flex">
            <div class="aba-field">
              <label>Posting Frequency</label>
              <div class="aba-select-wrapper">
                <select name="webpenter_aba_settings[schedule_frequency]">
                  <option value="custom" <?php selected($settings['schedule_frequency'], 'custom'); ?>>⚙️ Custom Schedule</option>
                  <option value="secondly" <?php selected($settings['schedule_frequency'], 'secondly'); ?>>Every Second</option>
                  <option value="minutely" <?php selected($settings['schedule_frequency'], 'minutely'); ?>>Every Minute</option>
                  <option value="hourly" <?php selected($settings['schedule_frequency'], 'hourly'); ?>>Hourly</option>
                  <option value="daily" <?php selected($settings['schedule_frequency'], 'daily'); ?>>Daily</option>
                </select>
              </div>
            </div>
            <div class="aba-field">
              <label>Custom Interval (Seconds)</label>
              <div class="aba-interval-inputs">
                <input type="number" name="webpenter_aba_settings[custom_interval_seconds]" value="<?php echo esc_attr($settings['custom_interval_seconds']); ?>" min="1">
                <span class="aba-unit">sec</span>
              </div>
            </div>
          </div>
          <div class="aba-notice-red">⚠️ Note: Setting below 60 seconds may cause API rate-limit bans from Google.</div>
        </div>

        <div class="aba-card aba-run-card">
          <div class="aba-run-info">
            <h4>NEXT SCHEDULED RUN</h4>
            <?php if ($settings['automation_status'] == 'enabled' && $cron_info['scheduled']): ?>
              <div class="aba-status-badge success">
                <span class="dashicons dashicons-clock"></span> <?php echo esc_html($cron_info['next_run_formatted']); ?>
              </div>
            <?php else: ?>
              <div class="aba-status-badge error">
                <span class="dashicons dashicons-warning"></span> Not Scheduled (Paused)
              </div>
            <?php endif; ?>
          </div>
          <div class="aba-run-action">
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=webpenter_aba_generate_now'), 'webpenter_aba_generate_now')); ?>" class="aba-btn aba-btn-run">▶ Run Manual Batch Now</a>
          </div>
        </div>

      </div>

      <!-- CONTENT STRATEGY TAB -->
      <div id="tab-content" class="aba-tab-content <?php echo $active_tab == 'content' ? 'active' : ''; ?>">
        
        <div class="aba-card-grid">
          <div class="aba-card">
            <h3 class="aba-card-title">📝 Topics Pool</h3>
            <p class="aba-desc">Enter your blog post topics, one per line. The AI will randomly select one topic for every new post it generates.</p>
            <div class="aba-field">
              <textarea name="webpenter_aba_settings[topics]" rows="10" placeholder="Tech News\nWordPress Development\nAI Tools..."><?php echo esc_textarea($settings['topics']); ?></textarea>
            </div>
          </div>

          <div class="aba-card">
            <h3 class="aba-card-title">⚙️ Generation Rules</h3>
            
            <div class="aba-field">
              <label>Target Word Count</label>
              <div class="aba-interval-inputs" style="max-width: 200px;">
                <input type="number" name="webpenter_aba_settings[word_count]" value="<?php echo esc_attr($settings['word_count']); ?>" min="100" max="5000">
                <span class="aba-unit">words</span>
              </div>
              <p class="aba-desc">Recommended: 800 - 1500 words for optimal SEO performance.</p>
            </div>

            <hr class="aba-divider">

            <h3 class="aba-card-title">💰 Monetization (Affiliate)</h3>
            <div class="aba-field">
              <label>HTML / Ad Code (Optional)</label>
              <textarea name="webpenter_aba_settings[affiliate_code]" rows="4" placeholder="<!-- Your Adsense or Affiliate Banner Code Here -->"><?php echo esc_textarea($settings['affiliate_code']); ?></textarea>
              <p class="aba-desc">This code will automatically be injected at the bottom of every AI-generated post.</p>
            </div>
          </div>
        </div>

      </div>

      <div class="aba-save-wrapper">
        <button type="submit" class="aba-btn aba-btn-primary">
          <span class="dashicons dashicons-saved"></span> Save All Settings
        </button>
      </div>

    </form>
  </div>
</div>