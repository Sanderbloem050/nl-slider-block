<?php
/**
 * Plugin Name: NL Slider Block
 * Description: Custom slider met 2 type slides.
 * Version: 1.5.9
 * Author: NothLabs
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) exit;

define('NLSB_DIR', plugin_dir_path(__FILE__));
define('NLSB_URL', plugin_dir_url(__FILE__));
define('NLSB_VER', '1.0.0');

require_once NLSB_DIR.'includes/class-nlsb-cpt.php';
require_once NLSB_DIR.'includes/admin-meta.php';
require_once NLSB_DIR.'includes/ajax.php';
require_once NLSB_DIR.'includes/render.php';

add_action('wp_enqueue_scripts', function () {
  wp_register_style ('nlsb-slider', NLSB_URL.'assets/css/slider.css', [], NLSB_VER);
  wp_register_script('nlsb-slider', NLSB_URL.'assets/js/slider.js', [], NLSB_VER, true);
});

add_action('admin_enqueue_scripts', function($hook){
  global $post;
  $is_slider = in_array($hook, ['post.php','post-new.php'], true) && isset($post->post_type) && $post->post_type==='nlsb_slider';
  $is_slide  = in_array($hook, ['post.php','post-new.php'], true) && isset($post->post_type) && $post->post_type==='nlsb_slide';

  if ($is_slider || $is_slide) {
    wp_enqueue_style('nlsb-admin', NLSB_URL.'assets/css/admin.css', [], NLSB_VER);
    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('nlsb-admin', NLSB_URL.'assets/js/admin.js', ['jquery','jquery-ui-sortable'], NLSB_VER, true);
    wp_localize_script('nlsb-admin', 'NLSB', [
      'ajax'  => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('nlsb_nonce'),
    ]);
  }
});
