<?php

/**
 * Settings Page Template
 *
 * @package AI_Blog_Automator
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are scoped to this file

// Get current settings
$settings = Bluteem_ABA_Settings::get_settings();

// Get cron information
$cron_info = Bluteem_ABA_Cron::get_cron_info();

// Categories are now auto-assigned intelligently

// Handle generation result messages
$show_result = false;
$result_type = '';
$result_message = '';

// Verify nonce for redirect parameters
if (isset($_GET['generation_result'])) {
  // Verify nonce to ensure this is from our safe redirect
  if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bluteem_aba_generation_result')) {
    $show_result = true;
    $result_type = sanitize_text_field(wp_unslash($_GET['generation_result']));
    $result_message = isset($_GET['generation_message']) ? urldecode(sanitize_text_field(wp_unslash($_GET['generation_message']))) : '';
  }
}
?>

<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

  <?php if ($show_result): ?>
    <div class="notice notice-<?php echo esc_attr($result_type === 'success' ? 'success' : 'error'); ?> is-dismissible">
      <p><?php echo esc_html($result_message); ?></p>
      <?php
      // Double-check nonce for post_id - belt and suspenders security
      if ($result_type === 'success' && isset($_GET['post_id']) && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bluteem_aba_generation_result')): ?>
        <p>
          <a href="<?php echo esc_url(get_edit_post_link(absint(wp_unslash($_GET['post_id'])))); ?>" class="button">
            <?php esc_html_e('Edit Post', 'ai-blog-automator'); ?>
          </a>
        </p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php settings_errors(); ?>

  <?php
  $error_log_count = count(Bluteem_ABA_Settings::get_recent_errors());
  if ($error_log_count > 0):
    ?>
    <div class="notice notice-warning">
      <p>
        <?php
        echo esc_html(
          sprintf(
            /* translators: %d: number of logged errors */
            _n(
              '%d error logged.',
              '%d errors logged.',
              $error_log_count,
              'ai-blog-automator'
            ),
            $error_log_count
          )
        );
        ?>
        <a href="<?php echo esc_url(Bluteem_ABA_Settings::get_logs_page_url()); ?>">
          <?php esc_html_e('View Logs', 'ai-blog-automator'); ?>
        </a>
      </p>
    </div>
  <?php endif; ?>

  <!-- Free Tier Status -->
  <?php if (!Bluteem_ABA_Premium::is_active()): ?>
    <?php $posts_count = Bluteem_ABA_Generator::get_posts_count(); ?>

    <?php if (empty($settings['api_key'])): ?>
      <div class="notice notice-warning" style="background: #fff3cd; border-left: 4px solid #ffc107;">
        <h3>🔑 <?php esc_html_e('API Key Required', 'ai-blog-automator'); ?></h3>
        <p style="font-size: 16px;">
          <strong><?php esc_html_e('Add your free Groq API key below to start generating unlimited posts!', 'ai-blog-automator'); ?></strong>
        </p>
        <p>
          <?php esc_html_e('Free version includes unlimited posts with Groq AI. Upgrade to Pro for OpenAI, Claude, and advanced features.', 'ai-blog-automator'); ?>
        </p>
        <p>
          <a href="https://console.groq.com/" target="_blank" class="button button-primary">
            <?php esc_html_e('Get Free Groq API Key', 'ai-blog-automator'); ?>
          </a>
        </p>
        <p style="font-size: 12px; color: #666;">
          <strong><?php esc_html_e('How to get Groq API key:', 'ai-blog-automator'); ?></strong><br>
          1. <?php esc_html_e('Visit console.groq.com', 'ai-blog-automator'); ?><br>
          2. <?php esc_html_e('Sign up (free, no credit card required)', 'ai-blog-automator'); ?><br>
          3. <?php esc_html_e('Go to "API Keys" section', 'ai-blog-automator'); ?><br>
          4. <?php esc_html_e('Create new key and copy it', 'ai-blog-automator'); ?><br>
          5. <?php esc_html_e('Paste it in the "API Key" field below', 'ai-blog-automator'); ?>
        </p>
      </div>
    <?php else: ?>
      <div class="notice notice-success" style="background: #e7f8ed; border-left: 4px solid #46b450;">
        <h3>✅ <?php esc_html_e('Free Version Active', 'ai-blog-automator'); ?></h3>
        <p style="font-size: 16px;">
          <strong>
            <?php
            printf(
              /* translators: %d: Number of posts generated */
              esc_html__('Total posts generated: %d (Unlimited!)', 'ai-blog-automator'),
              absint($posts_count)
            );
            ?>
          </strong>
        </p>
        <p>
          <?php esc_html_e('Upgrade to Pro for OpenAI, Claude, custom AI endpoints, advanced scheduling, bulk generation, and more!', 'ai-blog-automator'); ?>
        </p>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="notice notice-success" style="background: #e7f8ed; border-left: 4px solid #46b450;">
      <h3>💎 <?php esc_html_e('Premium Active', 'ai-blog-automator'); ?></h3>
      <p style="font-size: 16px;">
        <strong><?php esc_html_e('Unlimited posts! All premium features enabled.', 'ai-blog-automator'); ?></strong>
      </p>
    </div>
  <?php endif; ?>

  <div class="ai-blog-automator-settings">

    <!-- Status Card -->
    <div class="ai-blog-automator-card">
      <h2><?php esc_html_e('Status', 'ai-blog-automator'); ?></h2>

      <div class="ai-blog-automator-stats">
        <p>
          <strong><?php esc_html_e('Total Posts Generated:', 'ai-blog-automator'); ?></strong>
          <?php echo esc_html(Bluteem_ABA_Generator::get_posts_count()); ?>
        </p>
        <p>
          <strong><?php esc_html_e('Last Run:', 'ai-blog-automator'); ?></strong>
          <?php
          if (!empty($settings['last_run'])) {
            echo esc_html(get_date_from_gmt($settings['last_run'], 'F j, Y g:i a'));
          } else {
            esc_html_e('Never', 'ai-blog-automator');
          }
          ?>
        </p>
        <p>
          <strong><?php esc_html_e('Cron Status:', 'ai-blog-automator'); ?></strong>
          <?php
          if ($cron_info['scheduled']) {
            echo '<span style="color: green;">✓ ' . esc_html__('Active', 'ai-blog-automator') . '</span>';
          } else {
            echo '<span style="color: red;">✗ ' . esc_html__('Not Scheduled', 'ai-blog-automator') . '</span>';
          }
          ?>
        </p>
        <p>
          <strong><?php esc_html_e('Next Scheduled Run:', 'ai-blog-automator'); ?></strong>
          <?php
          if ($cron_info['scheduled']) {
            echo esc_html($cron_info['next_run_formatted']);
            echo ' (' . esc_html($cron_info['frequency_description']) . ')';
          } else {
            esc_html_e('Not scheduled', 'ai-blog-automator');
          }
          ?>
        </p>
      </div>

      <form id="bluteem-aba-generate-form" class="bluteem-aba-generate-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="bluteem_aba_generate_now">
        <?php wp_nonce_field('bluteem_aba_generate_now'); ?>
        <?php submit_button(
          __('Generate Post Now', 'ai-blog-automator'),
          'secondary',
          'bluteem_aba_generate_submit',
          true,
          array(
            'id' => 'bluteem-aba-generate-now',
          )
        ); ?>
      </form>
    </div>

    <!-- Premium Features Banner -->
    <?php if (!Bluteem_ABA_Premium::is_active()): ?>
      <?php Bluteem_ABA_Premium::upgrade_notice('', 'banner'); ?>
    <?php endif; ?>

    <!-- Configuration Form -->
    <form method="post" action="options.php">
      <?php settings_fields('bluteem_aba_settings_group'); ?>

      <!-- API Configuration -->
      <div class="ai-blog-automator-card">
        <h2><?php esc_html_e('API Configuration', 'ai-blog-automator'); ?></h2>

        <?php if (!Bluteem_ABA_Premium::is_active()): ?>
          <!-- Free Version: Groq-Specific Configuration -->
          <div class="ai-blog-automator-field">
            <label>
              <?php esc_html_e('AI Provider', 'ai-blog-automator'); ?>
            </label>
            <p style="margin: 5px 0; font-size: 16px;">
              <strong><?php esc_html_e('Groq AI', 'ai-blog-automator'); ?></strong>
            </p>
            <p style="margin: 5px 0; color: #666; font-size: 13px;">
              <?php esc_html_e('Endpoint: https://api.groq.com/openai/v1/chat/completions', 'ai-blog-automator'); ?>
            </p>
            <span class="description">
              <?php esc_html_e('The free version is designed specifically for Groq AI, which offers unlimited free API access with no credit card required.', 'ai-blog-automator'); ?>
              <br><em><?php esc_html_e('Pro version supports multiple AI providers including OpenAI, Claude, and custom endpoints with a provider selection interface.', 'ai-blog-automator'); ?></em>
            </span>
            <!-- Hidden field to preserve Groq endpoint (not editable for free users) -->
            <input type="hidden" name="bluteem_aba_settings[api_endpoint]" value="https://api.groq.com/openai/v1/chat/completions" />
          </div>
        <?php endif; ?>

        <?php do_action('bluteem_aba_after_api_settings'); ?>

        <div class="ai-blog-automator-field">
          <label for="api_key">
            <?php esc_html_e('API Key', 'ai-blog-automator'); ?>
            <span style="color: #d63638; font-weight: normal;">
              (<?php esc_html_e('Required', 'ai-blog-automator'); ?>)
            </span>
          </label>
          <input
            type="password"
            id="api_key"
            name="bluteem_aba_settings[api_key]"
            value="<?php echo esc_attr($settings['api_key']); ?>"
            class="regular-text"
            placeholder="<?php echo esc_attr(Bluteem_ABA_Premium::is_active() ? 'Your AI API Key' : 'gsk_... (Get from console.groq.com)'); ?>"
            required />
          <span class="description">
            <strong style="color: #d63638;"><?php esc_html_e('⚠️ Required to generate posts!', 'ai-blog-automator'); ?></strong><br>
            <?php if (Bluteem_ABA_Premium::is_active()): ?>
              <?php esc_html_e('Get your API key from your selected AI provider above.', 'ai-blog-automator'); ?>
            <?php else: ?>
              <a href="https://console.groq.com/" target="_blank"><strong><?php esc_html_e('Get Free Groq API Key →', 'ai-blog-automator'); ?></strong></a>
              <?php esc_html_e('(Free, unlimited, no credit card required)', 'ai-blog-automator'); ?>
            <?php endif; ?>
          </span>
        </div>

        <div class="ai-blog-automator-field">
          <label for="unsplash_api_key">
            <?php esc_html_e('Unsplash API Key', 'ai-blog-automator'); ?>
            <span style="color: #646970; font-weight: normal;">
              (<?php esc_html_e('For featured images', 'ai-blog-automator'); ?>)
            </span>
          </label>
          <input
            type="password"
            id="unsplash_api_key"
            name="bluteem_aba_settings[unsplash_api_key]"
            value="<?php echo esc_attr($settings['unsplash_api_key']); ?>"
            class="regular-text"
            placeholder="<?php esc_attr_e('Your Unsplash Access Key', 'ai-blog-automator'); ?>" />
          <span class="description">
            <strong style="color: #d63638;"><?php esc_html_e('Not required, but needed for featured images to work.', 'ai-blog-automator'); ?></strong><br>
            <a href="https://unsplash.com/developers" target="_blank"><strong><?php esc_html_e('Get Free Unsplash API Key →', 'ai-blog-automator'); ?></strong></a>
            <?php esc_html_e('(Free, unlimited for demo apps)', 'ai-blog-automator'); ?><br>
            <small><?php esc_html_e('1. Create account at unsplash.com/developers', 'ai-blog-automator'); ?><br>
              <?php esc_html_e('2. Create "New Application"', 'ai-blog-automator'); ?><br>
              <?php esc_html_e('3. Copy "Access Key" and paste here', 'ai-blog-automator'); ?></small>
          </span>
        </div>
      </div>

      <!-- Content Configuration -->
      <div class="ai-blog-automator-card">
        <h2><?php esc_html_e('Content Configuration', 'ai-blog-automator'); ?></h2>

        <div class="ai-blog-automator-field">
          <label for="base_keywords">
            <?php esc_html_e('Base Keywords/Topics', 'ai-blog-automator'); ?>
            <span style="color: #d63638; font-weight: normal;">
              (<?php esc_html_e('Required', 'ai-blog-automator'); ?>)
            </span>
          </label>
          <textarea
            id="base_keywords"
            name="bluteem_aba_settings[base_keywords]"
            rows="3"
            placeholder="<?php esc_attr_e('WordPress tips, SEO strategies, Content marketing', 'ai-blog-automator'); ?>"
            required><?php echo esc_textarea($settings['base_keywords']); ?></textarea>
          <span class="description">
            <strong style="color: #d63638;"><?php esc_html_e('⚠️ Required to generate posts!', 'ai-blog-automator'); ?></strong><br>
            <?php esc_html_e('Comma-separated list of topics. The plugin will analyze recent posts and select the most different topic for each new post to ensure content variety.', 'ai-blog-automator'); ?>
          </span>
        </div>

        <div class="ai-blog-automator-field">
          <label for="post_length">
            <?php esc_html_e('Post Length', 'ai-blog-automator'); ?>
          </label>
          <select id="post_length" name="bluteem_aba_settings[post_length]">
            <option value="short" <?php selected($settings['post_length'], 'short'); ?>>
              <?php esc_html_e('Short (~500 words)', 'ai-blog-automator'); ?>
            </option>
            <option value="medium" <?php selected($settings['post_length'], 'medium'); ?>>
              <?php esc_html_e('Medium (~1000 words)', 'ai-blog-automator'); ?>
            </option>
            <option value="long" <?php selected($settings['post_length'], 'long'); ?>>
              <?php esc_html_e('Long (~2000 words)', 'ai-blog-automator'); ?>
            </option>
            <?php
            // Allow Pro to add more options via filter
            if (Bluteem_ABA_Premium::is_active()) {
              $length_options = apply_filters('bluteem_aba_post_length_options', array());
              foreach ($length_options as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['post_length'], $key); ?>>
                  <?php echo esc_html($label); ?>
                </option>
            <?php endforeach;
            }
            ?>
          </select>
          <span class="description">
            <?php esc_html_e('Target word count for generated posts.', 'ai-blog-automator'); ?>
            <?php if (!Bluteem_ABA_Premium::is_active()): ?>
              <br><em><?php esc_html_e('Pro version includes custom word count slider (300-5000 words) and 12+ content templates.', 'ai-blog-automator'); ?></em>
            <?php endif; ?>
          </span>
        </div>

        <?php do_action('bluteem_aba_after_post_length'); ?>

        <div class="ai-blog-automator-field">
          <label>
            <?php esc_html_e('Post Category', 'ai-blog-automator'); ?>
          </label>
          <p style="margin: 5px 0;">
            <strong><?php esc_html_e('Automatic (AI-selected)', 'ai-blog-automator'); ?></strong>
          </p>
          <span class="description">
            <?php esc_html_e('Posts are automatically assigned to the best matching category. If no good match exists, a new category is created based on the post topic.', 'ai-blog-automator'); ?>
            <?php if (Bluteem_ABA_Premium::is_active()): ?>
              <br><strong><?php esc_html_e('💎 Pro adds 2-3 additional relevant categories automatically', 'ai-blog-automator'); ?></strong>
            <?php endif; ?>
          </span>
        </div>
      </div>

      <!-- Publishing Configuration -->
      <div class="ai-blog-automator-card">
        <h2><?php esc_html_e('Publishing Configuration', 'ai-blog-automator'); ?></h2>

        <div class="ai-blog-automator-field">
          <label for="frequency">
            <?php esc_html_e('Post Frequency', 'ai-blog-automator'); ?>
          </label>
          <select id="frequency" name="bluteem_aba_settings[frequency]">
            <option value="daily" <?php selected($settings['frequency'], 'daily'); ?>>
              <?php esc_html_e('Daily (1 post per day)', 'ai-blog-automator'); ?>
            </option>
            <option value="twicedaily" <?php selected($settings['frequency'], 'twicedaily'); ?>>
              <?php esc_html_e('Twice Daily (2 posts per day)', 'ai-blog-automator'); ?>
            </option>
            <option value="weekly" <?php selected($settings['frequency'], 'weekly'); ?>>
              <?php esc_html_e('Weekly (1 post per week)', 'ai-blog-automator'); ?>
            </option>
            <?php
            // Allow Pro to add more options via filter
            if (Bluteem_ABA_Premium::is_active()) {
              $frequency_options = apply_filters('bluteem_aba_allowed_frequencies', array());
              foreach ($frequency_options as $freq): ?>
                <?php if (!in_array($freq, array('daily', 'twicedaily', 'weekly'))): ?>
                  <option value="<?php echo esc_attr($freq); ?>" <?php selected($settings['frequency'], $freq); ?>>
                    <?php
                    $freq_labels = array(
                      'hourly' => __('Every Hour (24 posts per day)', 'ai-blog-automator'),
                      'every_two_hours' => __('Every 2 Hours (12 posts per day)', 'ai-blog-automator'),
                      'every_three_hours' => __('Every 3 Hours (8 posts per day)', 'ai-blog-automator'),
                      'every_six_hours' => __('Every 6 Hours (4 posts per day)', 'ai-blog-automator'),
                    );
                    echo esc_html(isset($freq_labels[$freq]) ? $freq_labels[$freq] : ucfirst(str_replace('_', ' ', $freq)));
                    ?>
                  </option>
                <?php endif; ?>
            <?php endforeach;
            }
            ?>
          </select>
          <span class="description">
            <?php esc_html_e('How often to automatically generate new posts.', 'ai-blog-automator'); ?>
            <?php if (!Bluteem_ABA_Premium::is_active()): ?>
              <br><em><?php esc_html_e('Pro version includes additional scheduling options (hourly, custom intervals).', 'ai-blog-automator'); ?></em>
            <?php endif; ?>
          </span>
        </div>

        <div class="ai-blog-automator-field">
          <label for="auto_publish">
            <?php esc_html_e('Publishing Mode', 'ai-blog-automator'); ?>
          </label>
          <select id="auto_publish" name="bluteem_aba_settings[auto_publish]">
            <option value="publish" <?php selected($settings['auto_publish'], 'publish'); ?>>
              <?php esc_html_e('Publish Immediately (Recommended)', 'ai-blog-automator'); ?>
            </option>
            <option value="draft" <?php selected($settings['auto_publish'], 'draft'); ?>>
              <?php esc_html_e('Save as Draft for Review', 'ai-blog-automator'); ?>
            </option>
          </select>
          <span class="description">
            <?php esc_html_e('Publish immediately for full automation, or save as drafts if you want to review posts before publishing.', 'ai-blog-automator'); ?>
          </span>
        </div>
      </div>

      <!-- Prompt Template -->
      <?php if (Bluteem_ABA_Premium::is_active()): ?>
        <div class="ai-blog-automator-card">
          <h2><?php esc_html_e('Prompt Template', 'ai-blog-automator'); ?></h2>

          <div class="ai-blog-automator-field">
            <label for="prompt_template">
              <?php esc_html_e('Custom Prompt Template', 'ai-blog-automator'); ?>
            </label>
            <textarea
              id="prompt_template"
              name="bluteem_aba_settings[prompt_template]"><?php echo esc_textarea($settings['prompt_template']); ?></textarea>
            <span class="description">
              <?php esc_html_e('Customize the AI prompt. Available placeholders: {keyword}, {length}, {date}, {year}', 'ai-blog-automator'); ?>
            </span>
          </div>

          <?php do_action('bluteem_aba_after_prompt_template'); ?>

          <details style="margin-top: 10px;">
            <summary style="cursor: pointer; color: #2271b1;">
              <?php esc_html_e('View Default Template', 'ai-blog-automator'); ?>
            </summary>
            <pre style="background: #f0f0f0; padding: 10px; margin-top: 10px; overflow-x: auto;">Write a comprehensive blog post about {keyword}.

