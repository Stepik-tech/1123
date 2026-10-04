/* VDSmart — фронтенд: лендинг, авторизация, панель */
(function () {
  'use strict';

  // ---------- Общие мелочи ----------
  document.querySelectorAll('.alert-x').forEach(function (b) {
    b.addEventListener('click', function () { b.closest('.alert').remove(); });
  });

  // Показать/скрыть пароль на формах входа и регистрации
  document.querySelectorAll('.fill-pass').forEach(function (t) {
    function toggle() {
      var inp = document.getElementById(t.dataset.target);
      if (!inp) return;
      var show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      t.textContent = show ? 'Скрыть' : 'Показать';
      inp.focus();
    }
    t.addEventListener('click', toggle);
    t.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
  });

  // Индикатор надёжности пароля (регистрация)
  var passInp = document.getElementById('pass'), meter = document.querySelector('.pw-meter i');
  if (passInp && meter) {
    passInp.addEventListener('input', function () {
      var v = passInp.value, s = 0;
      if (v.length >= 8) s++;
      if (v.length >= 12) s++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
      if (/\d/.test(v)) s++;
      if (/[^A-Za-z0-9]/.test(v)) s++;
      var pct = Math.min(100, s * 20), col = pct < 40 ? '#c4340d' : pct < 80 ? '#e8a400' : '#107c10';
      meter.style.width = pct + '%';
      meter.style.background = col;
    });
  }
  // Живая сверка повтора пароля
  var p2 = document.getElementById('pass2'), p2err = document.getElementById('pass2Err');
  if (p2 && passInp && p2err) {
    p2.addEventListener('input', function () {
      p2err.hidden = !(p2.value && p2.value !== passInp.value);
    });
  }

  // ---------- Лендинг ----------
  var burger = document.getElementById('burger'), nav = document.getElementById('lnNav');
  if (burger && nav) {
    burger.addEventListener('click', function () {
      nav.classList.toggle('open');
      burger.classList.toggle('x');
    });
    nav.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { nav.classList.remove('open'); burger.classList.remove('x'); });
    });
  }

  var hdr = document.getElementById('lnHeader'), prog = document.getElementById('scrollProgress');
  function onScroll() {
    if (hdr) hdr.classList.toggle('scrolled', window.scrollY > 8);
    if (prog) {
      var h = document.documentElement.scrollHeight - innerHeight;
      prog.style.width = (h > 0 ? (scrollY / h) * 100 : 0) + '%';
    }
  }
  addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Имитация живых метрик в hero-панели
  var hpCpu = document.getElementById('hpCpu'), hpCpuBar = document.getElementById('hpCpuBar'),
      hpRam = document.getElementById('hpRam'), hpRamBar = document.getElementById('hpRamBar');
  if (hpCpu && hpRam) {
    setInterval(function () {
      var c = 25 + Math.round(Math.random() * 30), r = 55 + Math.round(Math.random() * 15);
      hpCpu.textContent = c + '%'; hpCpuBar.style.width = c + '%';
      hpRam.textContent = r + '%'; hpRamBar.style.width = r + '%';
    }, 2200);
  }

  // Плавное появление секций
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (ents) {
      ents.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('vis'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12 });
    document.querySelectorAll('.feat, .plan, .rg, .step-ln, .compare').forEach(function (el) {
      el.classList.add('reveal');
      io.observe(el);
    });
  }


  // ---------- Тосты (всплывающие уведомления справа сверху) ----------
  function makeToast(msg, type){
    let box = document.querySelector('.toasts');
    if (!box) { box = document.createElement('div'); box.className = 'toasts'; document.body.appendChild(box); }
    const t = document.createElement('div');
    t.className = 'alert alert-' + (type || 'ok') + ' toast in';
    t.setAttribute('role', 'status');
    t.innerHTML = '<span></span><button class="alert-x" type="button" aria-label="Закрыть">&times;</button>';
    t.firstChild.textContent = msg;
    box.appendChild(t);
    const kill = () => { t.classList.remove('in'); setTimeout(() => t.remove(), 360); };
    t.querySelector('.alert-x').addEventListener('click', kill);
    setTimeout(kill, 6000);
  }
  window.toast = makeToast;
  if (window.VDS_TOASTS && window.VDS_TOASTS.length) {
    window.VDS_TOASTS.forEach(function (x, i) { setTimeout(function(){ makeToast(x.msg, x.type); }, i * 260); });
  }

  // ---------- «Живые» метрики CPU/RAM в панели ----------
  document.querySelectorAll('[data-live]').forEach(function (el) {
    var base = parseInt(el.textContent, 10) || 0;
    setInterval(function () {
      var v = Math.max(3, Math.min(97, base + Math.round((Math.random() - 0.5) * 14)));
      el.textContent = v + '%';
      var bar = document.querySelector('[data-live-bar="' + el.dataset.live + '"]');
      if (bar) bar.style.width = v + '%';
    }, 3000);
  });

  // ---------- Панель: подтверждение опасных действий ----------
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!confirm(f.dataset.confirm)) e.preventDefault();
    });
  });

  // Автоскрытие тостов-алертов (кроме ошибок)
  setTimeout(function () {
    document.querySelectorAll('.alert-ok').forEach(function (a) {
      a.style.transition = 'opacity .4s'; a.style.opacity = '0';
      setTimeout(function () { a.remove(); }, 420);
    });
  }, 6000);
})();
