<?php
add_action('wp_ajax_aicw_export_txt', function () {
    check_ajax_referer('aicw_nonce', 'nonce');
    $content = wp_kses_post(stripslashes($_POST['content'] ?? ''));
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="content.txt"');
    echo $content;
    wp_die();
    });

// docx (super-light – just HTML wrapped)
add_action('wp_ajax_aicw_export_docx', function () {
    check_ajax_referer('aicw_nonce', 'nonce');
    $content = wp_kses_post(stripslashes($_POST['content'] ?? ''));
    $html = '<html><body>'.wpautop($content).'</body></html>';
    require_once AICW_PLUGIN_DIR.'libs/html-to-docx/vendor/autoload.php'; // composer require html-to-docx
    $docx = \HtmlToDocx\Create::createDocx($html);
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="content.docx"');
    echo $docx;
    wp_die();
});

// Save as draft post
add_action('wp_ajax_aicw_save_draft', function () {
    check_ajax_referer('aicw_nonce', 'nonce');
    $title   = sanitize_text_field($_POST['title'] ?? 'AI Generated Content');
    $content = wp_kses_post(stripslashes($_POST['content'] ?? ''));
    $post_id = wp_insert_post([
        'post_title'  => $title,
        'post_content'=> $content,
        'post_status' => 'draft',
        'post_author' => get_current_user_id(),
    ]);
    if ($post_id) wp_send_json_success(['edit_url'=>get_edit_post_link($post_id)]);
    wp_send_json_error(['message'=>__('Could not create draft.','ai-content-writer')]);
});