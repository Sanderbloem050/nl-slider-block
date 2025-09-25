<?php
if (!defined('ABSPATH')) exit;

const NLSB_META_HERO  = '_nlsb_hero';
const NLSB_META_MODAL = '_nlsb_modal';
const NLSB_META_SLIDES = '_nlsb_slides';

/**
 * Helpers: fetch structured meta
 */
function nlsb_project_get_hero($post_id){
  $raw = get_post_meta($post_id, NLSB_META_HERO, true);
  $raw = is_array($raw) ? $raw : [];
  return [
    'image_id' => isset($raw['image_id']) ? intval($raw['image_id']) : 0,
  ];
}

function nlsb_project_get_modal($post_id){
  $raw = get_post_meta($post_id, NLSB_META_MODAL, true);
  $raw = is_array($raw) ? $raw : [];
  return [
    'title' => isset($raw['title']) ? (string)$raw['title'] : '',
    'body'  => isset($raw['body']) ? (string)$raw['body'] : '',
  ];
}

function nlsb_project_get_slides($post_id){
  $raw = get_post_meta($post_id, NLSB_META_SLIDES, true);
  if (!is_array($raw)) {
    return [];
  }
  $slides = [];
  foreach ($raw as $entry) {
    if (!is_array($entry)) continue;
    $image_id = isset($entry['image_id']) ? intval($entry['image_id']) : 0;
    $title    = isset($entry['title']) ? (string)$entry['title'] : '';
    $body     = isset($entry['body']) ? (string)$entry['body'] : '';
    if (!$image_id && $title === '' && $body === '') continue;
    $slides[] = [
      'image_id' => $image_id,
      'title'    => $title,
      'body'     => $body,
    ];
  }
  return $slides;
}

/**
 * Admin metaboxen
 */
add_action('add_meta_boxes', function(){
  add_meta_box(
    'nlsb_project_hero',
    __('Project slider: hoofdslide & info', 'nlsb'),
    'nlsb_project_render_hero_box',
    'projects',
    'normal',
    'high'
  );

  add_meta_box(
    'nlsb_project_slides',
    __('Project slider: type B slides', 'nlsb'),
    'nlsb_project_render_slides_box',
    'projects',
    'normal',
    'default'
  );

  add_meta_box(
    'nlsb_project_shortcode',
    __('Shortcode', 'nlsb'),
    'nlsb_project_render_shortcode_box',
    'projects',
    'side',
    'high'
  );
});

function nlsb_project_render_hero_box($post){
  $hero  = nlsb_project_get_hero($post->ID);
  $modal = nlsb_project_get_modal($post->ID);
  $hero_url = $hero['image_id'] ? wp_get_attachment_image_url($hero['image_id'], 'large') : '';

  wp_nonce_field('nlsb_project_save', 'nlsb_project_nonce');
  ?>
  <div class="nlsb-meta">
    <div class="nlsb-meta-section">
      <h3><?php esc_html_e('Hoofdslide (Type A)', 'nlsb'); ?></h3>
      <p class="description"><?php esc_html_e('Kies de hoofdafbeelding voor de eerste slide.', 'nlsb'); ?></p>

      <div class="nlsb-field">
        <label class="nlsb-field-label"><?php esc_html_e('Hoofdafbeelding', 'nlsb'); ?></label>
        <div class="nlsb-media" data-type="hero">
          <div class="nlsb-media-preview" data-target="hero">
            <?php if ($hero_url): ?>
              <img src="<?php echo esc_url($hero_url); ?>" alt="" />
            <?php else: ?>
              <span class="nlsb-media-placeholder"><?php esc_html_e('Nog geen afbeelding geselecteerd', 'nlsb'); ?></span>
            <?php endif; ?>
          </div>
          <div class="nlsb-media-buttons">
            <input type="hidden" name="nlsb_hero[image_id]" value="<?php echo esc_attr($hero['image_id']); ?>" data-field="image_id">
            <button type="button" class="button nlsb-media-select" data-field="image_id"><?php esc_html_e('Kies afbeelding', 'nlsb'); ?></button>
            <button type="button" class="button-link nlsb-media-remove" data-field="image_id" <?php echo $hero_url ? '' : 'style="display:none"'; ?>><?php esc_html_e('Verwijder', 'nlsb'); ?></button>
          </div>
        </div>
      </div>
    </div>

    <div class="nlsb-meta-section">
      <h3><?php esc_html_e('Project info (modal)', 'nlsb'); ?></h3>
      <p class="description"><?php esc_html_e('Deze tekst verschijnt in de info-modal op alle slides.', 'nlsb'); ?></p>

      <div class="nlsb-field">
        <label for="nlsb-modal-title" class="nlsb-field-label"><?php esc_html_e('Titel (optioneel)', 'nlsb'); ?></label>
        <input type="text" id="nlsb-modal-title" name="nlsb_modal[title]" class="widefat" value="<?php echo esc_attr($modal['title']); ?>">
      </div>

      <div class="nlsb-field">
        <label class="nlsb-field-label" for="nlsb-modal-body"><?php esc_html_e('Projectomschrijving', 'nlsb'); ?></label>
        <?php
        $settings = [
          'textarea_name' => 'nlsb_modal[body]',
          'media_buttons' => false,
          'textarea_rows' => 10,
          'teeny'         => true,
        ];
        wp_editor($modal['body'], 'nlsb-modal-body', $settings);
        ?>
      </div>
    </div>
  </div>
  <?php
}

