<?php

/**
 * Premium Features Checker
 *
 * Handles premium feature detection and upgrade prompts
 *
 * @package AI_Blog_Automator
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Premium Features Class
 */
class WebPenter_ABA_Premium
{

  /**
   * Check if premium version is active
   *
   * @return bool
   */
  public static function is_active()
  {
    return class_exists('WebPenter_ABA_Pro') &&
      apply_filters('webpenter_aba_pro_is_active', false);
  }

  /**
   * Check if specific premium feature is available
   *
   * @param string $feature Feature name
   * @return bool
   */
  public static function has_feature($feature)
  {
    if (!self::is_active()) {
      return false;
    }

    $available_features = apply_filters('webpenter_aba_pro_features', array());
    return in_array($feature, $available_features, true);
  }

  /**
   * Get upgrade URL
   *
   * @param string $source Source identifier for tracking
   * @return string
   */
  public static function get_upgrade_url($source = 'general')
  {
    $url = 'https://webpenter.com/ai-blog-automator/';

    // Add UTM parameters for tracking
    $url = add_query_arg(array(
      'utm_source' => 'plugin',
      'utm_medium' => $source,
      'utm_campaign' => 'upgrade'
    ), $url);

    return apply_filters('webpenter_aba_upgrade_url', $url, $source);
  }

  /**
   * Get premium features list
   *
   * @return array
   */
  public static function get_premium_features()
  {
    return array(
      'ai_providers' => array(
        'title' => __('Multiple AI Providers', 'ai-blog-automator'),
        'description' => __('Choose OpenAI GPT-4, Claude, or custom AI endpoints', 'ai-blog-automator'),
        'icon' => 'dashicons-cloud'
      ),
      'custom_lengths' => array(
        'title' => __('Custom Post Lengths', 'ai-blog-automator'),
        'description' => __('Set any word count from 300 to 5000 words with slider', 'ai-blog-automator'),
        'icon' => 'dashicons-edit'
      ),
      'custom_schedules' => array(
        'title' => __('Custom Schedules', 'ai-blog-automator'),
        'description' => __('Post every 2, 3, 6 hours or custom intervals', 'ai-blog-automator'),
        'icon' => 'dashicons-clock'
      ),
      'multiple_categories' => array(
        'title' => __('Multiple Categories & Tags', 'ai-blog-automator'),
        'description' => __('Auto-assign multiple relevant categories and generate tags', 'ai-blog-automator'),
        'icon' => 'dashicons-category'
      ),
      'template_library' => array(
        'title' => __('12+ Prompt Templates', 'ai-blog-automator'),
        'description' => __('How-To, Listicle, Case Study, and more formats', 'ai-blog-automator'),
        'icon' => 'dashicons-media-document'
      ),
      'custom_prompt' => array(
        'title' => __('Custom Prompt Template Editor', 'ai-blog-automator'),
        'description' => __('Fully customize AI prompts to match your content style and requirements', 'ai-blog-automator'),
        'icon' => 'dashicons-edit'
      ),
      'seo_optimization' => array(
        'title' => __('SEO Optimization', 'ai-blog-automator'),
        'description' => __('Auto-generate meta descriptions for Yoast, Rank Math, AIOSEO', 'ai-blog-automator'),
        'icon' => 'dashicons-search'
      ),
      'bulk_generation' => array(
        'title' => __('Bulk Post Generation', 'ai-blog-automator'),
        'description' => __('Generate multiple posts at once from keyword list', 'ai-blog-automator'),
        'icon' => 'dashicons-admin-page'
      )
    );
  }

  /**
   * Display upgrade notice
   *
   * @param string $feature Feature name
   * @param string $context Context (settings, modal, inline)
   */
  public static function upgrade_notice($feature = '', $context = 'inline')
  {
    if (self::is_active()) {
      return;
    }

    $features = self::get_premium_features();
    $feature_info = isset($features[$feature]) ? $features[$feature] : null;

    switch ($context) {
      case 'modal':
        self::render_upgrade_modal($feature_info);
        break;

      case 'banner':
        self::render_upgrade_banner();
        break;

      default:
        self::render_upgrade_inline($feature_info);
        break;
    }
  }

