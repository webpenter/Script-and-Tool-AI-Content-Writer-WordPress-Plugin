<?php

/**
 * Error Logs Page Template
 *
 * @package AI_Blog_Automator
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are scoped to this file

$recent_errors = WebPenter_ABA_Settings::get_recent_errors();
$error_count = count($recent_errors);
?>

<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

  <?php
  if (
    isset($_GET['errors_cleared'], $_GET['_wpnonce'])
    && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'webpenter_aba_errors_feedback')
  ): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php esc_html_e('Error log cleared.', 'ai-blog-automator'); ?></p>
    </div>
  <?php endif; ?>

  <p>
    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-blog-automator-settings')); ?>" class="button">
      <?php esc_html_e('&larr; Back to Settings', 'ai-blog-automator'); ?>
    </a>
  </p>

  <div class="ai-blog-automator-settings">
    <div class="ai-blog-automator-card ai-blog-automator-logs-card">
      <h2><?php esc_html_e('Recent Errors', 'ai-blog-automator'); ?></h2>

      <?php if ($error_count > 0): ?>
        <p class="description">
          <?php
          printf(
            /* translators: %d: number of stored error entries (max 10) */
            esc_html__('Showing the last %d error(s) from post generation and API requests.', 'ai-blog-automator'),
            absint($error_count)
          );
          ?>
        </p>

        <div class="ai-blog-automator-logs-list">
          <?php foreach (array_reverse($recent_errors) as $error): ?>
            <div class="ai-blog-automator-log-entry">
              <p class="ai-blog-automator-log-time">
                <strong><?php echo esc_html(date_i18n('F j, Y g:i a', strtotime($error['time']))); ?></strong>
              </p>
              <p class="ai-blog-automator-log-message"><?php echo esc_html($error['message']); ?></p>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="ai-blog-automator-logs-actions">
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="webpenter_aba_clear_errors">
            <?php wp_nonce_field('webpenter_aba_clear_errors'); ?>
            <?php submit_button(__('Clear log', 'ai-blog-automator'), 'delete', 'submit', false); ?>
          </form>
        </div>
      <?php else: ?>
        <p><?php esc_html_e('No errors logged. When generation fails, details will appear here.', 'ai-blog-automator'); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