function nlsb_project_render_slides_box($post){
  $slides = nlsb_project_get_slides($post->ID);
  ?>
  <div class="nlsb-meta">
    <p class="description"><?php esc_html_e('Voeg extra slides toe (Type B). Iedere slide heeft een eigen afbeelding en linker tekstkolom.', 'nlsb'); ?></p>
    <div class="nlsb-slides" id="nlsb-project-slides">
      <?php
      if ($slides) {
        foreach ($slides as $index => $slide) {
          echo nlsb_project_slide_item_html($index, $slide);
        }
      }
      ?>
    </div>

    <p>
      <button type="button" class="button button-primary" id="nlsb-add-slide"><?php esc_html_e('Slide toevoegen', 'nlsb'); ?></button>
    </p>

    <div id="nlsb-slide-template" style="display:none;">
      <?php echo nlsb_project_slide_item_html('__INDEX__', []); ?>
    </div>
  </div>
  <?php
}

function nlsb_project_render_shortcode_box($post){
  if (!$post->ID || get_post_status($post) === 'auto-draft') {
    echo '<p>'.esc_html__('Sla het project eerst op om de shortcode te zien.', 'nlsb').'</p>';
    return;
  }

  $slug = $post->post_name;
  $shortcode = $slug ? '[nlsb_slider slug="'.esc_attr($slug).'"]' : '';
  $uid = 'nlsb_proj_sc_'.uniqid();
  $input = $uid.'_inp';
  $button = $uid.'_btn';
  ?>
  <div class="nlsb-sc-wrap">
    <label><strong><?php esc_html_e('Shortcode', 'nlsb'); ?></strong></label>
    <input
      type="text"
      readonly
      class="codefield"
      id="<?php echo esc_attr($input); ?>"
      value="<?php echo esc_attr($shortcode); ?>"
      <?php echo $shortcode ? '' : 'placeholder="[nlsb_slider slug=&quot;voorbeeld-project&quot;]"'; ?>
    >
    <div class="nlsb-sc-buttons">
      <button type="button" class="button" id="<?php echo esc_attr($button); ?>" <?php echo $shortcode ? '' : 'disabled'; ?>><?php esc_html_e('Kopieer shortcode', 'nlsb'); ?></button>
    </div>
    <p class="description"><?php printf(esc_html__('Slug: %s', 'nlsb'), '<code>'.esc_html($slug ?: __('onbekend', 'nlsb')).'</code>'); ?></p>
  </div>
  <style>
    #nlsb_project_shortcode .nlsb-sc-wrap{display:flex;flex-direction:column;gap:8px}
    #nlsb_project_shortcode .nlsb-sc-wrap .codefield{font-family:monospace;width:100%;box-sizing:border-box}
    #nlsb_project_shortcode .nlsb-sc-buttons{display:flex;gap:6px}
  </style>
  <script>
    (function(){
      var btn = document.getElementById('<?php echo esc_js($button); ?>');
      var inp = document.getElementById('<?php echo esc_js($input); ?>');
      if (!btn || !inp || !inp.value) return;
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var val = inp.value;
        function feedback(){
          var old = btn.textContent;
          btn.textContent = '<?php echo esc_js(__('Gekopieerd!', 'nlsb')); ?>';
          btn.disabled = true;
          setTimeout(function(){ btn.textContent = old; btn.disabled = false; }, 1100);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(val).then(feedback).catch(function(){
            inp.removeAttribute('readonly');
            inp.select();
            document.execCommand('copy');
            inp.setAttribute('readonly','readonly');
            feedback();
          });
        } else {
          inp.removeAttribute('readonly');
          inp.select();
          document.execCommand('copy');
          inp.setAttribute('readonly','readonly');
          feedback();
        }
      });
    })();
  </script>
  <?php
}
function nlsb_project_slide_item_html($index, $slide){
  $title    = isset($slide['title']) ? (string)$slide['title'] : '';
  $body     = isset($slide['body']) ? (string)$slide['body'] : '';
  $image_id = isset($slide['image_id']) ? intval($slide['image_id']) : 0;
  $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
  $name_prefix = 'nlsb_slides['.$index.']';
  ob_start();
  ?>
  <div class="nlsb-slide" data-index="<?php echo esc_attr($index); ?>">
    <div class="nlsb-slide-head">
      <span class="dashicons dashicons-move nlsb-slide-handle" aria-hidden="true"></span>
      <strong class="nlsb-slide-title-text"><?php echo $title !== '' ? esc_html($title) : esc_html__('(zonder titel)', 'nlsb'); ?></strong>
      <div class="nlsb-slide-actions">
        <button type="button" class="button-link nlsb-slide-toggle" aria-expanded="true"><?php esc_html_e('Inklappen', 'nlsb'); ?></button>
        <button type="button" class="button-link-delete nlsb-slide-remove"><?php esc_html_e('Verwijderen', 'nlsb'); ?></button>
      </div>
    </div>
    <div class="nlsb-slide-body">
      <div class="nlsb-field">
        <label class="nlsb-field-label"><?php esc_html_e('Afbeelding', 'nlsb'); ?></label>
        <div class="nlsb-media" data-type="slide">
          <div class="nlsb-media-preview">
            <?php if ($image_url): ?>
              <img src="<?php echo esc_url($image_url); ?>" alt="" />
            <?php else: ?>
              <span class="nlsb-media-placeholder"><?php esc_html_e('Nog geen afbeelding geselecteerd', 'nlsb'); ?></span>
            <?php endif; ?>
          </div>
          <div class="nlsb-media-buttons">
            <input type="hidden" class="nlsb-field-input" data-field="image_id" name="<?php echo esc_attr($name_prefix.'[image_id]'); ?>" value="<?php echo esc_attr($image_id); ?>">
            <button type="button" class="button nlsb-media-select" data-field="image_id"><?php esc_html_e('Kies afbeelding', 'nlsb'); ?></button>
            <button type="button" class="button-link nlsb-media-remove" data-field="image_id" <?php echo $image_url ? '' : 'style="display:none"'; ?>><?php esc_html_e('Verwijder', 'nlsb'); ?></button>
          </div>
        </div>
      </div>

      <div class="nlsb-field">
        <label class="nlsb-field-label" for="nlsb-slide-title-<?php echo esc_attr($index); ?>"><?php esc_html_e('Titel', 'nlsb'); ?></label>
        <input type="text" class="widefat nlsb-slide-title" id="nlsb-slide-title-<?php echo esc_attr($index); ?>" data-field="title" name="<?php echo esc_attr($name_prefix.'[title]'); ?>" value="<?php echo esc_attr($title); ?>">
      </div>

      <div class="nlsb-field">
        <label class="nlsb-field-label" for="nlsb-slide-body-<?php echo esc_attr($index); ?>"><?php esc_html_e('Tekst', 'nlsb'); ?></label>
        <textarea class="widefat nlsb-slide-body-text" rows="6" id="nlsb-slide-body-<?php echo esc_attr($index); ?>" data-field="body" name="<?php echo esc_attr($name_prefix.'[body]'); ?>"><?php echo esc_textarea($body); ?></textarea>
      </div>
    </div>
  </div>
  <?php
  return ob_get_clean();
}

