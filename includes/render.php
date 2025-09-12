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

  // Slider settings
  $height     = get_post_meta($slider_id,'_nlsb_height',  true) ?: '65vh';
  $mheight    = get_post_meta($slider_id,'_nlsb_mheight', true) ?: '60vh';
  $info_mode  = get_post_meta($slider_id,'_nlsb_infobar_mode',  true) ?: 'accent';   // 'accent' | 'custom'
  $info_color = get_post_meta($slider_id,'_nlsb_infobar_color', true) ?: '#111111';  // vaste kleur bij 'custom'

  // Slides ophalen
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

  // Shared info (we tonen content van slide 1 als "globaal" fallback)
  $first_id      = $slides[0]->ID;
  $shared_title  = get_the_title($first_id);
  $shared_body   = apply_filters('the_content', get_post_field('post_content', $first_id));
  $shared_btnTxt = get_post_meta($first_id,'_nlsb_btnText', true) ?: '';
  $shared_btnUrl = get_post_meta($first_id,'_nlsb_btnUrl',  true) ?: '';
  $shared_accent = get_post_meta($first_id,'_nlsb_accent', true) ?: '#ffeb00';

  // Bepaal of ALLE slides type A zijn
  $only_type_a = true;
  foreach ($slides as $_s) {
    $lay = get_post_meta($_s->ID,'_nlsb_layout', true)==='b' ? 'b' : 'a';
    if ($lay === 'b') { $only_type_a = false; break; }
  }

  wp_enqueue_style('nlsb-slider');
  wp_enqueue_script('nlsb-slider');

  // Uniek id per instance (voor JS scoping indien nodig)
  $wrap_id = 'nlsb-slider-'.uniqid();

  ob_start(); ?>
  <div id="<?php echo esc_attr($wrap_id); ?>"
       class="rucs-slider"
       data-per-slide-modal="<?php echo $only_type_a ? '1' : '0'; ?>"
       style="--rucs-height:<?php echo esc_attr($height); ?>;
              --rucs-height-mobile:<?php echo esc_attr($mheight); ?>;
              --ru-accent: <?php echo esc_attr($shared_accent); ?>;">

    <!-- Globale info-modal (inhoud: gedeeld óf per-slide, via JS) -->
    <div id="rucs-info-modal"
         class="rucs-info-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="rucs-info-title">
      <button class="rucs-info-close" aria-label="<?php esc_attr_e('Sluiten','nlsb'); ?>">×</button>

      <!-- SLOT: wordt gevuld door JS (per-slide) of bevat fallback (gedeeld) -->
      <div class="rucs-info-slot">
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
    </div>

    <!-- Inline styles (mag later naar assets/css/slider.css verplaatst worden) -->
    <style id="nlsb-inline-modal">
      /* Overlay wanneer modal open is */
      .rucs-slider.modal-open::before{
        content:""; position:fixed; inset:0; z-index:9900;
        background:rgba(0,0,0,.6); backdrop-filter:saturate(120%) blur(2px);
      }
      /* Modal – positie/maat via CSS vars die JS zet o.b.v. pill */
      .rucs-info-modal{
        position:fixed; z-index:9901;

        left: var(--nlsb-modal-left, clamp(12px,3vw,32px));
        top:  var(--nlsb-modal-top,  clamp(12px,3vh,32px));
        width: min(var(--ru-modal-w, 50vw), calc(100vw - 24px));

        max-height:85vh; overflow:auto;

        background: var(--ru-modal-bg, var(--ru-accent, #ffffff));
        color:      var(--ru-modal-text, #111111);

        border-radius:20px 0 20px 20px;
        box-shadow:0 20px 50px rgba(0,0,0,.45);
        padding:clamp(20px,3vw,40px);

        transform:translateY(-12px) scale(.98);
        opacity:0; pointer-events:none;
        transition:transform .28s ease,opacity .28s ease;
      }
      .rucs-slider.modal-open .rucs-info-modal{
        opacity:1; pointer-events:auto; transform:translateY(0) scale(1);
      }
      .rucs-info-close{
        position:absolute; top:10px; right:10px;
        width:36px; height:36px; border:0; border-radius:50%;
        background:rgba(0,0,0,.08); color:#000; font-size:20px;
        display:flex; align-items:center; justify-content:center; cursor:pointer;
      }

      /* Topbar (transparant) + pill (50%, kleur per slide of custom sliderkleur) */
      .rucs-slide .info-bar{
        position:absolute; left:0; right:0; top:0;
        height: var(--ru-info-h, 52px);
        background: transparent;
        display:flex; align-items:center;
        z-index:3;
        pointer-events:none; /* alleen de pill is klikbaar */
      }
      .rucs-slide .info-pill{
        height: var(--ru-info-h, 52px);
        width: 50%;
        margin-left: 0;
        background: var(--ru-info-bg, var(--ru-accent, #ffeb00));
        color: var(--ru-text, #111);
        border:0; cursor:pointer;
        border-radius: 0 999px 999px 0;  /* afgerond rechts */
        display:flex; align-items:center; justify-content:flex-end; /* plus rechts */
        padding: 0 14px;
        box-shadow: 0 6px 18px rgba(0,0,0,.18);
        pointer-events:auto; /* klikbaar */
      }
      .rucs-slide .info-pill:focus{ outline:2px solid #000; outline-offset:2px; }
      .rucs-slide .info-plus{
        font-size:22px; line-height:1; user-select:none;
        display:inline-flex; align-items:center; justify-content:center;
        width:28px; height:28px; border-radius:999px;
      }

      @media (max-width:780px){
        .rucs-slide .info-bar{ height: var(--ru-info-h-mobile, 44px); }
        .rucs-slide .info-pill{ height: var(--ru-info-h-mobile, 44px); width: 50%; }
      }
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

        // Per-slide modal tekstkleur (optioneel)
        $modalText = get_post_meta($id,'_nlsb_modalText', true) ?: '';

        // Kleur voor de balk/pill: slider custom of slide accent
        $bar_bg = ($info_mode === 'custom' && $info_color) ? $info_color : $accent;
      ?>
        <section class="rucs-slide <?php echo esc_attr($layout); ?>"
          style="<?php echo $img ? "background-image:url('".esc_url($img)."');" : ''; ?>
                 --ru-left: <?php echo esc_attr($left); ?>;
                 --ru-accent: <?php echo esc_attr($accent); ?>;
                 <?php if ($modalText) echo "--ru-modal-text: ".esc_attr($modalText).";"; ?>
                 <?php if ($capH!=='') echo "--ru-cap-h: ".esc_attr($capH).";"; ?>">

          <!-- TOPBAR: transparante balk + pill (opent modal) -->
          <div class="info-bar" style="--ru-info-bg: <?php echo esc_attr($bar_bg); ?>;">
            <button class="info-pill" type="button" aria-label="<?php esc_attr_e('Toon informatie','nlsb'); ?>" aria-controls="rucs-info-modal">
              <span class="info-plus" aria-hidden="true">+</span>
            </button>
          </div>

          <?php if ($layout==='type_a'): ?>
            <div class="panel-left">
              <?php if ($title): ?><h2 class="title"><?php echo esc_html($title); ?></h2><?php endif; ?>
              <?php if ($body):  ?><div class="text"><?php echo $body; ?></div><?php endif; ?>
              <?php if ($btnTxt && $btnUrl): ?><p><a class="button" href="<?php echo esc_url($btnUrl); ?>"><?php echo esc_html($btnTxt); ?></a></p><?php endif; ?>
            </div>
          <?php else: // type_b ?>
            <div class="caption"><?php echo esc_html($caption ?: $title); ?></div>
          <?php endif; ?>

          <!-- VERBORGEN TEMPLATE voor per-slide modal content -->
          <template class="rucs-modal-tpl">
            <?php if ($title): ?>
              <h2><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if ($body): ?>
              <div class="rucs-info-body"><?php echo $body; ?></div>
            <?php endif; ?>
            <?php if ($btnTxt && $btnUrl): ?>
              <p><a class="button" href="<?php echo esc_url($btnUrl); ?>"><?php echo esc_html($btnTxt); ?></a></p>
            <?php endif; ?>
          </template>

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
