<?php
/**
 * Plugin Name: NL Slider Block
 * Description: Slider gekoppeld aan CPT Projects.
 * Version: 1.6.4
 * Author: NothLabs
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) exit;

define('NLSB_DIR', plugin_dir_path(__FILE__));
define('NLSB_URL', plugin_dir_url(__FILE__));
define('NLSB_VER', '1.6.0');

require_once NLSB_DIR.'includes/project-meta.php';
require_once NLSB_DIR.'includes/render.php';

add_action('wp_enqueue_scripts', function () {
  wp_register_style('nlsb-slider', NLSB_URL.'assets/css/slider.css', [], NLSB_VER);
  wp_register_script('nlsb-slider', NLSB_URL.'assets/js/slider.js', [], NLSB_VER, true);
});

add_action('admin_enqueue_scripts', function () {
  $screen = get_current_screen();
  if (!$screen || $screen->post_type !== 'projects') {
    return;
  }

  wp_enqueue_style('nlsb-admin', NLSB_URL.'assets/css/admin.css', [], NLSB_VER);
  wp_enqueue_media();
  wp_enqueue_script('jquery-ui-sortable');
  wp_enqueue_script('nlsb-admin', NLSB_URL.'assets/js/admin.js', ['jquery', 'jquery-ui-sortable'], NLSB_VER, true);
  wp_localize_script('nlsb-admin', 'NLSBProject', [
    'chooseImage'    => __('Kies afbeelding', 'nlsb'),
    'replaceImage'   => __('Wijzig afbeelding', 'nlsb'),
    'removeImage'    => __('Verwijderen', 'nlsb'),
    'confirmDelete'  => __('Weet je zeker dat je deze slide wilt verwijderen?', 'nlsb'),
    'slideLabel'     => __('Slide', 'nlsb'),
    'noImage'        => __('Nog geen afbeelding geselecteerd', 'nlsb'),
    'expand'         => __('Uitklappen', 'nlsb'),
    'collapse'       => __('Inklappen', 'nlsb'),
  ]);
});