  /**
   * Render inline upgrade notice
   *
   * @param array|null $feature_info Feature information
   */
  private static function render_upgrade_inline($feature_info)
  {
?>
    <div class="ai-blog-automator-upgrade-notice inline">
      <span class="dashicons dashicons-lock"></span>
      <?php if ($feature_info): ?>
        <strong><?php echo esc_html($feature_info['title']); ?></strong> -
        <?php echo esc_html($feature_info['description']); ?>
      <?php else: ?>
        <?php esc_html_e('This is a premium feature', 'ai-blog-automator'); ?>
      <?php endif; ?>
      <a href="<?php echo esc_url(self::get_upgrade_url('inline_' . ($feature_info ? $feature_info['title'] : 'general'))); ?>"
        class="button button-primary button-small"
        target="_blank">
        <?php esc_html_e('Upgrade to Pro', 'ai-blog-automator'); ?>
      </a>
    </div>
  <?php
  }

  /**
   * Render upgrade banner for settings page
   */
  private static function render_upgrade_banner()
  {
  ?>
    <div class="ai-blog-automator-upgrade-banner">
      <div class="upgrade-banner-content">
        <div class="upgrade-banner-icon">
          <span class="dashicons dashicons-star-filled"></span>
        </div>
        <div class="upgrade-banner-text">
          <h3><?php esc_html_e('Unlock More Powerful Features', 'ai-blog-automator'); ?></h3>
          <p><?php esc_html_e('Upgrade to Pro for multiple AI providers, bulk generation, and more!', 'ai-blog-automator'); ?></p>
        </div>
        <div class="upgrade-banner-action">
          <a href="<?php echo esc_url(self::get_upgrade_url('banner')); ?>"
            class="button button-primary button-hero"
            target="_blank">
            <?php esc_html_e('View Pro Features', 'ai-blog-automator'); ?>
          </a>
        </div>
      </div>
    </div>
  <?php
  }

  /**
   * Render upgrade modal
   *
   * @param array|null $feature_info Feature information
   */
  private static function render_upgrade_modal($feature_info)
  {
  ?>
    <div class="ai-blog-automator-upgrade-modal" style="display:none;">
      <div class="upgrade-modal-content">
        <span class="upgrade-modal-close">&times;</span>
        <div class="upgrade-modal-header">
          <span class="dashicons dashicons-unlock"></span>
          <h2><?php esc_html_e('Upgrade to AI Blog Automator Pro', 'ai-blog-automator'); ?></h2>
        </div>

        <?php if ($feature_info): ?>
          <div class="upgrade-modal-feature">
            <span class="dashicons <?php echo esc_attr($feature_info['icon']); ?>"></span>
            <h3><?php echo esc_html($feature_info['title']); ?></h3>
            <p><?php echo esc_html($feature_info['description']); ?></p>
          </div>
        <?php endif; ?>

        <div class="upgrade-modal-features">
          <h4><?php esc_html_e('Premium Features Include:', 'ai-blog-automator'); ?></h4>
          <ul>
            <?php foreach (self::get_premium_features() as $feature): ?>
              <li>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php echo esc_html($feature['title']); ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="upgrade-modal-cta">
          <a href="<?php echo esc_url(self::get_upgrade_url('modal')); ?>"
            class="button button-primary button-hero"
            target="_blank">
            <?php esc_html_e('Upgrade Now', 'ai-blog-automator'); ?>
          </a>
          <p class="upgrade-modal-guarantee">
            <?php esc_html_e('30-day money-back guarantee', 'ai-blog-automator'); ?>
          </p>
        </div>
      </div>
    </div>
  <?php
  }

  /**
   * Add premium tab to settings page
   */
  public static function add_premium_tab()
  {
    if (self::is_active()) {
      return;
    }

    add_action('webpenter_aba_settings_tabs', array(__CLASS__, 'render_premium_tab'));
  }

