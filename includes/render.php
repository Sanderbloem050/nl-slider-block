<?php
if (!defined('ABSPATH')) exit;

add_shortcode('nlsb_slider', 'nlsb_render_project_slider');

function nlsb_render_project_slider($atts){
  $atts = shortcode_atts([
    'id'   => '',
    'slug' => '',
  ], $atts, 'nlsb_slider');

  $project_id = 0;
  if ($atts['id']) {
    $project_id = intval($atts['id']);
  } elseif ($atts['slug']) {
    $project = get_page_by_path(sanitize_title($atts['slug']), OBJECT, 'projects');
    if ($project) {
      $project_id = $project->ID;
    }
  } elseif (is_singular('projects')) {
    $project_id = get_queried_object_id();
  }

  if (!$project_id) {
    return '';
  }

  $project_post = get_post($project_id);
  if (!$project_post || $project_post->post_type !== 'projects') {
    return '';
  }

  $hero   = nlsb_project_get_hero($project_id);
  $modal  = nlsb_project_get_modal($project_id);
  $slides_meta = nlsb_project_get_slides($project_id);

  $slides = [];
  if ($hero['image_id']) {
    $slides[] = [
      'type'     => 'hero',
      'image_id' => $hero['image_id'],
      'image'    => wp_get_attachment_image_url($hero['image_id'], 'full'),
    ];
  }
  foreach ($slides_meta as $entry) {
    $slides[] = [
      'type'     => 'content',
      'image_id' => $entry['image_id'],
      'image'    => $entry['image_id'] ? wp_get_attachment_image_url($entry['image_id'], 'full') : '',
      'title'    => $entry['title'] ?? '',
      'body'     => $entry['body'] ?? '',
    ];
  }

  if (!$slides) {
    return '';
  }

  $logo_url = $hero['logo_id'] ? wp_get_attachment_image_url($hero['logo_id'], 'medium') : '';
  $project_title = get_the_title($project_post);
  $modal_title   = $modal['title'] !== '' ? $modal['title'] : $project_title;
  $modal_body    = $modal['body'] !== '' ? apply_filters('the_content', $modal['body']) : '';

  global $post;
  $saved_post = $post;
  $post = $project_post;
  setup_postdata($post);
  $prev_project = get_adjacent_post(false, '', true, '');
  $next_project = get_adjacent_post(false, '', false, '');
  $post = $saved_post;
  if ($saved_post instanceof WP_Post) {
    setup_postdata($post);
  } else {
    wp_reset_postdata();
  }

  wp_enqueue_style('nlsb-slider');
  wp_enqueue_script('nlsb-slider');

  $wrap_id = 'nlsb-slider-'.uniqid();
  $slide_count = count($slides);
  ob_start();
  ?>
  <div id="<?php echo esc_attr($wrap_id); ?>" class="nlsb-project-slider" data-slides="<?php echo esc_attr($slide_count); ?>">
    <div class="nlsb-slider-header">
      <div class="nlsb-header-left">
        <?php if ($logo_url): ?>
          <img class="nlsb-logo" src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($project_title); ?>">
        <?php endif; ?>
        <div class="nlsb-project-titles">
          <span class="nlsb-project-name"><?php echo esc_html($project_title); ?></span>
          <button type="button" class="nlsb-info-trigger" aria-haspopup="dialog" aria-expanded="false">
            <span><?php esc_html_e('Project info', 'nlsb'); ?></span>
            <span class="nlsb-info-icon" aria-hidden="true">i</span>
          </button>
        </div>
      </div>
    </div>

    <div class="nlsb-slider-frame">
      <button type="button" class="nlsb-nav prev" aria-label="<?php esc_attr_e('Vorige slide', 'nlsb'); ?>">
        <span aria-hidden="true">&#x25C0;</span>
      </button>

      <div class="nlsb-track" tabindex="0" aria-roledescription="carousel">
        <?php foreach ($slides as $index => $slide): ?>
          <?php if ($slide['type'] === 'hero'): ?>
            <section class="nlsb-slide type-a" data-index="<?php echo esc_attr($index); ?>">
              <?php if ($slide['image']): ?>
                <img src="<?php echo esc_url($slide['image']); ?>" alt="" class="nlsb-slide-image" />
              <?php endif; ?>
            </section>
          <?php else: ?>
            <section class="nlsb-slide type-b" data-index="<?php echo esc_attr($index); ?>">
              <div class="nlsb-slide-inner">
                <div class="nlsb-slide-text">
                  <?php if (!empty($slide['title'])): ?>
                    <h3 class="nlsb-slide-title"><?php echo esc_html($slide['title']); ?></h3>
                  <?php endif; ?>
                  <?php if (!empty($slide['body'])): ?>
                    <div class="nlsb-slide-body"><?php echo wpautop(wp_kses_post($slide['body'])); ?></div>
                  <?php endif; ?>
                </div>
                <div class="nlsb-slide-media">
                  <?php if ($slide['image']): ?>
                    <img src="<?php echo esc_url($slide['image']); ?>" alt="" />
                  <?php endif; ?>
                </div>
              </div>
            </section>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <button type="button" class="nlsb-nav next" aria-label="<?php esc_attr_e('Volgende slide', 'nlsb'); ?>">
        <span aria-hidden="true">&#x25B6;</span>
      </button>
    </div>

    <?php if ($slide_count > 1): ?>
      <div class="nlsb-dots" role="tablist" aria-label="<?php esc_attr_e('Slider paginatie', 'nlsb'); ?>">
        <?php for ($i = 0; $i < $slide_count; $i++): ?>
          <button class="nlsb-dot<?php echo $i === 0 ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr($i); ?>" role="tab" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"></button>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

    <div class="nlsb-project-pagination">
      <div class="nlsb-project-prev">
        <?php if ($prev_project): ?>
          <a href="<?php echo esc_url(get_permalink($prev_project)); ?>" class="nlsb-project-link">
            <span class="nlsb-project-arrow" aria-hidden="true">&#x25C0;</span>
            <span><?php esc_html_e('Vorig project', 'nlsb'); ?></span>
          </a>
        <?php else: ?>
          <span class="nlsb-project-link is-disabled">
            <span class="nlsb-project-arrow" aria-hidden="true">&#x25C0;</span>
            <span><?php esc_html_e('Vorig project', 'nlsb'); ?></span>
          </span>
        <?php endif; ?>
      </div>
      <div class="nlsb-project-next">
        <?php if ($next_project): ?>
          <a href="<?php echo esc_url(get_permalink($next_project)); ?>" class="nlsb-project-link">
            <span><?php esc_html_e('Volgende project', 'nlsb'); ?></span>
            <span class="nlsb-project-arrow" aria-hidden="true">&#x25B6;</span>
          </a>
        <?php else: ?>
          <span class="nlsb-project-link is-disabled">
            <span><?php esc_html_e('Volgende project', 'nlsb'); ?></span>
            <span class="nlsb-project-arrow" aria-hidden="true">&#x25B6;</span>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <div class="nlsb-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($wrap_id); ?>-modal-title" hidden>
      <div class="nlsb-modal-dialog">
        <button type="button" class="nlsb-modal-close" aria-label="<?php esc_attr_e('Sluiten', 'nlsb'); ?>">&times;</button>
        <?php if ($modal_title): ?>
          <h2 id="<?php echo esc_attr($wrap_id); ?>-modal-title" class="nlsb-modal-title"><?php echo esc_html($modal_title); ?></h2>
        <?php endif; ?>
        <div class="nlsb-modal-body">
          <?php echo $modal_body ?: '<p>'.esc_html__('Geen omschrijving beschikbaar.', 'nlsb').'</p>'; ?>
        </div>
      </div>
    </div>
    <div class="nlsb-modal-backdrop" hidden></div>
  </div>
  <?php
  return ob_get_clean();
}
