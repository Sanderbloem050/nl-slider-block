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
        <style id="nlsb-inline-modal">
.rucs-info-toggle{position:absolute;left:50%;top:8px;transform:translateX(-50%);z-index:5;width:44px;height:44px;border:0;border-radius:999px;cursor:pointer;background:#fff;color:#111;box-shadow:0 6px 18px rgba(0,0,0,.18);font-size:24px;font-weight:700;line-height:44px}
.rucs-slider.modal-open .rucs-info-toggle{font-size:0}
.rucs-slider.modal-open .rucs-info-toggle::before{content:"×";font-size:24px;line-height:44px;display:block}
.rucs-slider.modal-open::before{content:"";position:fixed;inset:0;z-index:9900;background:rgba(0,0,0,.6);backdrop-filter:saturate(120%) blur(2px)}
.rucs-info-modal{position:fixed;z-index:9901;top:clamp(12px,3vh,32px);left:clamp(12px,3vw,32px);width:min(1100px,calc(100vw - 24px));max-height:calc(100vh - 24px);overflow:auto;background:#fff;color:#111;border-radius:20px 0 20px 20px;box-shadow:0 20px 50px rgba(0,0,0,.45);padding:clamp(20px,3vw,40px);transform:translateY(-12px) scale(.98);opacity:0;pointer-events:none;transition:transform .28s ease,opacity .28s ease}
.rucs-slider.modal-open .rucs-info-modal{opacity:1;pointer-events:auto;transform:translateY(0) scale(1)}
.rucs-info-close{position:absolute;top:10px;right:10px;width:36px;height:36px;border:0;border-radius:50%;background:rgba(0,0,0,.08);color:#000;font-size:20px;line-height:1;display:flex;align-items:center;justify-content:center;cursor:pointer}
</style>
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
