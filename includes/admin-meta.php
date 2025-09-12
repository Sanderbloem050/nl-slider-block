<?php
if (!defined('ABSPATH')) exit;

/** Metaboxen */
add_action('add_meta_boxes', function () {
  add_meta_box('nlsb_slider_settings', 'Slider instellingen', 'nlsb_slider_settings_box', 'nlsb_slider', 'side',   'default');
  add_meta_box('nlsb_slider_builder',  'Slides',             'nlsb_slider_builder_box',  'nlsb_slider', 'normal', 'high');
  add_meta_box('nlsb_slide_fields',    'Slide instellingen', 'nlsb_slide_fields_box',    'nlsb_slide',  'normal', 'high');
});

/** Slider instellingen */
function nlsb_slider_settings_box($post){
  $h  = get_post_meta($post->ID, '_nlsb_height',  true) ?: '65vh';
  $mh = get_post_meta($post->ID, '_nlsb_mheight', true) ?: '60vh';
  
  // Info-balk instellingen
  $ib_mode  = get_post_meta($post->ID, '_nlsb_infobar_mode',  true) ?: 'accent'; // 'accent' | 'custom'
  $ib_color = get_post_meta($post->ID, '_nlsb_infobar_color', true) ?: '#111111';
  
  wp_nonce_field('nlsb_save_slider','nlsb_nonce_slider');
  ?>
  <p><label>Hoogte (desktop/tablet)
    <input type="text" name="nlsb_height" value="<?php echo esc_attr($h); ?>" class="widefat" placeholder="bijv. 65vh of 600px">
  </label></p>
  <p><label>Hoogte (mobiel)
    <input type="text" name="nlsb_mheight" value="<?php echo esc_attr($mh); ?>" class="widefat" placeholder="bijv. 60vh">
  </label></p>
  <hr>
  <p><strong>Info-balk (top) kleur</strong></p>
  <p>
    <label><input type="radio" name="nlsb_infobar_mode" value="accent" <?php checked($ib_mode,'accent'); ?>>
      Gebruik <em>accentkleur van de slide</em></label><br>
    <label><input type="radio" name="nlsb_infobar_mode" value="custom" <?php checked($ib_mode,'custom'); ?>>
      Gebruik <em>vaste kleur</em> → </label>
    <input type="color" name="nlsb_infobar_color" value="<?php echo esc_attr($ib_color); ?>" style="vertical-align:middle;">
  </p>
  <?php
}

