/* global NLSB, jQuery, wp */
jQuery(function ($) {
  const $builder = $('.nlsb-builder');
  if (!$builder.length) return;

  const $list = $('#nlsb-list');
  const sliderId = $builder.data('slider');

  // ---------------------------
  // Helpers
  // ---------------------------
  function send(action, payload, done) {
    $.post(NLSB.ajax, Object.assign({ action, nonce: NLSB.nonce }, payload || {}), done);
  }

  function currentIds() {
    return $list.children('.nlsb-item').map(function () { return $(this).data('id'); }).get();
  }

  function updateOrder() {
    const ids = currentIds();
    send('nlsb_update_order', { ids }, function () { /* no-op */ });
  }

  function closeAllQuick(except) {
    $list.find('.nlsb-quick-wrap').not(except).slideUp(120);
  }

  function toggleLayoutVisibility($quick) {
    const isB = $quick.find('.nlsb-q-layout').val() === 'b';
    $quick.find('.nlsb-only-b').toggle(isB);
    $quick.find('.nlsb-only-a').toggle(!isB);
  }

  function makeSortable() {
    $list.sortable({
      handle: '.handle',
      items: '.nlsb-item',
      update: updateOrder
    });
  }

  function ensureQuickLoaded($item, cb) {
    const $wrap = $item.find('.nlsb-quick-wrap');
    if ($wrap.data('loaded')) {
      cb && cb($wrap.find('.nlsb-quick'));
      return;
    }
    // Inject template
    const tpl = $('#nlsb-quick-template').html();
    if (!tpl) {
      console.error('nlsb-quick-template ontbreekt.');
      return;
    }
    $wrap.html(tpl);
    const $quick = $wrap.find('.nlsb-quick');

    // Fetch data
    const id = $item.data('id');
    send('nlsb_get_slide', { id }, function (res) {
      if (!res || !res.success) {
        alert('Kon slide niet laden.');
        return;
      }
      const d = res.data || {};
      $quick.find('.nlsb-q-title').val(d.title || '');
      $quick.find('.nlsb-q-body').val(d.body || '');
      $quick.find('.nlsb-q-layout').val(d.layout || 'a');
      $quick.find('.nlsb-q-leftValue').val(d.leftValue || '600');
      $quick.find('.nlsb-q-leftUnit').val(d.leftUnit || 'px');
      $quick.find('.nlsb-q-caption').val(d.caption || '');
      $quick.find('.nlsb-q-capHeightValue').val(d.capHeightValue === null ? '' : d.capHeightValue);
      $quick.find('.nlsb-q-capHeightUnit').val(d.capHeightUnit || 'px');
      $quick.find('.nlsb-q-accent').val(d.accent || '#ffeb00');
      $quick.find('.nlsb-q-btnText').val(d.btnText || '');
      $quick.find('.nlsb-q-btnUrl').val(d.btnUrl || '');
      $quick.find('.nlsb-q-thumb-id').val(d.thumbId || 0);

      if (d.thumbUrl) {
        $quick.find('.nlsb-q-thumb').attr('src', d.thumbUrl).show();
        $quick.find('.nlsb-q-remove').show();
      }

      toggleLayoutVisibility($quick);
      $wrap.data('loaded', true);
      cb && cb($quick);
    });
  }

  // ---------------------------
  // Init
  // ---------------------------
  makeSortable();

  // ---------------------------
  // Add slide
  // ---------------------------
  $('#nlsb-add-slide').on('click', function () {
    const title = $('#nlsb-new-title').val();
    const $btn = $(this).prop('disabled', true).text('Toevoegen…');

    send('nlsb_add_slide', { slider: sliderId, title }, function (res) {
      $btn.prop('disabled', false).text('Slide toevoegen');
      if (res && res.success && res.data && res.data.html) {
        const $new = $(res.data.html);
        $list.append($new);
        makeSortable();
        $('#nlsb-new-title').val('');

        // Open direct quick edit voor de nieuwe slide
        setTimeout(() => {
          $new.find('.nlsb-edit').trigger('click');
          // Scroll naar de nieuwe
          $new[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 50);
      } else {
        alert('Kon slide niet toevoegen.');
      }
    });
  });

  // ---------------------------
  // Delete slide
  // ---------------------------
  $list.on('click', '.nlsb-del', function () {
    if (!confirm('Deze slide verwijderen?')) return;
    const $item = $(this).closest('.nlsb-item');
    const id = $item.data('id');
    send('nlsb_delete_slide', { id }, function (res) {
      if (res && res.success) {
        $item.slideUp(120, function () {
          $(this).remove();
          updateOrder();
        });
      } else {
        alert('Verwijderen mislukt.');
      }
    });
  });

  // ---------------------------
  // Quick Edit open/close
  // ---------------------------
  $list.on('click', '.nlsb-edit', function () {
    const $item = $(this).closest('.nlsb-item');
    const $wrap = $item.find('.nlsb-quick-wrap');

    if ($wrap.is(':visible')) {
      $wrap.slideUp(120);
      return;
    }

    closeAllQuick($wrap);

    ensureQuickLoaded($item, function () {
      $wrap.slideDown(120);
    });
  });

  $list.on('click', '.nlsb-q-cancel', function () {
    $(this).closest('.nlsb-item').find('.nlsb-quick-wrap').slideUp(120);
  });

  // ---------------------------
  // Quick Edit: layout toggle
  // ---------------------------
  $list.on('change', '.nlsb-q-layout', function () {
    toggleLayoutVisibility($(this).closest('.nlsb-quick'));
  });

  // ---------------------------
  // Quick Edit: media kiezen/verwijderen
  // ---------------------------
  $list.on('click', '.nlsb-q-choose', function (e) {
    e.preventDefault();
    const $q = $(this).closest('.nlsb-quick');
    const frame = wp.media({ title: 'Kies afbeelding', multiple: false, library: { type: 'image' } });
    frame.on('select', function () {
      const m = frame.state().get('selection').first().toJSON();
      $q.find('.nlsb-q-thumb-id').val(m.id);
      $q.find('.nlsb-q-thumb').attr('src', (m.sizes && m.sizes.thumbnail ? m.sizes.thumbnail.url : m.url)).show();
      $q.find('.nlsb-q-remove').show();
    });
    frame.open();
  });

  $list.on('click', '.nlsb-q-remove', function () {
    const $q = $(this).closest('.nlsb-quick');
    $q.find('.nlsb-q-thumb-id').val(0);
    $q.find('.nlsb-q-thumb').hide().attr('src', '');
    $(this).hide();
  });

  // ---------------------------
  // Quick Edit: opslaan
  // ---------------------------
  $list.on('click', '.nlsb-q-save', function () {
    const $item = $(this).closest('.nlsb-item');
    const $q = $(this).closest('.nlsb-quick');
    const id = $item.data('id');

    const payload = {
      id,
      title: $q.find('.nlsb-q-title').val(),
      body: $q.find('.nlsb-q-body').val(),
      layout: $q.find('.nlsb-q-layout').val(),
      leftValue: $q.find('.nlsb-q-leftValue').val(),
      leftUnit: $q.find('.nlsb-q-leftUnit').val(),
      caption: $q.find('.nlsb-q-caption').val(),
      capHeightValue: $q.find('.nlsb-q-capHeightValue').val(),
      capHeightUnit: $q.find('.nlsb-q-capHeightUnit').val(),
      accent: $q.find('.nlsb-q-accent').val(),
      btnText: $q.find('.nlsb-q-btnText').val(),
      btnUrl: $q.find('.nlsb-q-btnUrl').val(),
      thumbId: $q.find('.nlsb-q-thumb-id').val()
    };

    const $btn = $(this).prop('disabled', true).text('Opslaan…');

    send('nlsb_save_slide_quick', payload, function (res) {
      $btn.prop('disabled', false).text('Opslaan');

      if (!res || !res.success) {
        alert('Opslaan mislukt.');
        return;
      }

      // UI updaten
      if (res.data && res.data.title !== undefined) {
        $item.find('.meta .title').text(res.data.title || '(zonder titel)');
      }
      if (res.data && 'thumbUrl' in res.data) {
        if (res.data.thumbUrl) {
          $item.find('.thumb').html('<img src="' + res.data.thumbUrl + '" alt="">');
        } else {
          $item.find('.thumb').html('<span class="noimg">Geen afbeelding</span>');
        }
      }

      // Feedback + sluiten
      $btn.text('Opgeslagen');
      setTimeout(() => { $btn.text('Opslaan'); }, 900);
      $item.find('.nlsb-quick-wrap').slideUp(120);
    });
  });

});
