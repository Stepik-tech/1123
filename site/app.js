/* NEBULA — интерактив: GSAP + ScrollTrigger + Lenis (плагины из vendor/) */
(function () {
  const { gsap, ScrollTrigger } = window;
  gsap.registerPlugin(ScrollTrigger);

  /* ---------- плавный скролл (Lenis) ---------- */
  let lenis = null;
  if (window.Lenis) {
    lenis = new Lenis({ lerp: 0.12, wheelMultiplier: 1 });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(t => lenis && lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);
    // якорные ссылки через lenis
    document.querySelectorAll('a[href^="#"]').forEach(a => {
      a.addEventListener('click', e => {
        const el = document.querySelector(a.getAttribute('href'));
        if (el) { e.preventDefault(); lenis ? lenis.scrollTo(el, { offset: -60 }) : el.scrollIntoView(); closeMenu(); }
      });
    });
  }

  /* ---------- прелоадер ---------- */
  const loader = document.getElementById('loader');
  const pct = { v: 0 };
  gsap.to(pct, {
    v: 100, duration: 1.6, ease: 'power2.inOut',
    onUpdate() {
      document.getElementById('loaderPct').textContent = Math.round(pct.v);
      document.getElementById('loaderBar').style.width = pct.v + '%';
    },
    onComplete() {
      loader.classList.add('is-done');
      introTL.play();
    }
  });

  /* ---------- интро-анимация hero ---------- */
  const introTL = gsap.timeline({ paused: true });
  introTL
    .to('.hero__title .word', { y: 0, duration: 1.1, stagger: 0.12, ease: 'power4.out' })
    .from('.hero__tag span', { y: '110%', duration: 0.7, ease: 'power3.out' }, 0.3)
    .to('.reveal-fade', { opacity: 1, y: 0, duration: 0.8, stagger: 0.15, ease: 'power3.out' }, 0.7);

  /* ---------- кастомный курсор ---------- */
  const dot = document.getElementById('cursorDot');
  const ring = document.getElementById('cursorRing');
  const mx = gsap.quickTo(ring, 'x', { duration: 0.35, ease: 'power3' });
  const my = gsap.quickTo(ring, 'y', { duration: 0.35, ease: 'power3' });
  addEventListener('pointermove', e => {
    gsap.set(dot, { x: e.clientX, y: e.clientY });
    mx(e.clientX); my(e.clientY);
  });
  document.querySelectorAll('[data-cursor], a, button').forEach(el => {
    el.addEventListener('pointerenter', () => ring.classList.add('is-hover'));
    el.addEventListener('pointerleave', () => ring.classList.remove('is-hover'));
  });

  /* ---------- магнитные кнопки ---------- */
  document.querySelectorAll('.btn--magnetic').forEach(btn => {
    btn.addEventListener('pointermove', e => {
      const r = btn.getBoundingClientRect();
      gsap.to(btn, {
        x: (e.clientX - r.left - r.width / 2) * 0.25,
        y: (e.clientY - r.top - r.height / 2) * 0.35,
        duration: 0.4, ease: 'power3.out'
      });
    });
    btn.addEventListener('pointerleave', () => gsap.to(btn, { x: 0, y: 0, duration: 0.6, ease: 'elastic.out(1,0.4)' }));
  });

  /* ---------- 3D tilt карточек услуг ---------- */
  document.querySelectorAll('[data-tilt]').forEach(card => {
    card.addEventListener('pointermove', e => {
      const r = card.getBoundingClientRect();
      const px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
      card.style.setProperty('--mx', px * 100 + '%');
      card.style.setProperty('--my', py * 100 + '%');
      gsap.to(card, {
        rotationY: (px - 0.5) * 16, rotationX: (0.5 - py) * 16,
        transformPerspective: 900, duration: 0.4, ease: 'power2.out'
      });
    });
    card.addEventListener('pointerleave', () =>
      gsap.to(card, { rotationX: 0, rotationY: 0, duration: 0.7, ease: 'elastic.out(1,0.5)' }));
  });

  /* ---------- scroll-анимации ---------- */
  // заголовки секций: буквы «выпрыгивают»
  document.querySelectorAll('.split').forEach(h => {
    const text = h.textContent.trim();
    h.textContent = '';
    [...text].forEach(ch => {
      const s = document.createElement('span');
      s.className = 'ltr';
      s.style.display = 'inline-block';
      s.textContent = ch === ' ' ? '\u00A0' : ch;
      h.appendChild(s);
    });
    gsap.from(h.querySelectorAll('.ltr'), {
      yPercent: 120, opacity: 0, rotateZ: 8, stagger: 0.03, duration: 0.8, ease: 'back.out(2)',
      scrollTrigger: { trigger: h, start: 'top 85%' }
    });
  });

  // карточки работ: параллакс + появление
  gsap.utils.toArray('.work').forEach((w, i) => {
    gsap.from(w, {
      y: 80, opacity: 0, duration: 1, ease: 'power3.out', delay: (i % 2) * 0.1,
      scrollTrigger: { trigger: w, start: 'top 88%' }
    });
    gsap.to(w.querySelector('.work__media'), {
      yPercent: 12, ease: 'none',
      scrollTrigger: { trigger: w, start: 'top bottom', end: 'bottom top', scrub: true }
    });
  });

  // карточки услуг
  gsap.from('.card', {
    y: 60, opacity: 0, stagger: 0.08, duration: 0.9, ease: 'power3.out',
    scrollTrigger: { trigger: '#cards', start: 'top 82%' }
  });

  // pipeline: линии прочерчиваются
  gsap.utils.toArray('.pipe').forEach(p => {
    gsap.from(p, {
      x: -60, opacity: 0, duration: 0.8, ease: 'power3.out',
      scrollTrigger: { trigger: p, start: 'top 88%' }
    });
  });

  // тарифы
  gsap.from('.plan', {
    y: 70, opacity: 0, scale: 0.96, stagger: 0.1, duration: 0.9, ease: 'power3.out',
    scrollTrigger: { trigger: '.plans', start: 'top 82%' }
  });

  // счётчики статистики
  document.querySelectorAll('[data-count]').forEach(el => {
    const target = +el.dataset.count;
    const obj = { v: 0 };
    ScrollTrigger.create({
      trigger: el, start: 'top 90%', once: true,
      onEnter() {
        gsap.to(obj, {
          v: target, duration: 1.8, ease: 'power2.out',
          onUpdate: () => el.textContent = Math.round(obj.v)
        });
      }
    });
  });

  // гигантская надпись в футере едет параллаксом
  gsap.from('.footer__big', {
    yPercent: 40, ease: 'none',
    scrollTrigger: { trigger: '.footer', start: 'top bottom', end: 'bottom bottom', scrub: 1 }
  });

  /* ---------- прогресс-бар скролла ---------- */
  gsap.to('#scrollBar', {
    width: '100%', ease: 'none',
    scrollTrigger: { trigger: document.body, start: 'top top', end: 'bottom bottom', scrub: 0.3 }
  });

  /* ---------- хедер ---------- */
  ScrollTrigger.create({
    start: 80,
    onUpdate: self => document.getElementById('header').classList.toggle('is-scrolled', self.scroll() > 80)
  });

  /* ---------- бургер-меню ---------- */
  const burger = document.getElementById('burger');
  const nav = document.getElementById('nav');
  function closeMenu() { burger.classList.remove('is-open'); nav.classList.remove('is-open'); }
  burger.addEventListener('click', () => {
    burger.classList.toggle('is-open');
    nav.classList.toggle('is-open');
  });

  /* ---------- форма ---------- */
  document.getElementById('form').addEventListener('submit', e => {
    e.preventDefault();
    const f = e.target;
    if (!f.name.value.trim() || !f.email.value.trim()) {
      gsap.fromTo(f, { x: -10 }, { x: 0, duration: 0.5, ease: 'elastic.out(1,0.3)' });
      return;
    }
    document.getElementById('formOk').classList.add('is-show');
    f.reset();
    // Здесь подключается реальный бэкенд: fetch('/api/lead', {method:'POST', body: FormData})
  });

  /* ---------- RefreshTrigger после загрузки шрифтов ---------- */
  document.fonts && document.fonts.ready.then(() => ScrollTrigger.refresh());
})();