/** Slider builder */
function nlsb_slider_builder_box($post){
  $slides = get_posts([
    'post_type'      => 'nlsb_slide',
    'posts_per_page' => -1,
    'meta_key'       => 'nlsb_parent',
    'meta_value'     => $post->ID,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
    'post_status'    => ['publish','draft'],
  ]);
  ?>
  <div class="nlsb-builder" data-slider="<?php echo esc_attr($post->ID); ?>">
    <div class="nlsb-list" id="nlsb-list">
      <?php foreach($slides as $s): echo nlsb_builder_item_html($s->ID, $s->post_title); endforeach; ?>
    </div>

    <div class="nlsb-actions">
      <input type="text" class="regular-text" id="nlsb-new-title" placeholder="Titel nieuwe slide">
      <button type="button" class="button button-primary" id="nlsb-add-slide">Slide toevoegen</button>
      <span class="desc">Sleep om te sorteren • Klik het tandwiel om te bewerken</span>
    </div>
  </div>

  <!-- ### QUICK-EDIT TEMPLATE (tabs + grid) ################################ -->
  <script type="text/template" id="nlsb-quick-template">
    <div class="nlsb-quick">
      <!-- Tabs -->
      <ul class="nlsb-tabs">
        <li><button class="active" data-tab="basics" type="button">Basis</button></li>
        <li><button data-tab="media"  type="button">Media</button></li>
        <li><button data-tab="style"  type="button">Stijl</button></li>
        <li><button data-tab="link"   type="button">Link</button></li>
        <li><button data-tab="adv"    type="button">Geavanceerd</button></li>
      </ul>

      <div class="nlsb-tabpanes">
        <!-- BASIS -->
        <div class="nlsb-tabpane show" data-tab="basics">
          <div class="nlsb-grid">
            <label>
              <span class="nlsb-label">Titel</span>
              <input type="text" class="nlsb-q-title" />
            </label>

            <label>
              <span class="nlsb-label">Layout</span>
              <select class="nlsb-q-layout">
                <option value="a">Type A (linkerkolom)</option>
                <option value="b">Type B (caption onder)</option>
              </select>
            </label>

            <label class="nlsb-span2">
              <span class="nlsb-label">Tekst</span>
              <textarea class="nlsb-q-body" rows="4"></textarea>
            </label>

            <label class="nlsb-span2 nlsb-only-b">
              <span class="nlsb-label">Caption</span>
              <input type="text" class="nlsb-q-caption" />
            </label>

            <div class="nlsb-only-a">
              <span class="nlsb-label">Breedte linker kolom</span>
              <div class="nlsb-inputgroup">
                <input type="number" class="nlsb-q-leftValue" />
                <select class="nlsb-q-leftUnit">
                  <option value="px">px</option>
                  <option value="%">%</option>
                </select>
              </div>
            </div>

            <div class="nlsb-only-b">
              <span class="nlsb-label">Hoogte onderbalk</span>
              <div class="nlsb-inputgroup">
                <input type="number" class="nlsb-q-capHeightValue" />
                <select class="nlsb-q-capHeightUnit">
                  <option value="px">px</option>
                  <option value="rem">rem</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- MEDIA -->
        <div class="nlsb-tabpane" data-tab="media">
          <div class="nlsb-grid">
            <div class="nlsb-span2 nlsb-media-thumbwrap">
              <img class="nlsb-q-thumb" src="" alt="" />
              <input type="hidden" class="nlsb-q-thumb-id" />
            </div>
            <div>
              <button type="button" class="button nlsb-q-choose">Kies afbeelding</button>
              <button type="button" class="button button-link-delete nlsb-q-remove">Verwijder</button>
              <p class="description">Aanbevolen: 1600×900 (16:9) of groter.</p>
            </div>
          </div>
        </div>

        <!-- STIJL -->
        <div class="nlsb-tabpane" data-tab="style">
          <div class="nlsb-grid">
            <label>
              <span class="nlsb-label">Accentkleur</span>
              <input type="color" class="nlsb-q-accent" />
            </label>

            <label>
              <span class="nlsb-label">Button-tekst</span>
              <input type="text" class="nlsb-q-btnText" placeholder="Lees meer" />
            </label>
          </div>
        </div>

        <!-- LINK -->
        <div class="nlsb-tabpane" data-tab="link">
          <div class="nlsb-grid">
            <label class="nlsb-span2">
              <span class="nlsb-label">URL</span>
              <input type="url" class="nlsb-q-btnUrl" placeholder="https://…" />
            </label>
          </div>
        </div>

        <!-- GEVANCEERD -->
        <div class="nlsb-tabpane" data-tab="adv">
          <p class="description">Geavanceerde opties kunnen hier later bij.</p>
        </div>
      </div>

      <div class="nlsb-q-actions">
        <button type="button" class="button button-primary nlsb-q-save">Opslaan</button>
        <button type="button" class="button nlsb-q-cancel">Sluiten</button>
      </div>
    </div>
  </script>

  <!-- Mini tab-switcher (event delegation; botst niet met admin.js) -->
  <script>
    (function($){
      $(document).on('click', '.nlsb-tabs button', function(){
        var $btn = $(this), tab = $btn.data('tab'), $q = $btn.closest('.nlsb-quick');
        $q.find('.nlsb-tabs button').removeClass('active');
        $btn.addClass('active');
        $q.find('.nlsb-tabpane').removeClass('show');
        $q.find('.nlsb-tabpane[data-tab="'+tab+'"]').addClass('show');
      });
    })(jQuery);
  </script>
  <?php
}


function nlsb_builder_item_html($id,$title){
  $title = $title ?: '(zonder titel)';
  $thumb = get_the_post_thumbnail_url($id,'thumbnail');
  $thumbHtml = $thumb ? '<img src="'.esc_url($thumb).'" alt="" />' : '<span class="noimg">Geen afbeelding</span>';

  return '<div class="nlsb-item" data-id="'.esc_attr($id).'">
    <span class="dashicons dashicons-move handle" title="Sleep om te sorteren"></span>
    <div class="thumb">'.$thumbHtml.'</div>
    <div class="meta"><strong class="title">'.esc_html($title).'</strong><div class="mini">ID '.$id.'</div></div>
    <div class="act">
      <button type="button" class="button button-small nlsb-edit" title="Snel bewerken">⚙</button>
      <button type="button" class="button button-small nlsb-del" title="Verwijderen">✕</button>
    </div>
    <div class="nlsb-quick-wrap" style="display:none;"></div>
  </div>';
}

