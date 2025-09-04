<?php
if (!defined('ABSPATH')) exit;

/** Add slide (AJAX) */
add_action('wp_ajax_nlsb_add_slide', function(){
  check_ajax_referer('nlsb_nonce','nonce');
  if (!current_user_can('edit_posts')) wp_send_json_error('forbidden');

  $slider = intval($_POST['slider'] ?? 0);
  $title  = sanitize_text_field($_POST['title'] ?? '');
  if (!$slider) wp_send_json_error('no slider');

  $id = wp_insert_post([
    'post_type'   => 'nlsb_slide',
    'post_title'  => $title ?: 'Nieuwe slide',
    'post_status' => 'draft',
    'menu_order'  => 0,
  ], true);
  if (is_wp_error($id)) wp_send_json_error($id->get_error_message());

  update_post_meta($id,'nlsb_parent',$slider);

  if (!function_exists('nlsb_builder_item_html')) require_once __DIR__.'/admin-meta.php';
  wp_send_json_success([
    'html' => nlsb_builder_item_html($id, get_the_title($id))
  ]);
});

/** Update order */
add_action('wp_ajax_nlsb_update_order', function(){
  check_ajax_referer('nlsb_nonce','nonce');
  if (!current_user_can('edit_posts')) wp_send_json_error('forbidden');
  $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
  foreach($ids as $i => $id){
    wp_update_post(['ID'=>$id, 'menu_order'=>$i]);
  }
  wp_send_json_success(true);
});

/** Delete slide */
add_action('wp_ajax_nlsb_delete_slide', function(){
  check_ajax_referer('nlsb_nonce','nonce');
  if (!current_user_can('delete_posts')) wp_send_json_error('forbidden');
  $id = intval($_POST['id'] ?? 0);
  if ($id) wp_delete_post($id, true);
  wp_send_json_success(true);
});

/** Quick: get slide data */
add_action('wp_ajax_nlsb_get_slide', function(){
  check_ajax_referer('nlsb_nonce','nonce');
  if (!current_user_can('edit_posts')) wp_send_json_error('forbidden');

  $id = intval($_POST['id'] ?? 0);
  if (!$id) wp_send_json_error('no id');
  $p = get_post($id);
  if (!$p || $p->post_type !== 'nlsb_slide') wp_send_json_error('not a slide');

  $thumb_id = get_post_thumbnail_id($id);
  $thumb_url= $thumb_id ? wp_get_attachment_image_url($thumb_id,'thumbnail') : '';

  wp_send_json_success([
    'title'   => get_the_title($id),
    'body'    => $p->post_content,
    'layout'  => get_post_meta($id,'_nlsb_layout', true) ?: 'a',
    'leftValue' => get_post_meta($id,'_nlsb_leftValue', true) ?: '600',
    'leftUnit'  => get_post_meta($id,'_nlsb_leftUnit',  true) ?: 'px',
    'caption'   => get_post_meta($id,'_nlsb_caption',   true) ?: '',
    'capHeightValue' => get_post_meta($id,'_nlsb_capHeightValue', true) ?? '',
    'capHeightUnit'  => get_post_meta($id,'_nlsb_capHeightUnit',  true) ?: 'px',
    'accent'   => get_post_meta($id,'_nlsb_accent', true) ?: '#ffeb00',
    'btnText'  => get_post_meta($id,'_nlsb_btnText', true) ?: '',
    'btnUrl'   => get_post_meta($id,'_nlsb_btnUrl',  true) ?: '',
    'thumbId'  => $thumb_id ?: 0,
    'thumbUrl' => $thumb_url ?: '',
  ]);
});

/** Quick: save slide data */
add_action('wp_ajax_nlsb_save_slide_quick', function(){
  check_ajax_referer('nlsb_nonce','nonce');
  if (!current_user_can('edit_posts')) wp_send_json_error('forbidden');

  $id = intval($_POST['id'] ?? 0);
  if (!$id) wp_send_json_error('no id');
  $p = get_post($id);
  if (!$p || $p->post_type !== 'nlsb_slide') wp_send_json_error('not a slide');

  $title  = sanitize_text_field($_POST['title'] ?? '');
  $body   = wp_kses_post($_POST['body'] ?? '');
  $layout = ($_POST['layout'] ?? 'a') === 'b' ? 'b' : 'a';
  $leftV  = preg_replace('/[^0-9.]/','', (string)($_POST['leftValue'] ?? '600'));
  $leftU  = in_array($_POST['leftUnit'] ?? 'px', ['px','%'], true) ? $_POST['leftUnit'] : 'px';
  $caption= sanitize_text_field($_POST['caption'] ?? '');
  $capV   = $_POST['capHeightValue'] ?? '';
  $capV   = ($capV === '' ? '' : preg_replace('/[^0-9.]/','', (string)$capV));
  $capU   = in_array($_POST['capHeightUnit'] ?? 'px', ['px','rem'], true) ? $_POST['capHeightUnit'] : 'px';
  $accent = sanitize_hex_color($_POST['accent'] ?? '#ffeb00');
  $btnTxt = sanitize_text_field($_POST['btnText'] ?? '');
  $btnUrl = esc_url_raw($_POST['btnUrl'] ?? '');
  $thumb  = intval($_POST['thumbId'] ?? 0);

  // Update post
  wp_update_post(['ID'=>$id, 'post_title'=>$title, 'post_content'=>$body]);

  // Update meta
  update_post_meta($id, '_nlsb_layout', $layout);
  update_post_meta($id, '_nlsb_leftValue', $leftV);
  update_post_meta($id, '_nlsb_leftUnit',  $leftU);
  update_post_meta($id, '_nlsb_caption',   $caption);
  update_post_meta($id, '_nlsb_capHeightValue', $capV);
  update_post_meta($id, '_nlsb_capHeightUnit',  $capU);
  update_post_meta($id, '_nlsb_accent',    $accent);
  update_post_meta($id, '_nlsb_btnText',   $btnTxt);
  update_post_meta($id, '_nlsb_btnUrl',    $btnUrl);

  // Thumbnail
  if ($thumb) {
    set_post_thumbnail($id, $thumb);
  } else {
    delete_post_thumbnail($id);
  }
  $thumb_url = $thumb ? wp_get_attachment_image_url($thumb,'thumbnail') : '';

  wp_send_json_success([
    'title'    => get_the_title($id),
    'thumbUrl' => $thumb_url ?: '',
  ]);
});
