<?php
require __DIR__ . '/includes/header_auth.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['pass'] ?? '';
    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();
    if ($u && password_verify($pass, $u['pass'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        if (!empty($_POST['remember'])) {
            // «Запомнить меня»: токен в куке + хеш в БД — автологин на 30 дней
            $token = bin2hex(random_bytes(32));
            $exp = date('Y-m-d H:i:s', time() + 30 * 86400);
            db()->prepare('INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?,?,?)')
               ->execute([$u['id'], hash('sha256', $token), $exp]);
            setcookie('vds_remember', $u['id'] . ':' . $token, [
                'expires' => time() + 30 * 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
            ]);
        }
        notify((int)$u['id'], 'Вход в панель выполнен', 'Новый вход: ' . date('d.m.Y H:i') . ' · IP ' . ($_SERVER['REMOTE_ADDR'] ?? ''), 'info');
        header('Location: /user/dashboard.php');
        exit;
    }
    $error = 'Неверный e-mail или пароль. Проверьте раскладку клавиатуры и попробуйте ещё раз.';
    $email = e($email);
}
?>
<h1>Вход в панель</h1>
<p class="auth-sub">Управляйте своими серверами VDSmart.</p>

<?php if ($error): ?><div class="alert alert-err" role="alert"><?= e($error) ?></div><?php endif; ?>

<form method="post" id="loginForm">
    <?= csrf_field() ?>
    <div class="field"><label for="email">E-mail</label>
        <input class="input" id="email" name="email" type="email" autocomplete="email" inputmode="email"
               value="<?= $email ?? '' ?>" placeholder="you@example.com" required autofocus></div>
    <div class="field"><label for="pass">Пароль
        <span class="fill-pass" data-target="pass" role="button" tabindex="0">Показать</span></label>
        <input class="input" id="pass" name="pass" type="password" autocomplete="current-password" minlength="6" required></div>
    <div class="auth-row">
        <label class="check-row" style="margin:0"><input type="checkbox" name="remember" value="1" checked>
            <span>Запомнить меня</span></label>
        <a href="/forgot.php" class="auth-forgot-link">Забыли пароль?</a>
    </div>
    <button class="btn btn-primary btn-lg btn-block" type="submit" id="loginBtn">Войти</button>
</form>

<div class="demo-note">
    <b>Демо-доступы — нажмите, чтобы подставить в форму:</b><br>
    <a href="#" class="demo-fill" data-mail="ivan@demo.local" data-pass="demo123">Пользователь: ivan@demo.local · 3 сервера, баланс</a><br>
    <a href="#" class="demo-fill" data-mail="admin@vdsmart.local" data-pass="admin123">Администратор: admin@vdsmart.local</a>
</div>

<script>
(function(){
  const em=document.getElementById('email'), pw=document.getElementById('pass');
  em.addEventListener('keydown',e=>{ if(e.key==='Enter'){e.preventDefault();pw.focus();} });
  // демо-автозаполнение по клику
  document.querySelectorAll('.demo-fill').forEach(a=>a.addEventListener('click',e=>{
    e.preventDefault(); em.value=a.dataset.mail; pw.value=a.dataset.pass;
    document.getElementById('loginBtn').focus();
  }));
})();
</script>

<?php
$alt = 'Нет аккаунта? <a href="/register.php">Зарегистрироваться</a>';
require __DIR__ . '/includes/footer_auth.php';
