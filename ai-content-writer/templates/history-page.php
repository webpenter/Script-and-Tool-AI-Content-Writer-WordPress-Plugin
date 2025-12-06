<?php
/**
 * AI Content Writer – History Page Template
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
  <h1><?php esc_html_e('Content History', 'ai-content-writer'); ?></h1>

  <table class="widefat striped">
    <thead>
      <tr>
        <th><?php esc_html_e('Date', 'ai-content-writer'); ?></th>
        <th><?php esc_html_e('Type', 'ai-content-writer'); ?></th>
        <th><?php esc_html_e('Tone', 'ai-content-writer'); ?></th>
        <th><?php esc_html_e('Language', 'ai-content-writer'); ?></th>
        <th><?php esc_html_e('Content', 'ai-content-writer'); ?></th>
        <th><?php esc_html_e('Actions', 'ai-content-writer'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (isset($rows) && !empty($rows)) : ?>
        <?php foreach ($rows as $row) : ?>
          <tr>
            <td><?php echo esc_html($row->created ?? '—'); ?></td>
            <td><?php echo esc_html($row->type ?? '—'); ?></td>
            <td><?php echo esc_html($row->tone ?? '—'); ?></td>
            <td><?php echo esc_html($row->language ?? '—'); ?></td>
            <td><?php echo esc_html(wp_trim_words($row->content ?? '', 20)); ?></td>
            <td>
              <button class="button aicw-reuse" data-id="<?php echo esc_attr($row->id ?? ''); ?>">
                <?php esc_html_e('Reuse', 'ai-content-writer'); ?>
              </button>
              <button class="button aicw-delete" data-id="<?php echo esc_attr($row->id ?? ''); ?>">
                <?php esc_html_e('Delete', 'ai-content-writer'); ?>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else : ?>
        <tr>
          <td colspan="6"><?php esc_html_e('No content history found.', 'ai-content-writer'); ?></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

