<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'ai-content-writer',
        __('Content History','ai-content-writer'),
        __('History','ai-content-writer'),
        'manage_options',
        'ai-content-history',
        'aicw_render_history_page'
    );
});

function aicw_render_history_page() {
    global $wpdb;
    $table = $wpdb->prefix.'aicw_history';
    $rows  = $wpdb->get_results("SELECT * FROM $table ORDER BY created DESC LIMIT 500");
    include AICW_PLUGIN_DIR.'templates/history-page.php';
}