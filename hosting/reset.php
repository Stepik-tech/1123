<?php
require __DIR__ . '/includes/header_auth.php';
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$ok = false;

if ($token !== '') {
    $st = db()->prepare("SELECT * FROM pass_resets WHERE token_hash=? AND used=0 AND expires_at > datetime('now')");
    $st->execute([hash('sha256', $token)]);
    $reset = $st->fetch();
} else {
    $reset = null;
}

if (!$reset && !empty($token)) $error = 'Ссылка недействительна или устарела. Запросите новую.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    check_csrf();
    $p1 = $_POST['pass'] ?? '';
    $p2 = $_POST['pass2'] ?? '';
    if (strlen($p1) < 8)        $error = 'Новый пароль должен быть не короче 8 символов.';
    elseif ($p1 !== $p2)        $error = 'Пароли не совпадают.';
    else {
        $qu = db()->prepare('SELECT id FROM users WHERE email=?');
        $qu->execute([$reset['email']]);
        if ($user = $qu->fetch()) {
            db()->prepare('UPDATE users SET pass=? WHERE id=?')->execute([password_hash($p1, PASSWORD_DEFAULT), $user['id']]);
            db()->prepare('UPDATE pass_resets SET used=1 WHERE id=?')->execute([$reset['id']]);
            // сброс всех «запомнинок» и токенов — старый доступ аннулируется
            db()->prepare('DELETE FROM remember_tokens WHERE user_id=?')->execute([$user['id']]);
            notify((int)$user['id'], 'Пароль изменён', 'Пароль аккаунта обновлён через ссылку сброса. Если это были не вы — напишите в поддержку немедленно.', 'warn');
            $ok = true;
        } else $error = 'Аккаунт не найден.';
    }
}
?>
<h1>Новый пароль</h1>
<p class="auth-sub">Придумайте надёжный пароль для аккаунта <b><?= e($reset['email'] ?? '') ?></b>.</p>

<?php if ($error): ?><div class="alert alert-err" role="alert"><?= e($error) ?></div><?php endif; ?>

<?php if ($ok): ?>
    <div class="alert alert-ok" role="status">Пароль обновлён. Все старые сессии «Запомнить меня» отозваны.</div>
    <a class="btn btn-primary btn-lg btn-block" href="/login.php">Войти с новым паролем</a>
<?php elseif ($reset): ?>
<form method="post">
    <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="field"><label for="pass">Новый пароль
        <span class="fill-pass" data-target="pass" role="button" tabindex="0">Показать</span></label>
        <input class="input" id="pass" name="pass" type="password" autocomplete="new-password" minlength="8" required autofocus></div>
    <div class="field"><label for="pass2">Повторите пароль</label>
        <input class="input" id="pass2" name="pass2" type="password" autocomplete="new-password" minlength="8" required></div>
    <button class="btn btn-primary btn-lg btn-block" type="submit">Сохранить пароль</button>
</form>
<script>
// живая сверка двух полей пароля
(function(){
  const p1=document.getElementById('pass'), p2=document.getElementById('pass2');
  p2.addEventListener('input',()=>{ p2.setCustomValidity(p2.value && p2.value!==p1.value ? 'Пароли не совпадают' : ''); });
  p1.addEventListener('input',()=>{ p2.setCustomValidity(p2.value && p2.value!==p1.value ? 'Пароли не совпадают' : ''); });
})();
</script>
<?php else: ?>
<a class="btn btn-block" href="/forgot.php">Запросить новую ссылку</a>
<?php endif; ?>

<?php
$alt = 'Вернуться ко <a href="/login.php">входу</a>';
require __DIR__ . '/includes/footer_auth.php';
