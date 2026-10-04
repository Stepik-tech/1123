// VDSmart — лендинг v3 (корпоративный стиль): без внешних библиотек
(function () {
  'use strict';

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

  // тень под шапкой при скролле
  var hd = document.getElementById('lnHeader');
  window.addEventListener('scroll', function () {
    if (hd) hd.style.boxShadow = document.documentElement.scrollTop > 8 ? '0 2px 8px rgba(0,0,0,.08)' : 'none';
  }, { passive: true });

  // появление секций при скролле
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (ents) {
      ents.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('vis'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
  } else {
    document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('vis'); });
  }

  // переключатель тарифов: помесячно / за год (-20%)
  var sw = document.getElementById('planSwitch');
  if (sw) {
    sw.addEventListener('click', function () {
      var yearly = !sw.classList.contains('on');
      sw.classList.toggle('on', yearly);
      document.querySelectorAll('.price span[data-m]').forEach(function (s) {
        var m = +s.dataset.m;
        s.textContent = (yearly ? Math.round(m * 0.8) : m).toLocaleString('ru-RU');
      });
    });
  }

  // FAQ-аккордеон
  document.querySelectorAll('.qa button').forEach(function (b) {
    b.addEventListener('click', function () {
      var qa = b.parentElement, open = qa.classList.contains('open');
      document.querySelectorAll('.qa.open').forEach(function (x) { x.classList.remove('open'); });
      if (!open) qa.classList.add('open');
    });
  });

    // плавный якорный скролл с учётом sticky-шапки
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var sel = a.getAttribute('href');
      if (!sel || sel.length < 2) return;
      var el = document.querySelector(sel);
      if (!el) return;
      e.preventDefault();
      window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - 62, behavior: 'smooth' });
    });
  });
})();