add_action('save_post_projects', function ($post_id, $post){
  if ($post->post_type !== 'projects') return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!isset($_POST['nlsb_project_nonce']) || !wp_verify_nonce($_POST['nlsb_project_nonce'], 'nlsb_project_save')) return;
  if (!current_user_can('edit_post', $post_id)) return;

  // Hero
  $hero = isset($_POST['nlsb_hero']) && is_array($_POST['nlsb_hero']) ? $_POST['nlsb_hero'] : [];
  $hero_data = [
    'image_id' => isset($hero['image_id']) ? intval($hero['image_id']) : 0,
  ];
  if ($hero_data['image_id']) update_post_meta($post_id, NLSB_META_HERO, $hero_data);
  else delete_post_meta($post_id, NLSB_META_HERO);

  // Modal
  $modal = isset($_POST['nlsb_modal']) && is_array($_POST['nlsb_modal']) ? $_POST['nlsb_modal'] : [];
  $modal_data = [
    'title' => isset($modal['title']) ? sanitize_text_field($modal['title']) : '',
    'body'  => isset($modal['body']) ? wp_kses_post($modal['body']) : '',
  ];
  if ($modal_data['title'] !== '' || $modal_data['body'] !== '') update_post_meta($post_id, NLSB_META_MODAL, $modal_data);
  else delete_post_meta($post_id, NLSB_META_MODAL);

  // Slides
  $slides_input = isset($_POST['nlsb_slides']) && is_array($_POST['nlsb_slides']) ? $_POST['nlsb_slides'] : [];
  $slides_data = [];
  foreach ($slides_input as $item) {
    if (!is_array($item)) continue;
    $image_id = isset($item['image_id']) ? intval($item['image_id']) : 0;
    $title    = isset($item['title']) ? sanitize_text_field($item['title']) : '';
    $body     = isset($item['body']) ? wp_kses_post($item['body']) : '';
    if (!$image_id && $title === '' && $body === '') continue;
    $slides_data[] = [
      'image_id' => $image_id,
      'title'    => $title,
      'body'     => $body,
    ];
  }
  if ($slides_data) update_post_meta($post_id, NLSB_META_SLIDES, $slides_data);
  else delete_post_meta($post_id, NLSB_META_SLIDES);
}, 10, 2);



