// VDSmart — лендинг: интерактив «как у взрослых» (без внешних библиотек)
(function () {
  'use strict';

  // прогресс скролла страницы
  var prog = document.getElementById('scrollProgress');
  function onScroll() {
    var h = document.documentElement;
    var p = h.scrollTop / (h.scrollHeight - h.clientHeight || 1);
    if (prog) prog.style.width = (p * 100).toFixed(1) + '%';
    var hd = document.getElementById('lnHeader');
    if (hd) hd.classList.toggle('scrolled', h.scrollTop > 8);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // мобильное меню
  var burger = document.getElementById('burger'), nav = document.getElementById('lnNav');
  if (burger && nav) {
    burger.addEventListener('click', function () {
      nav.classList.toggle('open'); burger.classList.toggle('x');
    });
    nav.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') { nav.classList.remove('open'); burger.classList.remove('x'); }
    });
  }

  // появление секций при скролле
  var io = new IntersectionObserver(function (ents) {
    ents.forEach(function (en) {
      if (en.isIntersecting) { en.target.classList.add('vis'); io.unobserve(en.target); }
    });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });

  // переключатель месяц/год в тарифах
  var tg = document.getElementById('billToggle');
  if (tg) tg.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-m]'); if (!b) return;
    tg.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
    var m = b.dataset.m;
    document.querySelectorAll('.pprice .pm, .pprice .py').forEach(function (s) { s.hidden = s.dataset.m !== m; });
    document.querySelectorAll('.pprice .unit').forEach(function (u) { u.textContent = m === 'm' ? ' /мес' : ' /год'; });
  });

  // живые метрики в макете панели на hero
  function tick(id, base, amp) {
    var v = Math.max(5, Math.min(95, base + Math.round((Math.random() - 0.5) * amp)));
    var t = document.getElementById(id), bar = document.getElementById(id + 'Bar');
    if (t) t.textContent = v + '%';
    if (bar) bar.style.width = v + '%';
  }
  setInterval(function () { tick('hpCpu', 34, 24); tick('hpRam', 61, 14); }, 2600);

  // плавный якорный скролл с учётом sticky-шапки
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var el = document.querySelector(a.getAttribute('href'));
      if (!el) return;
      e.preventDefault();
      window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - 76, behavior: 'smooth' });
    });
  });
})();
