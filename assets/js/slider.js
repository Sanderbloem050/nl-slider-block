/* NL Slider Block – frontend */
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

    // ====== Globale info-modal (alleen via de pill) ======
    const infoModal = root.querySelector('#rucs-info-modal');
    const infoClose = root.querySelector('.rucs-info-close');
    const infoPills = root.querySelectorAll('.info-pill');
    const infoSlot  = root.querySelector('.rucs-info-slot');

    function isOpen(){ return root.classList.contains('modal-open'); }
    function openInfo(){
      if (!infoModal) return;
      root.classList.add('modal-open');
    }
    function closeInfo(){
      if (!infoModal) return;
      root.classList.remove('modal-open');
    }

    // Meet & style modal op basis van de geklikte pill/slide
    function positionModalFromPill(pill){
      const slide = pill.closest('.rucs-slide');
      const rootRect  = root.getBoundingClientRect();
      const pillRect  = pill.getBoundingClientRect();
      const slideCS   = getComputedStyle(slide);

      // 1) Breedte = pill
      root.style.setProperty('--ru-modal-w', pillRect.width + 'px');

      // 2) Positie aan linkerrand slider, net onder topbar
      const barH = parseFloat(slideCS.getPropertyValue('--ru-info-h')) || 52;
      const top  = window.scrollY + rootRect.top + barH + 8;
      const left = window.scrollX + rootRect.left;
      root.style.setProperty('--nlsb-modal-top',  top  + 'px');
      root.style.setProperty('--nlsb-modal-left', left + 'px');

      // 3) Kleuren uit slide vars
      const bg   =
        slideCS.getPropertyValue('--ru-info-bg')?.trim() ||
        slideCS.getPropertyValue('--ru-accent')?.trim() ||
        '#ffffff';
      const text =
        slideCS.getPropertyValue('--ru-modal-text')?.trim() ||
        '#111111';
      root.style.setProperty('--ru-modal-bg',   bg);
      root.style.setProperty('--ru-modal-text', text);

      // 4) Inhoud (alleen wanneer slider enkel Type A heeft)
      if (root.dataset.perSlideModal === '1' && infoSlot) {
        const tpl = slide.querySelector('.rucs-modal-tpl');
        if (tpl) {
          infoSlot.innerHTML = tpl.innerHTML; // content komt uit server-side render
        }
      }
    }

    // Toggle via pill
    infoPills.forEach((pill) => {
      const toggle = (e) => {
        e.preventDefault();
        if (isOpen()) {
          closeInfo();
        } else {
          positionModalFromPill(pill);
          openInfo();
        }
      };
      pill.addEventListener('click', toggle);
      pill.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') toggle(e);
      });
    });

    // Sluiten via X
    infoClose?.addEventListener('click', (e) => { e.preventDefault(); closeInfo(); });
    // Sluiten via Esc
    root.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeInfo(); });
    // Sluiten bij klik BUITEN de modal (overlay/slider)
    root.addEventListener('click', (e) => {
      if (!isOpen()) return;
      const insideModal = e.target.closest('.rucs-info-modal');
      const onPill      = e.target.closest('.info-pill');
      if (!insideModal && !onPill) closeInfo();
    }, true);

    // ---- Active states ----
    function setActiveSlideClasses(raw){
      const logical = toLogical(raw);
      slides.forEach(s => {
        s.classList.remove('is-active-visible','bar-open'); // reset evt. mobiele state
        s.querySelector('.accent-toggle')?.setAttribute('aria-expanded','false');
      });
      originals.forEach(s => s.classList.remove('is-active'));

      if (slides[raw]) slides[raw].classList.add('is-active-visible');
      if (originals[logical]) originals[logical].classList.add('is-active');

      setActiveDot(logical);
      // // Wil je altijd modal sluiten bij slide-wissel?
      // if (isOpen()) closeInfo();
    }

    // ---- Startpositie ----
    if (L > 1) jumpToRaw(1);
    setActiveSlideClasses(L > 1 ? 1 : 0);

    // ---- Navigatie ----
    prev && prev.addEventListener('click', () => {
      if (isOpen()) return; // blokkeer als modal open
      const i = toLogical(rawIndex());
      goLogical((i - 1 + L) % L);
    });
    next && next.addEventListener('click', () => {
      if (isOpen()) return;
      const i = toLogical(rawIndex());
      goLogical((i + 1) % L);
    });
    dots.forEach((d, di) => d.addEventListener('click', () => {
      if (isOpen()) return;
      goLogical(di);
    }));

    // ---- Scroll-sync (infinite) ----
    let timer;
    track.addEventListener('scroll', () => {
      if (isOpen()) return; // negeer zolang modal open is
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
      if (isOpen()) return;
      if (e.key === 'ArrowRight') next?.click();
      if (e.key === 'ArrowLeft')  prev?.click();
    });

    // ============================================================
    // Accent-balk toggle (MOBIEL) – Type A
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
    if (mq.addEventListener) mq.addEventListener('change', applyAccentForViewport);
    else if (mq.addListener) mq.addListener(applyAccentForViewport);

    // ---- Resize: positie en toggles opnieuw toepassen ----
    window.addEventListener('resize', () => {
      if (isOpen()) return; // laat modal met rust
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
