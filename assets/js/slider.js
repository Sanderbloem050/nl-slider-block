(function () {
  function init(root) {
    const track = root.querySelector('.rucs-track');
    if (!track) return;

    const prev = root.querySelector('.nav.prev');
    const next = root.querySelector('.nav.next');
    const dots = [...root.querySelectorAll('.rucs-dots .dot')];

    // ---- Slides + infinite clones ----
    let originals = [...track.querySelectorAll('.rucs-slide')];
    const L = originals.length;
    if (L === 0) return;

    if (L > 1) {
      const firstClone = originals[0].cloneNode(true);
      const lastClone  = originals[L - 1].cloneNode(true);
      firstClone.dataset.clone = 'true';
      firstClone.setAttribute('aria-hidden','true');
      lastClone.dataset.clone  = 'true';
      lastClone.setAttribute('aria-hidden','true');
      track.insertBefore(lastClone, originals[0]);
      track.appendChild(firstClone);
    }
    let slides = [...track.querySelectorAll('.rucs-slide')];

    // ---- Index helpers ----
    const rawIndex  = () => Math.round(track.scrollLeft / track.clientWidth);
    const toRaw     = (logical) => (L > 1 ? logical + 1 : logical);
    const toLogical = (raw) => {
      if (L <= 1) return raw;
      let i = raw - 1;
      if (i < 0) i = L - 1;
      if (i >= L) i = 0;
      return i;
    };

    function jumpToRaw(r){
      const b = track.style.scrollBehavior;
      track.style.scrollBehavior = 'auto';
      track.scrollTo({ left: r * track.clientWidth, behavior: 'auto' });
      setTimeout(() => (track.style.scrollBehavior = b || ''), 0);
    }
    function goLogical(i){
      if (L <= 1) return;
      const r = toRaw((i + L) % L);
      track.scrollTo({ left: r * track.clientWidth, behavior: 'smooth' });
    }
    function setActiveDot(i){
      dots.forEach((d, di) => {
        d.classList.toggle('is-active', di === i);
        d.setAttribute('aria-selected', di === i ? 'true' : 'false');
      });
    }

    // ====== Globale info-modal (slider-breed) ======
    const infoToggle = root.querySelector('.rucs-info-toggle');
    const infoModal  = root.querySelector('#rucs-info-modal');
    const infoClose  = root.querySelector('.rucs-info-close');

    function openInfo(){
      if (!infoToggle || !infoModal) return;
      root.classList.add('modal-open');
      infoToggle.setAttribute('aria-expanded','true');
    }
    function closeInfo(){
      if (!infoToggle || !infoModal) return;
      root.classList.remove('modal-open');
      infoToggle.setAttribute('aria-expanded','false');
    }

    // ➕ Toggle: voorkom dat andere lagen de klik 'opeten'
    infoToggle?.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      root.classList.contains('modal-open') ? closeInfo() : openInfo();
    });
    infoClose?.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeInfo();
    });
    root.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeInfo();
    });

    // ---- Active states (sluit ook modal + per-slide states) ----
    function setActiveSlideClasses(raw){
      const logical = toLogical(raw);
      slides.forEach(s => {
        s.classList.remove('is-active-visible','bar-open'); // sluit mobiel paneel Type A
        s.querySelector('.accent-toggle')?.setAttribute('aria-expanded','false');
      });
      originals.forEach(s => s.classList.remove('is-active'));

      if (slides[raw]) slides[raw].classList.add('is-active-visible');
      if (originals[logical]) originals[logical].classList.add('is-active');

      setActiveDot(logical);
      closeInfo(); // bij wissel: globale modal dicht
    }

    // ---- Startpositie ----
    if (L > 1) jumpToRaw(1);
    setActiveSlideClasses(L > 1 ? 1 : 0);

    // ---- Navigatie ----
    prev && prev.addEventListener('click', () => {
      const i = toLogical(rawIndex());
      goLogical((i - 1 + L) % L);
    });
    next && next.addEventListener('click', () => {
      const i = toLogical(rawIndex());
      goLogical((i + 1) % L);
    });
    dots.forEach((d, di) => d.addEventListener('click', () => goLogical(di)));

    // ---- Scroll-sync (infinite) ----
    let timer;
    track.addEventListener('scroll', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        let r = rawIndex();
        if (L > 1) {
          if (r === 0) { jumpToRaw(L); r = L; }
          else if (r === L + 1) { jumpToRaw(1); r = 1; }
        }
        setActiveSlideClasses(r);
      }, 80);
    });

    // ---- Keyboard pijlen ----
    root.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') next?.click();
      if (e.key === 'ArrowLeft')  prev?.click();
    });

    // ============================================================
    // Accent-balk toggle (MOBIEL) – gescoped per slider (Type A)
    // ============================================================
    const mq = window.matchMedia('(max-width: 782px)');

    function ensureAccentToggle(slide){
      if (slide.querySelector('.accent-toggle')) return;
      const btn = document.createElement('button');
      btn.className = 'accent-toggle';
      btn.type = 'button';
      btn.setAttribute('aria-label', 'Toon accentbalk');
      btn.setAttribute('aria-expanded', 'false');
      btn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5l8 7-8 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      btn.addEventListener('click', () => {
        const open = slide.classList.toggle('bar-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      slide.appendChild(btn);
    }

    function removeAccentToggle(slide){
      slide.classList.remove('bar-open');
      slide.querySelector('.accent-toggle')?.remove();
    }

    function applyAccentForViewport(){
      root.querySelectorAll('.rucs-slide.type_a').forEach((slide) => {
        mq.matches ? ensureAccentToggle(slide) : removeAccentToggle(slide);
      });
    }

    applyAccentForViewport();
    mq.addEventListener ? mq.addEventListener('change', applyAccentForViewport)
                        : mq.addListener(applyAccentForViewport);

    // ---- Resize: positie en toggles opnieuw toepassen ----
    window.addEventListener('resize', () => {
      const logical = toLogical(rawIndex());
      jumpToRaw(toRaw(logical));
      setActiveSlideClasses(toRaw(logical));
      applyAccentForViewport();
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.rucs-slider').forEach(init);
  });
})();