  /**
   * Render premium features tab
   */
  public static function render_premium_tab()
  {
  ?>
    <div class="ai-blog-automator-card premium-features-card">
      <h2>
        <span class="dashicons dashicons-star-filled" style="color: #FFD700;"></span>
        <?php esc_html_e('Premium Features', 'ai-blog-automator'); ?>
      </h2>

      <div class="premium-features-grid">
        <?php foreach (self::get_premium_features() as $key => $feature): ?>
          <div class="premium-feature-box">
            <div class="premium-feature-icon">
              <span class="dashicons <?php echo esc_attr($feature['icon']); ?>"></span>
            </div>
            <h3><?php echo esc_html($feature['title']); ?></h3>
            <p><?php echo esc_html($feature['description']); ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="premium-features-cta">
        <h3><?php esc_html_e('Ready to unlock these features?', 'ai-blog-automator'); ?></h3>
        <p><?php esc_html_e('Get instant access to all premium features with our Pro version.', 'ai-blog-automator'); ?></p>

        <div class="premium-pricing">
          <div class="price-box">
            <div class="price-label"><?php esc_html_e('Personal', 'ai-blog-automator'); ?></div>
            <div class="price-amount">$49<span> Lifetime</span></div>
            <div class="price-sites"><?php esc_html_e('1 Site', 'ai-blog-automator'); ?></div>
          </div>
          <div class="price-box featured">
            <div class="price-badge"><?php esc_html_e('Most Popular', 'ai-blog-automator'); ?></div>
            <div class="price-label"><?php esc_html_e('Professional', 'ai-blog-automator'); ?></div>
            <div class="price-amount">$99<span> Lifetime</span></div>
            <div class="price-sites"><?php esc_html_e('5 Sites', 'ai-blog-automator'); ?></div>
          </div>
          <div class="price-box">
            <div class="price-label"><?php esc_html_e('Agency', 'ai-blog-automator'); ?></div>
            <div class="price-amount">$199<span> Lifetime</span></div>
            <div class="price-sites"><?php esc_html_e('Unlimited Sites', 'ai-blog-automator'); ?></div>
          </div>
        </div>

        <p style="text-align: center; margin-top: 30px;">
          <a href="<?php echo esc_url(self::get_upgrade_url('pricing_tab')); ?>"
            class="button button-primary button-hero"
            target="_blank">
            <?php esc_html_e('View All Plans & Pricing', 'ai-blog-automator'); ?>
          </a>
        </p>

        <p style="text-align: center; color: #666; margin-top: 15px;">
          <small>
            <?php esc_html_e('✓ 30-day money-back guarantee', 'ai-blog-automator'); ?> •
            <?php esc_html_e('✓ Instant activation', 'ai-blog-automator'); ?> •
            <?php esc_html_e('✓ Regular updates', 'ai-blog-automator'); ?>
          </small>
        </p>
      </div>
    </div>
<?php
  }

  /**
   * Add CSS for premium notices
   */
  public static function enqueue_premium_styles()
  {
    wp_add_inline_style('wp-admin', '
            .ai-blog-automator-upgrade-notice {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 12px;
                margin: 15px 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .ai-blog-automator-upgrade-notice .dashicons {
                color: #856404;
            }
            .ai-blog-automator-upgrade-notice .button {
                margin-left: auto;
            }
            
            .ai-blog-automator-upgrade-banner {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .upgrade-banner-content {
                display: flex;
                align-items: center;
                gap: 20px;
            }
            .upgrade-banner-icon .dashicons {
                font-size: 60px;
                width: 60px;
                height: 60px;
                color: #FFD700;
            }
            .upgrade-banner-text h3 {
                color: white;
                margin: 0 0 10px 0;
            }
            .upgrade-banner-text p {
                margin: 0;
                opacity: 0.9;
            }
            .upgrade-banner-action .button-hero {
                background: white !important;
                border-color: white !important;
                color: #667eea !important;
                font-weight: 600;
            }
            
            .premium-features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            .premium-feature-box {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                transition: transform 0.2s;
            }
            .premium-feature-box:hover {
                transform: translateY(-5px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            .premium-feature-icon .dashicons {
                font-size: 48px;
                width: 48px;
                height: 48px;
                color: #667eea;
            }
            .premium-feature-box h3 {
                margin: 15px 0 10px 0;
                font-size: 16px;
            }
            .premium-feature-box p {
                color: #666;
                font-size: 14px;
                margin: 0;
            }
            
            .premium-pricing {
                display: flex;
                justify-content: center;
                gap: 20px;
                margin: 30px 0;
            }
            .price-box {
                background: white;
                border: 2px solid #ddd;
                border-radius: 8px;
                padding: 30px;
                text-align: center;
                min-width: 200px;
                position: relative;
            }
            .price-box.featured {
                border-color: #667eea;
                transform: scale(1.05);
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
            }
            .price-badge {
                position: absolute;
                top: -12px;
                left: 50%;
                transform: translateX(-50%);
                background: #667eea;
                color: white;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
            }
            .price-label {
                font-weight: 600;
                font-size: 18px;
                margin-bottom: 15px;
            }
            .price-amount {
                font-size: 36px;
                font-weight: 700;
                color: #667eea;
            }
            .price-amount span {
                font-size: 16px;
                color: #666;
            }
            .price-sites {
                margin-top: 10px;
                color: #666;
            }
        ');
  }
}
