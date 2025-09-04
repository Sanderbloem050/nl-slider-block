<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode:
 * [nlsb_slider id="123"]
 * [nlsb_slider slug="home-slider"]
 */
add_shortcode('nlsb_slider', function($atts){
  $atts = shortcode_atts(['id'=>'','slug'=>''], $atts, 'nlsb_slider');
  $slider_id = intval($atts['id']);
  if (!$slider_id && $atts['slug']) {
    $p = get_page_by_path(sanitize_title($atts['slug']), OBJECT, 'nlsb_slider');
    if ($p) $slider_id = $p->ID;
  }
  if (!$slider_id) return '';

  $height  = get_post_meta($slider_id,'_nlsb_height',  true) ?: '65vh';
  $mheight = get_post_meta($slider_id,'_nlsb_mheight', true) ?: '60vh';

  $slides = get_posts([
    'post_type'      => 'nlsb_slide',
    'posts_per_page' => -1,
    'meta_key'       => 'nlsb_parent',
    'meta_value'     => $slider_id,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
    'post_status'    => 'publish',
  ]);
  if (!$slides) return '';

  // ===== Shared info (slide 1) =====
  $first_id      = $slides[0]->ID;
  $shared_title  = get_the_title($first_id);
  $shared_body   = apply_filters('the_content', get_post_field('post_content', $first_id));
  $shared_btnTxt = get_post_meta($first_id,'_nlsb_btnText', true) ?: '';
  $shared_btnUrl = get_post_meta($first_id,'_nlsb_btnUrl',  true) ?: '';
  $shared_accent = get_post_meta($first_id,'_nlsb_accent', true) ?: '#ffeb00';

  wp_enqueue_style('nlsb-slider');
  wp_enqueue_script('nlsb-slider');

  ob_start(); ?>
  <div class="rucs-slider"
       style="--rucs-height:<?php echo esc_attr($height); ?>;
              --rucs-height-mobile:<?php echo esc_attr($mheight); ?>;
              --ru-accent: <?php echo esc_attr($shared_accent); ?>;">

    <!-- Globale info toggle + modal (gebruikt content van slide 1) -->
    <button class="rucs-info-toggle"
            aria-expanded="false"
            aria-controls="rucs-info-modal"
            title="<?php esc_attr_e('Toon informatie','nlsb'); ?>">+</button>

    <div id="rucs-info-modal"
         class="rucs-info-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="rucs-info-title">
      <button class="rucs-info-close" aria-label="<?php esc_attr_e('Sluiten','nlsb'); ?>">×</button>
      <?php if ($shared_title): ?>
        <h2 id="rucs-info-title"><?php echo esc_html($shared_title); ?></h2>
      <?php endif; ?>
      <?php if ($shared_body): ?>
        <div class="rucs-info-body"><?php echo $shared_body; ?></div>
      <?php endif; ?>
      <?php if ($shared_btnTxt && $shared_btnUrl): ?>
        <p><a class="button" href="<?php echo esc_url($shared_btnUrl); ?>"><?php echo esc_html($shared_btnTxt); ?></a></p>
      <?php endif; ?>
    </div>

    <div class="rucs-track" tabindex="0" aria-roledescription="carousel">
      <?php foreach($slides as $s):
        $id     = $s->ID;
        $title  = get_the_title($id);
        $body   = apply_filters('the_content', $s->post_content);
        $img    = get_the_post_thumbnail_url($id, 'full');

        $layout = get_post_meta($id,'_nlsb_layout', true)==='b' ? 'type_b':'type_a';
        $accent = get_post_meta($id,'_nlsb_accent', true) ?: '#ffeb00';
        $left   = (get_post_meta($id,'_nlsb_leftValue', true) ?: '600').(get_post_meta($id,'_nlsb_leftUnit', true) ?: 'px');
        $capV   = get_post_meta($id,'_nlsb_capHeightValue', true);
        $capU   = get_post_meta($id,'_nlsb_capHeightUnit',  true) ?: 'px';
        $capH   = ($capV === '' ? '' : $capV.$capU);
        $caption= get_post_meta($id,'_nlsb_caption', true) ?: '';
        $btnTxt = get_post_meta($id,'_nlsb_btnText', true) ?: '';
        $btnUrl = get_post_meta($id,'_nlsb_btnUrl',  true) ?: '';
      ?>
        <section class="rucs-slide <?php echo esc_attr($layout); ?>"
          style="<?php echo $img ? "background-image:url('".esc_url($img)."');" : ''; ?>
                 --ru-left: <?php echo esc_attr($left); ?>;
                 --ru-accent: <?php echo esc_attr($accent); ?>;
                 <?php if ($capH!=='') echo "--ru-cap-h: ".esc_attr($capH).";"; ?>">
          <?php if ($layout==='type_a'): ?>
            <div class="panel-left">
              <?php if ($title): ?><h2 class="title"><?php echo esc_html($title); ?></h2><?php endif; ?>
              <?php if ($body):  ?><div class="text"><?php echo $body; ?></div><?php endif; ?>
              <?php if ($btnTxt && $btnUrl): ?><p><a class="button" href="<?php echo esc_url($btnUrl); ?>"><?php echo esc_html($btnTxt); ?></a></p><?php endif; ?>
            </div>
          <?php else: ?>
            <div class="caption"><?php echo esc_html($caption ?: $title); ?></div>
            <!-- Per-slide popup verwijderd; globale info-modal wordt gebruikt -->
          <?php endif; ?>
        </section>
      <?php endforeach; ?>
    </div>

    <button class="nav prev" aria-label="<?php esc_attr_e('Vorige slide','nlsb'); ?>">&#10094;</button>
    <button class="nav next" aria-label="<?php esc_attr_e('Volgende slide','nlsb'); ?>">&#10095;</button>

    <div class="rucs-dots" role="tablist" aria-label="<?php esc_attr_e('Slider paginatie','nlsb'); ?>">
      <?php foreach($slides as $i => $_): ?>
        <button class="dot<?php echo $i===0?' is-active':''; ?>" role="tab" aria-selected="<?php echo $i===0?'true':'false'; ?>" data-index="<?php echo $i; ?>"></button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php
  return ob_get_clean();
});
