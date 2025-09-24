(function () {
  const SELECTOR = '.nlsb-project-slider';

  function init(root) {
    const track = root.querySelector('.nlsb-track');
    if (!track) return;

    const slides = Array.from(track.querySelectorAll('.nlsb-slide'));
    if (!slides.length) return;

    const prevBtn = root.querySelector('.nlsb-nav.prev');
    const nextBtn = root.querySelector('.nlsb-nav.next');
    const dots = Array.from(root.querySelectorAll('.nlsb-dots .nlsb-dot'));

    let index = 0;

    function update() {
      track.style.transform = 'translateX(' + (-100 * index) + '%)';
      slides.forEach((slide, i) => {
        slide.classList.toggle('is-active', i === index);
      });
      dots.forEach((dot, i) => {
        dot.classList.toggle('is-active', i === index);
        dot.setAttribute('aria-selected', i === index ? 'true' : 'false');
      });
      if (prevBtn) prevBtn.disabled = index === 0;
      if (nextBtn) nextBtn.disabled = index === slides.length - 1;
    }

    function goTo(newIndex) {
      const max = slides.length - 1;
      const clamped = Math.max(0, Math.min(max, newIndex));
      if (clamped === index) return;
      index = clamped;
      update();
    }

    update();

    prevBtn && prevBtn.addEventListener('click', () => {
      if (isModalOpen(root)) return;
      goTo(index - 1);
    });
    nextBtn && nextBtn.addEventListener('click', () => {
      if (isModalOpen(root)) return;
      goTo(index + 1);
    });
    dots.forEach((dot, i) => {
      dot.addEventListener('click', () => {
        if (isModalOpen(root)) return;
        goTo(i);
      });
    });

    track.addEventListener('keydown', (e) => {
      if (isModalOpen(root)) return;
      if (e.key === 'ArrowRight') {
        e.preventDefault();
        goTo(index + 1);
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        goTo(index - 1);
      }
    });

    window.addEventListener('resize', () => {
      track.style.transition = 'none';
      track.offsetHeight; // trigger reflow
      track.style.transition = '';
      update();
    });

    setupModal(root);
  }

  function isModalOpen(root) {
    return root.classList.contains('nlsb-modal-open');
  }

  function setupModal(root) {
    const trigger = root.querySelector('.nlsb-info-trigger');
    const modal = root.querySelector('.nlsb-modal');
    const closeBtn = root.querySelector('.nlsb-modal-close');
    const backdrop = root.querySelector('.nlsb-modal-backdrop');

    if (!trigger || !modal || !closeBtn || !backdrop) return;

    const FOCUSABLE = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';
    let lastFocused = null;

    function openModal() {
      if (isModalOpen(root)) return;
      lastFocused = document.activeElement;
      root.classList.add('nlsb-modal-open');
      modal.hidden = false;
      backdrop.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      const focusTarget = modal.querySelector(FOCUSABLE);
      (focusTarget || closeBtn).focus({ preventScroll: true });
      document.addEventListener('keydown', onKeydown);
    }

    function closeModal() {
      if (!isModalOpen(root)) return;
      root.classList.remove('nlsb-modal-open');
      modal.hidden = true;
      backdrop.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      document.removeEventListener('keydown', onKeydown);
      if (lastFocused && typeof lastFocused.focus === 'function') {
        lastFocused.focus({ preventScroll: true });
      } else {
        trigger.focus({ preventScroll: true });
      }
    }

    function trapFocus(e) {
      const nodes = Array.from(modal.querySelectorAll(FOCUSABLE)).filter(el => el.offsetParent !== null);
      if (!nodes.length) {
        e.preventDefault();
        closeBtn.focus({ preventScroll: true });
        return;
      }
      const first = nodes[0];
      const last = nodes[nodes.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus({ preventScroll: true });
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus({ preventScroll: true });
      }
    }

    function onKeydown(e) {
      if (e.key === 'Escape') {
        e.preventDefault();
        closeModal();
      } else if (e.key === 'Tab') {
        trapFocus(e);
      }
    }

    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      if (isModalOpen(root)) closeModal();
      else openModal();
    });

    closeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      closeModal();
    });

    backdrop.addEventListener('click', closeModal);
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(SELECTOR).forEach(init);
  });
})();
