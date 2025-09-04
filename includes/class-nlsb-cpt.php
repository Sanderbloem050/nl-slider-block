<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
  // Sliders
  register_post_type('nlsb_slider', [
    'labels' => [
      'name'          => 'Sliders',
      'singular_name' => 'Slider',
      'add_new'       => 'Nieuwe slider',
      'add_new_item'  => 'Nieuwe slider',
      'edit_item'     => 'Bewerk slider',
      'menu_name'     => 'NL Sliders',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => true,
    'menu_icon'    => 'dashicons-images-alt2',
    'supports'     => ['title'],
  ]);

  // Slides
  register_post_type('nlsb_slide', [
    'labels' => [
      'name'          => 'Slides',
      'singular_name' => 'Slide',
      'add_new'       => 'Nieuwe slide',
      'add_new_item'  => 'Nieuwe slide',
      'edit_item'     => 'Bewerk slide',
      'menu_name'     => 'Slides',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => 'edit.php?post_type=nlsb_slider', // onder NL Sliders
    'supports'     => ['title','editor','thumbnail','page-attributes'],
  ]);
});