/** Slide velden */
function nlsb_slide_fields_box($post){
  $parent  = intval(get_post_meta($post->ID, 'nlsb_parent', true));
  $layout  = get_post_meta($post->ID, '_nlsb_layout', true) ?: 'a';
  $accent  = get_post_meta($post->ID, '_nlsb_accent', true) ?: '#ffeb00';
  $caption = get_post_meta($post->ID, '_nlsb_caption', true) ?: '';
  $leftV   = get_post_meta($post->ID, '_nlsb_leftValue', true) ?: '600';
  $leftU   = get_post_meta($post->ID, '_nlsb_leftUnit',  true) ?: 'px';
  $capHV   = get_post_meta($post->ID, '_nlsb_capHeightValue', true) ?? '';
  $capHU   = get_post_meta($post->ID, '_nlsb_capHeightUnit',  true) ?: 'px';
  $btnTxt  = get_post_meta($post->ID, '_nlsb_btnText', true) ?: '';
  $btnUrl  = get_post_meta($post->ID, '_nlsb_btnUrl',  true) ?: '';
  $modalTxt= get_post_meta($post->ID, '_nlsb_modalText', true) ?: '#111111'; // << NIEUW

  wp_nonce_field('nlsb_save_slide','nlsb_nonce_slide');
  $sliders = get_posts(['post_type'=>'nlsb_slider','numberposts'=>-1,'post_status'=>'any']);
  ?>
  <table class="form-table nlsb-slide-fields"><tbody>
    <tr><th><label>Behoort bij slider</label></th><td>
      <select name="nlsb_parent" required>
        <option value="">— Kies slider —</option>
        <?php foreach($sliders as $sl): ?>
          <option value="<?php echo $sl->ID; ?>" <?php selected($parent,$sl->ID); ?>>
            <?php echo esc_html($sl->post_title ?: 'Slider '.$sl->ID); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </td></tr>
    <tr><th><label>Layout</label></th><td>
      <label><input type="radio" name="nlsb_layout" value="a" <?php checked($layout,'a'); ?>> Type A (linkerkolom)</label>
      &nbsp;&nbsp;<label><input type="radio" name="nlsb_layout" value="b" <?php checked($layout,'b'); ?>> Type B (caption onder)</label>
    </td></tr>
    <tr class="nlsb-only-a"><th><label>Breedte linker kolom</label></th><td>
      <input type="number" name="nlsb_leftValue" value="<?php echo esc_attr($leftV); ?>" style="max-width:120px;">
      <select name="nlsb_leftUnit"><option value="px" <?php selected($leftU,'px'); ?>>px</option><option value="%" <?php selected($leftU,'%'); ?>>%</option></select>
    </td></tr>
    <tr class="nlsb-only-b"><th><label>Caption</label></th><td>
      <input type="text" class="regular-text" name="nlsb_caption" value="<?php echo esc_attr($caption); ?>">
    </td></tr>
    <tr class="nlsb-only-b"><th><label>Hoogte onderbalk (optioneel)</label></th><td>
      <input type="number" name="nlsb_capHeightValue" value="<?php echo esc_attr($capHV); ?>" style="max-width:120px;">
      <select name="nlsb_capHeightUnit"><option value="px" <?php selected($capHU,'px'); ?>>px</option><option value="rem" <?php selected($capHU,'rem'); ?>>rem</option></select>
      <p class="description">Leeg laten = automatische hoogte</p>
    </td></tr>
    <tr><th><label>Accentkleur</label></th><td>
      <input type="color" name="nlsb_accent" value="<?php echo esc_attr($accent); ?>">
    </td></tr>
    <tr><th><label>Modal tekstkleur</label></th><td>
      <input type="color" name="nlsb_modalText" value="<?php echo esc_attr($modalTxt); ?>">
      <p class="description">Kleur van de tekst in de info-modal voor deze slide.</p>
    </td></tr>
    <tr><th><label>Button (optioneel)</label></th><td>
      <input type="text" class="regular-text" name="nlsb_btnText" placeholder="Tekst" value="<?php echo esc_attr($btnTxt); ?>"><br>
      <input type="url"  class="regular-text" name="nlsb_btnUrl"  placeholder="https://…" value="<?php echo esc_attr($btnUrl); ?>">
    </td></tr>
    <tr><th><label>Achtergrond</label></th><td><p class="description">Gebruik de <strong>uitgelichte afbeelding</strong> als achtergrond.</p></td></tr>
  </tbody></table>

  <script>
  (function(){
    function toggle(){
      var isB = document.querySelector('input[name="nlsb_layout"][value="b"]').checked;
      document.querySelectorAll('.nlsb-only-b').forEach(el=>el.style.display = isB ? '' : 'none');
      document.querySelectorAll('.nlsb-only-a').forEach(el=>el.style.display = isB ? 'none' : '');
    }
    document.querySelectorAll('input[name="nlsb_layout"]').forEach(r=>r.addEventListener('change',toggle));
    toggle();
  })();
  </script>
  <?php
}

