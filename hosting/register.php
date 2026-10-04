<?php
require __DIR__ . '/includes/header_auth.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    purge_expired_tokens();
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['pass'] ?? '';
    $rep   = $_POST['pass2'] ?? '';
    $agree = isset($_POST['agree']) && $_POST['agree'] !== '';
    $toStep = (int)($_POST['to_step'] ?? 0);
    $back   = isset($_POST['back']) ? (int)$_POST['back'] : 0;

    if ($toStep === 2 || $toStep === 3) {
        // валидация шага 1 при переходе со 2-го и 3-го шага
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный e-mail.';
        if (mb_strlen($name) < 2 || mb_strlen($name) > 60) $errors[] = 'Имя — от 2 до 60 символов.';
    }
    if ($toStep === 3) {
        if (strlen($pass) < 8)      $errors[] = 'Пароль должен быть не короче 8 символов.';
        if ($pass !== $rep)         $errors[] = 'Пароли не совпадают.';
        if (!$agree)                $errors[] = 'Необходимо принять условия сервиса.';
        if (!$errors) {
            $st = db()->prepare('SELECT id FROM users WHERE email = ?');
            $st->execute([$email]);
            if ($st->fetch()) $errors[] = 'Такой e-mail уже зарегистрирован. Войдите или воспользуйтесь восстановлением доступа.';
        }
    }
    if (!$errors && $toStep === 3) {
        db()->prepare('INSERT INTO users (name, email, pass) VALUES (?,?,?)')->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)db()->lastInsertId();
        notify((int)$_SESSION['uid'], 'Добро пожаловать в VDSmart!', 'Аккаунт создан. Пополните баланс и запустите первый сервер за 60 секунд.', 'ok', '/user/server_new.php');
        flash('Аккаунт создан! Держите бонус 1 500 ₽ на тестовый сервер — начислим при первом пополнении.');
        header('Location: /user/dashboard.php');
        exit;
    }
}

// шаг из URL (?step=2|3), по умолчанию 1
$maxStep = max(1, min(3, (int)($_GET['step'] ?? 1)));
// данные для повторной отрисовки: из POST (если были ошибки) или из query (при навигации назад)
$oldName  = e($_POST['name']  ?? $_GET['name']  ?? '');
$oldEmail = e($_POST['email'] ?? $_GET['email'] ?? '');
?>
<h1>Создание аккаунта</h1>
<p class="auth-sub">Три коротких шага — и панель управления готова. Карта не требуется.</p>

<?php foreach ($errors as $er): ?><div class="alert alert-err" role="alert"><?= e($er) ?></div><?php endforeach; ?>

<div class="steps-bar" aria-hidden="true">
    <span class="step-dot <?= $maxStep>=1?'on cur':'' ?>"><b>1</b> Контакт</span><i class="step-line <?= $maxStep>1?'fill':'' ?>"></i>
    <span class="step-dot <?= $maxStep>=2?'on'.($maxStep==2?' cur':''):'' ?>"><b>2</b> Защита</span><i class="step-line <?= $maxStep>2?'fill':'' ?>"></i>
    <span class="step-dot <?= $maxStep>=3?'on cur':'' ?>"><b>3</b> Готово</span>
</div>