Length: {length} words

Structure:
- Engaging introduction with a hook
- 3-5 main sections with subheadings
- Each section should provide valuable, actionable information
- Conclusion with key takeaways

Tone: Informative, conversational, and SEO-friendly
Style: Human-like writing with natural flow
SEO: Include relevant keywords naturally throughout the content

Please format the output as follows:
Title: [Your engaging title here]

[Rest of the content]</pre>
          </details>
        </div>
      <?php else: ?>
        <!-- Free version: Informational note only -->
        <div class="ai-blog-automator-card">
          <h2><?php esc_html_e('Prompt Template', 'ai-blog-automator'); ?></h2>
          <div class="ai-blog-automator-field">
            <p style="margin: 10px 0; color: #666;">
              <?php esc_html_e('The plugin uses a pre-configured SEO-optimized prompt template for content generation.', 'ai-blog-automator'); ?>
            </p>
            <p style="margin: 10px 0; color: #666;">
              <em><?php esc_html_e('Note: It is possible to enter customized prompts in the Pro version, which includes a Custom Prompt Template Editor and 12+ ready-to-use templates for different content types.', 'ai-blog-automator'); ?></em>
            </p>
            <!-- Hidden field to preserve prompt template value when saving -->
            <input type="hidden" name="bluteem_aba_settings[prompt_template]" value="<?php echo esc_attr($settings['prompt_template']); ?>" />
          </div>
        </div>
      <?php endif; ?>

      <!-- SEO Settings (Pro) -->
      <?php if (Bluteem_ABA_Premium::is_active()): ?>
        <div class="ai-blog-automator-card">
          <h2><?php esc_html_e('SEO Optimization', 'ai-blog-automator'); ?></h2>
          <?php do_action('bluteem_aba_after_seo_settings'); ?>
        </div>
      <?php endif; ?>

      <?php submit_button(__('Save Settings', 'ai-blog-automator'), 'primary'); ?>
    </form>

    <!-- External Service Notice -->
    <div class="ai-blog-automator-card ai-blog-automator-external-services">
      <h2><?php esc_html_e('⚠️ External Services Used', 'ai-blog-automator'); ?></h2>
      <p>
        <strong><?php esc_html_e('This plugin uses external services to generate content and images:', 'ai-blog-automator'); ?></strong>
      </p>

      <h4><?php esc_html_e('1. AI Content Generation', 'ai-blog-automator'); ?></h4>
      <p>
        <?php if (Bluteem_ABA_Premium::is_active()): ?>
          <?php esc_html_e('Content generation requests (including your keywords and prompts) are sent to your configured AI API endpoint using your API key.', 'ai-blog-automator'); ?>
        <?php else: ?>
          <?php esc_html_e('Content generation requests (including your keywords and prompts) are sent to Groq AI using your Groq API key.', 'ai-blog-automator'); ?>
        <?php endif; ?>
      </p>

      <?php if (Bluteem_ABA_Premium::is_active() && strpos($settings['api_endpoint'], 'groq.com') === false): ?>
        <ul style="list-style: disc; margin-left: 20px;">
          <li><?php esc_html_e('Service: OpenAI (https://openai.com)', 'ai-blog-automator'); ?></li>
          <li><?php esc_html_e('API Endpoint: https://api.openai.com/v1/chat/completions', 'ai-blog-automator'); ?></li>
          <li><a href="https://openai.com/policies/privacy-policy" target="_blank"><?php esc_html_e('OpenAI Privacy Policy', 'ai-blog-automator'); ?></a></li>
          <li><a href="https://openai.com/policies/terms-of-use" target="_blank"><?php esc_html_e('OpenAI Terms of Service', 'ai-blog-automator'); ?></a></li>
        </ul>
      <?php else: ?>
        <ul style="list-style: disc; margin-left: 20px;">
          <li><?php esc_html_e('Service: Groq (https://groq.com)', 'ai-blog-automator'); ?></li>
          <li><?php esc_html_e('API Endpoint: https://api.groq.com/openai/v1/chat/completions', 'ai-blog-automator'); ?></li>
          <li><a href="https://groq.com/privacy-policy/" target="_blank"><?php esc_html_e('Groq Privacy Policy', 'ai-blog-automator'); ?></a></li>
          <li><a href="https://groq.com/terms-of-use/" target="_blank"><?php esc_html_e('Groq Terms of Service', 'ai-blog-automator'); ?></a></li>
        </ul>
      <?php endif; ?>

      <h4><?php esc_html_e('2. Featured Images (Unsplash)', 'ai-blog-automator'); ?></h4>
      <p>
        <?php esc_html_e('Featured images are automatically fetched from Unsplash based on your post keywords. Only the keyword is sent to Unsplash.', 'ai-blog-automator'); ?>
      </p>
      <ul style="list-style: disc; margin-left: 20px;">
        <li><?php esc_html_e('Service: Unsplash (https://unsplash.com)', 'ai-blog-automator'); ?></li>
        <li><?php esc_html_e('Requires: Free Unsplash API key from https://unsplash.com/developers', 'ai-blog-automator'); ?></li>
        <li><?php esc_html_e('API Endpoint: https://api.unsplash.com/photos/random', 'ai-blog-automator'); ?></li>
        <li><a href="https://unsplash.com/privacy" target="_blank"><?php esc_html_e('Unsplash Privacy Policy', 'ai-blog-automator'); ?></a></li>
        <li><a href="https://unsplash.com/terms" target="_blank"><?php esc_html_e('Unsplash Terms of Service', 'ai-blog-automator'); ?></a></li>
        <li><?php esc_html_e('Attribution: Photos are automatically attributed to photographers (Unsplash requirement)', 'ai-blog-automator'); ?></li>
      </ul>

      <p>
        <strong><?php esc_html_e('Important:', 'ai-blog-automator'); ?></strong>
        <?php esc_html_e('You are responsible for ensuring compliance with all external services\' terms. The plugin does not collect or transmit any personal data.', 'ai-blog-automator'); ?>
      </p>
    </div>

    <!-- Premium Features Tab -->
    <?php if (!Bluteem_ABA_Premium::is_active()): ?>
      <?php Bluteem_ABA_Premium::render_premium_tab(); ?>
    <?php endif; ?>

    <!-- Help Section -->
    <div class="ai-blog-automator-card">
      <h2><?php esc_html_e('Help & Documentation', 'ai-blog-automator'); ?></h2>
      <h3><?php esc_html_e('Getting Started', 'ai-blog-automator'); ?></h3>
      <ol>
        <li><?php esc_html_e('Get your free Groq API key from console.groq.com (no credit card required)', 'ai-blog-automator'); ?></li>
        <li><?php esc_html_e('Get your free Unsplash API key from unsplash.com/developers', 'ai-blog-automator'); ?></li>
        <li><?php esc_html_e('Enter both API keys in the fields above', 'ai-blog-automator'); ?></li>
        <li><?php esc_html_e('Add your desired topics/keywords (comma-separated) - REQUIRED', 'ai-blog-automator'); ?></li>
        <li><?php esc_html_e('Configure post frequency and publishing mode', 'ai-blog-automator'); ?></li>
        <li><?php esc_html_e('Save settings and test with "Generate Post Now" button', 'ai-blog-automator'); ?></li>
      </ol>

      <h3><?php esc_html_e('API Requirements', 'ai-blog-automator'); ?></h3>
      <p>
        <?php esc_html_e('The free version uses Groq AI exclusively. You\'ll need:', 'ai-blog-automator'); ?>
      </p>
      <ul>
        <li><?php esc_html_e('Free Groq API key from console.groq.com (no credit card required)', 'ai-blog-automator'); ?></li>
        <li><?php esc_html_e('Unlimited posts with fast AI-powered content generation', 'ai-blog-automator'); ?></li>
      </ul>
      <?php if (!Bluteem_ABA_Premium::is_active()): ?>
        <p>
          <em><?php esc_html_e('Want to use OpenAI, Claude, or custom AI endpoints? Upgrade to Pro!', 'ai-blog-automator'); ?></em>
        </p>
      <?php endif; ?>

      <h3><?php esc_html_e('Troubleshooting', 'ai-blog-automator'); ?></h3>
      <ul>
        <li><strong><?php esc_html_e('Posts not generating?', 'ai-blog-automator'); ?></strong> <?php esc_html_e('Check that WP-Cron is working on your site.', 'ai-blog-automator'); ?></li>
        <li><strong><?php esc_html_e('API errors?', 'ai-blog-automator'); ?></strong> <?php esc_html_e('Verify your Groq API key is valid and active.', 'ai-blog-automator'); ?></li>
        <li><strong><?php esc_html_e('Need custom intervals?', 'ai-blog-automator'); ?></strong> <?php esc_html_e('Upgrade to Pro for flexible scheduling (2, 3, 6 hours, etc.).', 'ai-blog-automator'); ?></li>
      </ul>
    </div>
  </div>
</div>