/** Save slider */
add_action('save_post_nlsb_slider', function ($post_id){
  if (empty($_POST['nlsb_nonce_slider']) || !wp_verify_nonce($_POST['nlsb_nonce_slider'],'nlsb_save_slider')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;

  update_post_meta($post_id, '_nlsb_height',  sanitize_text_field($_POST['nlsb_height']  ?? '65vh'));
  update_post_meta($post_id, '_nlsb_mheight', sanitize_text_field($_POST['nlsb_mheight'] ?? '60vh'));

  $modeRaw = $_POST['nlsb_infobar_mode'] ?? 'accent';
  $mode    = in_array($modeRaw, ['accent','custom'], true) ? $modeRaw : 'accent';
  $color   = sanitize_hex_color($_POST['nlsb_infobar_color'] ?? '#111111');

  update_post_meta($post_id, '_nlsb_infobar_mode',  $mode);
  update_post_meta($post_id, '_nlsb_infobar_color', $color ?: '#111111');
});

/** Save slide */
add_action('save_post_nlsb_slide', function ($post_id){
  if (empty($_POST['nlsb_nonce_slide']) || !wp_verify_nonce($_POST['nlsb_nonce_slide'],'nlsb_save_slide')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;

  update_post_meta($post_id, 'nlsb_parent',            intval($_POST['nlsb_parent'] ?? 0));
  update_post_meta($post_id, '_nlsb_layout',          ($_POST['nlsb_layout'] ?? 'a') === 'b' ? 'b':'a');
  update_post_meta($post_id, '_nlsb_leftValue',       preg_replace('/[^0-9.]/','', (string)($_POST['nlsb_leftValue'] ?? '600')));
  update_post_meta($post_id, '_nlsb_leftUnit',        in_array($_POST['nlsb_leftUnit'] ?? 'px', ['px','%'], true) ? $_POST['nlsb_leftUnit'] : 'px');
  update_post_meta($post_id, '_nlsb_caption',         sanitize_text_field($_POST['nlsb_caption'] ?? ''));
  $capV = $_POST['nlsb_capHeightValue'] ?? '';
  update_post_meta($post_id, '_nlsb_capHeightValue',  ($capV === '' ? '' : preg_replace('/[^0-9.]/','', (string)$capV)));
  update_post_meta($post_id, '_nlsb_capHeightUnit',   in_array($_POST['nlsb_capHeightUnit'] ?? 'px', ['px','rem'], true) ? $_POST['nlsb_capHeightUnit'] : 'px');
  update_post_meta($post_id, '_nlsb_accent',          sanitize_hex_color($_POST['nlsb_accent'] ?? '#ffeb00'));
  update_post_meta($post_id, '_nlsb_btnText',         sanitize_text_field($_POST['nlsb_btnText'] ?? ''));
  update_post_meta($post_id, '_nlsb_btnUrl',          esc_url_raw($_POST['nlsb_btnUrl'] ?? ''));

  // NIEUW: Modal tekstkleur opslaan
  $mt = isset($_POST['nlsb_modalText']) ? sanitize_hex_color($_POST['nlsb_modalText']) : '';
  if ($mt) update_post_meta($post_id, '_nlsb_modalText', $mt);
  else delete_post_meta($post_id, '_nlsb_modalText');
});

/* === Shortcode metabox op nlsb_slider bewerkscherm ======================= */
add_action('add_meta_boxes', function () {
  add_meta_box(
    'nlsb_shortcode_box',
    __('Shortcode', 'nlsb'),
    'nlsb_render_shortcode_box',
    'nlsb_slider',
    'side',
    'high'
  );
});

function nlsb_render_shortcode_box($post){
  // Als de slider nog niet is opgeslagen (auto-draft), toon een hint
  $status = get_post_status($post);
  if (!$post->ID || $status === 'auto-draft') {
    echo '<p>'.esc_html__('Sla de slider eerst op om de shortcode te zien.', 'nlsb').'</p>';
    return;
  }

  $id   = (int) $post->ID;
  $slug = $post->post_name; // kan leeg zijn bij concepten
  $scId = '[nlsb_slider id="'.$id.'"]';
  $scSl = $slug ? '[nlsb_slider slug="'.esc_attr($slug).'"]' : '';

  // Unieke IDs om conflicts te voorkomen
  $uid   = 'nlsb_sc_'.uniqid();
  $idInp = $uid.'_id';
  $idBtn = $uid.'_id_btn';
  $slInp = $uid.'_sl';
  $slBtn = $uid.'_sl_btn';
  ?>
  <style>
    .nlsb-sc-wrap .codefield{font-family:monospace;width:100%;box-sizing:border-box}
    .nlsb-sc-wrap .row{display:flex;gap:6px;margin-bottom:8px}
    .nlsb-sc-wrap .row .button{white-space:nowrap}
    .nlsb-sc-wrap .help{color:#666;margin:8px 0 0}
  </style>

 /* === Shortcode (alleen slug) metabox op nlsb_slider bewerkscherm ======== */
add_action('add_meta_boxes', function () {
  add_meta_box(
    'nlsb_shortcode_box',
    __('Shortcode', 'nlsb'),
    'nlsb_render_shortcode_box',
    'nlsb_slider',
    'side',
    'high'
  );
});

function nlsb_render_shortcode_box($post){
  $status = get_post_status($post);
  $slug   = $post->post_name; // Wordt gezet na opslaan/publiceren

  $scSlug = $slug ? '[nlsb_slider slug="'.esc_attr($slug).'"]' : '';
  $uid    = 'nlsb_sc_'.uniqid();
  $slInp  = $uid.'_sl';
  $slBtn  = $uid.'_sl_btn';
  ?>
  <style>
    .nlsb-sc-wrap .codefield{font-family:monospace;width:100%;box-sizing:border-box}
    .nlsb-sc-wrap .row{display:flex;gap:6px;margin-bottom:8px}
    .nlsb-sc-wrap .row .button{white-space:nowrap}
    .nlsb-sc-wrap .help{color:#666;margin:8px 0 0}
  </style>

  <div class="nlsb-sc-wrap">
    <label><strong><?php esc_html_e('Shortcode (slug)', 'nlsb'); ?></strong></label>
    <div class="row">
      <input
        type="text"
        readonly
        class="codefield"
        id="<?php echo esc_attr($slInp); ?>"
        value="<?php echo esc_attr($scSlug); ?>"
        <?php echo $slug ? '' : 'placeholder="[nlsb_slider slug=&quot;voorbeeld&quot;]"'; ?>
      >
      <button
        type="button"
        class="button"
        id="<?php echo esc_attr($slBtn); ?>"
        <?php echo $slug ? '' : 'disabled'; ?>
      ><?php esc_html_e('Kopieer', 'nlsb'); ?></button>
    </div>

    <p class="help">
      <?php
      if ($slug) {
        printf( esc_html__('Slug: %s', 'nlsb'), '<code>'.esc_html($slug).'</code>' );
      } else {
        esc_html_e('Tip: sla de slider eerst op/publiceer om een slug te krijgen.', 'nlsb');
      }
      ?>
    </p>
  </div>

  <script>
    (function(){
      var inp = document.getElementById('<?php echo esc_js($slInp); ?>');
      var btn = document.getElementById('<?php echo esc_js($slBtn); ?>');
      if (!inp || !btn) return;

      btn.addEventListener('click', function(e){
        e.preventDefault();
        var val = inp.value;
        if (!val) return;

        function feedback(){
          var old = btn.textContent;
          btn.textContent = '<?php echo esc_js(__('Gekopieerd!', 'nlsb')); ?>';
          btn.disabled = true;
          setTimeout(function(){
            btn.textContent = old;
            btn.disabled = false;
          }, 1200);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(val).then(feedback).catch(function(){
            inp.removeAttribute('readonly');
            inp.select(); inp.setSelectionRange(0, 99999);
            document.execCommand('copy');
            inp.setAttribute('readonly','readonly');
            feedback();
          });
        } else {
          inp.removeAttribute('readonly');
          inp.select(); inp.setSelectionRange(0, 99999);
          document.execCommand('copy');
          inp.setAttribute('readonly','readonly');
          feedback();
        }
      });
    })();
  </script>
  <?php
}
