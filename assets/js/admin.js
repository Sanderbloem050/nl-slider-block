/* global jQuery, wp */
jQuery(function ($) {
  const strings = Object.assign({
    chooseImage: 'Kies afbeelding',
    replaceImage: 'Wijzig afbeelding',
    removeImage: 'Verwijderen',
    confirmDelete: 'Slide verwijderen?',
    slideLabel: 'Slide',
    noImage: 'Nog geen afbeelding geselecteerd',
    noLogo: 'Geen logo geselecteerd',
    expand: 'Uitklappen',
    collapse: 'Inklappen',
  }, window.NLSBProject || {});

  const $meta = $('.nlsb-meta');
  if (!$meta.length) return;

  const $slidesWrap = $('#nlsb-project-slides');
  const slideTemplate = ($('#nlsb-slide-template').html() || '').trim();

  function mediaFallback($wrapper) {
    const type = $wrapper.data('type');
    const text = type === 'logo' ? strings.noLogo : strings.noImage;
    return '<span class="nlsb-media-placeholder">' + text + '</span>';
  }

  function setButtonDefault($btn) {
    const def = $btn.data('defaultLabel') || strings.chooseImage;
    $btn.text(def);
  }

  function ensureButtonDefaults(context) {
    $(context).find('.nlsb-media-select').each(function () {
      const $btn = $(this);
      if (!$btn.data('defaultLabel')) {
        $btn.data('defaultLabel', $btn.text());
      }
      const $wrapper = $btn.closest('.nlsb-media');
      const hasValue = parseInt($wrapper.find('input[data-field="' + $btn.data('field') + '"]').val(), 10) > 0;
      if (hasValue) {
        $btn.text(strings.replaceImage);
      } else {
        setButtonDefault($btn);
      }
    });
  }

  function renumberSlides() {
    $slidesWrap.children('.nlsb-slide').each(function (index) {
      const $slide = $(this);
      $slide.attr('data-index', index);
      $slide.find('[data-field]').each(function () {
        const $field = $(this);
        const field = $field.data('field');
        if (!field) return;
        const name = 'nlsb_slides[' + index + '][' + field + ']';
        $field.attr('name', name);
        if ($field.attr('type') !== 'hidden') {
          const id = 'nlsb-slide-' + field + '-' + index;
          $field.attr('id', id);
          const $label = $field.closest('.nlsb-field').find('label');
          if ($label.length) {
            $label.attr('for', id);
          }
        }
      });
    });
  }

  function updateSlideLabel($slide) {
    const text = ($slide.find('.nlsb-slide-title').val() || '').trim();
    const label = text !== '' ? text : strings.slideLabel;
    $slide.find('.nlsb-slide-title-text').text(label);
  }

  function bindSlideEvents($slide) {
    updateSlideLabel($slide);
    ensureButtonDefaults($slide);
  }

  ensureButtonDefaults(document);

  $meta.on('click', '.nlsb-media-select', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const field = $btn.data('field');
    const $wrapper = $btn.closest('.nlsb-media');
    if (!$wrapper.length || !wp || !wp.media) return;

    const frame = wp.media({
      title: strings.chooseImage,
      multiple: false,
      library: { type: 'image' },
    });

    frame.on('select', function () {
      const attachment = frame.state().get('selection').first().toJSON();
      const size = attachment.sizes && (attachment.sizes.large || attachment.sizes.medium || attachment.sizes.full);
      const url = size ? size.url : attachment.url;

      $wrapper.find('input[data-field="' + field + '"]').val(attachment.id);
      $wrapper.find('.nlsb-media-preview').html('<img src="' + url + '" alt="" />');
      $wrapper.find('.nlsb-media-remove').show();
      $btn.text(strings.replaceImage);
    });

    frame.open();
  });

  $meta.on('click', '.nlsb-media-remove', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const field = $btn.data('field');
    const $wrapper = $btn.closest('.nlsb-media');
    $wrapper.find('input[data-field="' + field + '"]').val('');
    $wrapper.find('.nlsb-media-preview').html(mediaFallback($wrapper));
    $btn.hide();
    const $select = $wrapper.find('.nlsb-media-select[data-field="' + field + '"]');
    setButtonDefault($select);
  });

  if ($slidesWrap.length) {
    $slidesWrap.sortable({
      handle: '.nlsb-slide-handle',
      items: '.nlsb-slide',
      stop: renumberSlides,
    });

    renumberSlides();
    $slidesWrap.children('.nlsb-slide').each(function () {
      bindSlideEvents($(this));
    });

    $slidesWrap.on('input change', '.nlsb-slide-title', function () {
      const $slide = $(this).closest('.nlsb-slide');
      updateSlideLabel($slide);
    });

    $slidesWrap.on('click', '.nlsb-slide-remove', function () {
      if (strings.confirmDelete && !window.confirm(strings.confirmDelete)) return;
      const $slide = $(this).closest('.nlsb-slide');
      $slide.slideUp(120, function () {
        $(this).remove();
        renumberSlides();
      });
    });

    $slidesWrap.on('click', '.nlsb-slide-toggle', function () {
      const $btn = $(this);
      const $slide = $btn.closest('.nlsb-slide');
      const body = $slide.find('.nlsb-slide-body');
      const collapsed = !$slide.hasClass('is-collapsed');
      if (collapsed) {
        $slide.addClass('is-collapsed');
        body.slideUp(150);
        $btn.attr('aria-expanded', 'false').text(strings.expand);
      } else {
        $slide.removeClass('is-collapsed');
        body.slideDown(150);
        $btn.attr('aria-expanded', 'true').text(strings.collapse);
      }
    });

    $('#nlsb-add-slide').on('click', function (e) {
      e.preventDefault();
      if (!slideTemplate) return;
      const index = $slidesWrap.children('.nlsb-slide').length;
      const html = slideTemplate.replace(/__INDEX__/g, index);
      const $slide = $(html);
      $slidesWrap.append($slide);
      renumberSlides();
      bindSlideEvents($slide);
      $slide.find('.nlsb-slide-body').show();
      $slide.find('.nlsb-slide-toggle').attr('aria-expanded', 'true').text(strings.collapse);
      $slide.find('.nlsb-media-remove').hide();
      $slide.find('.nlsb-media-preview').each(function () {
        const $wrapper = $(this).closest('.nlsb-media');
        $(this).html(mediaFallback($wrapper));
      });
      setTimeout(function () {
        $slide.find('.nlsb-slide-title').focus();
      }, 50);
    });
  }
});