<form method="post" id="regForm" novalidate autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="agree" value="" id="agreeHid">

    <!-- ШАГ 1 -->
    <section class="rstep" data-step="1" <?= $maxStep===1?'':'hidden' ?>>
        <div class="field"><label for="name">Как к вам обращаться</label>
            <input class="input" id="name" name="name" autocomplete="name" value="<?= $oldName ?>"
                   placeholder="Иван Петров" minlength="2" maxlength="60" required></div>
        <div class="field"><label for="email">Рабочий e-mail</label>
            <div class="input-mail"><input class="input" id="email" name="email" type="email" autocomplete="email"
                value="<?= $oldEmail ?>" placeholder="you@example.com" required>
                <span class="mail-ok" id="mailOk" hidden>✓</span></div>
            <div class="hint" id="mailHint">На него придут уведомления о серверах и ответы поддержки.</div></div>
        <button type="submit" class="btn btn-primary btn-lg btn-block" name="to_step" value="2">Продолжить →</button>
    </section>

    <?php if ($maxStep >= 2): ?>
    <!-- ШАГ 2 -->
    <section class="rstep" data-step="2" <?= $maxStep===2?'':'hidden' ?>>
        <input type="hidden" name="name" value="<?= $oldName ?>">
        <input type="hidden" name="email" value="<?= $oldEmail ?>">
        <div class="field"><label for="pass">Придумайте пароль
            <span class="fill-pass" data-target="pass" role="button" tabindex="0">Показать</span></label>
            <input class="input" id="pass" name="pass" type="password" autocomplete="new-password" minlength="8" required>
            <div class="pw-meter" aria-hidden="true"><i></i></div>
            <ul class="pw-rules" id="pwRules">
                <li data-rule="len">От 8 символов</li>
                <li data-rule="case">Буквы разных регистров</li>
                <li data-rule="digit">Цифра</li>
                <li data-rule="sign">Спецсимвол (!@#$…)</li>
            </ul></div>
        <div class="field"><label for="pass2">Повторите пароль</label>
            <input class="input" id="pass2" name="pass2" type="password" autocomplete="new-password" minlength="8" required>
            <div class="hint" id="pass2Err" hidden style="color:var(--err)">Пароли не совпадают.</div></div>
        <div class="field"><label>Подтверждение «я не робот»</label>
            <div class="human-box" id="humanBox" role="checkbox" aria-checked="false" tabindex="0">
                <span class="human-check"><svg viewBox="0 0 24 24"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></span>
                <span class="human-txt"><b>Я человек</b><small>нажмите, чтобы подтвердить</small></span>
                <span class="human-spin"></span>
            </div>
            <div class="hint">Мы не отправляем SMS — это локальная проверка формы.</div></div>
        <label class="check-row"><input type="checkbox" id="agreeChk">
            <span>Я принимаю <a href="/terms.php" target="_blank" rel="noopener">условия сервиса</a> и <a href="/privacy.php" target="_blank" rel="noopener">политику конфиденциальности</a></span></label>
        <div style="display:flex;gap:.6rem;margin-top:1rem">
            <button type="submit" name="back" value="1" class="btn btn-lg">← Назад</button>
            <button type="submit" name="to_step" value="3" class="btn btn-primary btn-lg" style="flex:1" id="toStep3">Продолжить</button>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($maxStep >= 3): ?>
    <!-- ШАГ 3 -->
    <section class="rstep" data-step="3">
        <input type="hidden" name="name" value="<?= $oldName ?>">
        <input type="hidden" name="email" value="<?= $oldEmail ?>">
        <div class="final-box">
            <div class="final-ic"><svg viewBox="0 0 24 24"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></div>
            <h3>Проверьте данные</h3>
            <p>Осталось нажать кнопку — аккаунт будет создан, и вы попадёте в панель управления.</p>
            <div class="final-sum">
                <div><span>Имя:</span><b><?= $oldName ?: '—' ?></b></div>
                <div><span>E-mail:</span><b><?= $oldEmail ?: '—' ?></b></div>
                <div><span>Пароль:</span><b id="sumPw">надёжный</b></div>
            </div>
            <div style="display:flex;gap:.6rem;margin-top:1.2rem">
                <button type="submit" name="back" value="2" class="btn btn-lg">← Назад</button>
                <button type="submit" class="btn btn-primary btn-lg" style="flex:1" id="finishBtn">Создать аккаунт</button>
            </div>
        </div>
    </section>
    <?php endif; ?>
</form>

<script>
(function(){
  const form=document.getElementById('regForm');
  const qs=s=>document.querySelector(s);
  const step=qs('.rstep:not([hidden])').dataset.step;

  // Навигация между шагами — через GET с переносом данных (без перезаписи пароля)
  function go(n){
    const p=new URLSearchParams(location.search);
    p.set('step',n);
    const nm=qs('#name'), em=qs('#email');
    if(nm&&nm.value) p.set('name',nm.value.trim());
    if(em&&em.value) p.set('email',em.value.trim());
    location.href='/register.php?'+p.toString();
  }
  document.querySelectorAll('[name=back]').forEach(b=>b.addEventListener('click',e=>{e.preventDefault();go(+b.value);}));

  // «Показать/скрыть» пароль
  document.querySelectorAll('.fill-pass').forEach(t=>{
    const toggle=()=>{const i=document.getElementById(t.dataset.target);i.type=i.type==='password'?'text':'password';t.textContent=i.type==='password'?'Показать':'Скрыть';};
    t.addEventListener('click',toggle);
    t.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();toggle();}});
  });

  // «я человек» с анимацией проверки
  const box=qs('#humanBox'); let human=!box;
  if(box){
    if(sessionStorage.getItem('vd_human')==='1'){human=true;box.classList.add('ok');box.setAttribute('aria-checked','true');}
    function confirmHuman(){
      if(human) return;
      box.classList.add('checking');
      setTimeout(()=>{human=true;sessionStorage.setItem('vd_human','1');box.classList.remove('checking');box.classList.add('ok');box.setAttribute('aria-checked','true');refreshGate();},900);
    }
    box.addEventListener('click',confirmHuman);
    box.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();confirmHuman();}});
  }

  // надёжность пароля: метр + чек-лист правил
  const passI=qs('#pass'), pass2I=qs('#pass2'), agree=qs('#agreeChk');
  function pwScore(v){let s=0;if(v.length>=8)s++;if(/[a-z]/.test(v)&&/[A-Z]/.test(v))s++;if(/\d/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;return s;}
  const meter=document.querySelector('.pw-meter i'), rules=document.querySelectorAll('#pwRules li');
  if(passI&&meter){
    passI.addEventListener('input',()=>{
      const v=passI.value, sc=pwScore(v), pct=Math.min(100,sc*25);
      meter.style.width=pct+'%';
      meter.style.background=pct<40?'#c4340d':pct<75?'#e8a400':'#107c10';
      rules.forEach(r=>{const k=r.dataset.rule;
        r.classList.toggle('ok', k==='len'&&v.length>=8 || k==='case'&&(/[a-z]/.test(v)&&/[A-Z]/.test(v)) || k==='digit'&&/\d/.test(v) || k==='sign'&&/[^A-Za-z0-9]/.test(v));});
      syncPass2(); refreshGate();
    });
  }
  function syncPass2(){ if(pass2I&&qs('#pass2Err')) qs('#pass2Err').hidden = !(pass2I.value && pass2I.value!==passI.value); }
  if(pass2I) pass2I.addEventListener('input',()=>{syncPass2();refreshGate();});

  // зелёная галочка валидности e-mail
  const mailI=qs('#email');
  if(mailI) mailI.addEventListener('input',()=>{ const el=qs('#mailOk'); if(el) el.hidden=!mailI.checkValidity(); });

  // блокировка кнопки «Продолжить» на шаге 2 до выполнения условий
  const next3=qs('#toStep3');
  function refreshGate(){
    if(!next3||!passI) return;
    const ok = pwScore(passI.value)>=2 && human && agree.checked && passI.value===pass2I.value && passI.value.length>=8;
    next3.disabled=!ok;
  }
  if(agree) agree.addEventListener('change',refreshGate);
  refreshGate();

  // итог на шаге 3
  const sumPw=qs('#sumPw');
  if(sumPw&&passI&&passI.value){
    const sc=pwScore(passI.value);
    sumPw.textContent=['','очень слабый','слабый','средний','надёжный','отличный'][sc]||'надёжный';
  }

  let hb;
  function hintBad(msg){
    let el=form.querySelector('.js-hint-bad');
    if(!el){el=document.createElement('div');el.className='alert alert-err js-hint-bad';form.prepend(el);}
    el.textContent=msg; clearTimeout(hb); hb=setTimeout(()=>el.remove(),5000);
  }

  // клиентская валидация перед отправкой
  form.addEventListener('submit',function(e){
    const btn=e.submitter;
    const to=btn&&btn.name==='to_step'?+btn.value:0;
    if(to===2){
      if(!form.reportValidity()){e.preventDefault();return;}
      sessionStorage.setItem('vd_name',qs('#name').value.trim());
      sessionStorage.setItem('vd_email',mailI.value.trim());
    }
    if(to===3||btn&&btn.id==='finishBtn'){
      if(passI&&passI.value.length<8){e.preventDefault();passI.focus();hintBad('Пароль короче 8 символов.');return;}
      if(passI&&passI.value!==pass2I.value){e.preventDefault();pass2I.focus();hintBad('Пароли не совпадают.');return;}
      if(passI&&pwScore(passI.value)<2){e.preventDefault();passI.focus();hintBad('Пароль слишком простой — выполните хотя бы 2 правила.');return;}
      if(!human){e.preventDefault();hintBad('Подтвердите, что вы человек.');return;}
      if(agree&&!agree.checked){e.preventDefault();hintBad('Нужно принять условия сервиса.');return;}
      if(btn&&btn.id==='finishBtn') qs('#agreeHid').value='1';
    }
  });

  // автоподстановка имени/почты после возврата назад
  if(step==='1'){
    const n=sessionStorage.getItem('vd_name'), m=sessionStorage.getItem('vd_email');
    if(n&&!qs('#name').value) qs('#name').value=n;
    if(m&&!mailI.value) mailI.value=m;
  }
})();
</script>

<?php
$alt = 'Уже есть аккаунт? <a href="/login.php">Войти</a>';
require __DIR__ . '/includes/footer_auth.php';
