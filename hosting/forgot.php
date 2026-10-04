<?php
require __DIR__ . '/includes/header_auth.php';
$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    purge_expired_tokens();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный e-mail.';
    } else {
        // всегда «успех», чтобы не раскрывать существование аккаунта
        $st = db()->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($row = $st->fetch()) {
            $token = bin2hex(random_bytes(32));
            db()->prepare('INSERT INTO pass_resets (email, token_hash, expires_at) VALUES (?,?,datetime(\'now\',\'+1 hour\'))')
               ->execute([$email, hash('sha256', $token)]);
            $link = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/reset.php?token=' . $token;
            notify((int)$row['id'], 'Запрошен сброс пароля',
                   'Если это были вы — перейдите по ссылке в письме. Ссылка действует 1 час.', 'warn');
            // Демо-режим: письмо не отправляется, ссылка показывается на экране
            $_SESSION['demo_reset_link'] = $link;
        }
        $sent = true;
    }
}
$demoLink = $_SESSION['demo_reset_link'] ?? null;
unset($_SESSION['demo_reset_link']);
?>
<h1>Забыли пароль?</h1>
<p class="auth-sub">Укажите e-mail аккаунта — мы пришлём ссылку для нового пароля.</p>

<?php if ($error): ?><div class="alert alert-err" role="alert"><?= e($error) ?></div><?php endif; ?>

<?php if ($sent): ?>
    <div class="alert alert-ok" role="status">Письмо со ссылкой отправлено на <b><?= e($email) ?></b>. Проверьте почту и папку «Спам». Ссылка действует 1 час.</div>
    <?php if ($demoLink): ?>
    <div class="demo-note"><b>Демо-режим:</b> почтовый сервер не подключён, поэтому ссылка напрямую:<br>
        <a href="<?= e($demoLink) ?>"><?= e($demoLink) ?></a></div>
    <?php endif; ?>
    <a class="btn btn-block" style="margin-top:.8rem" href="/login.php">Вернуться ко входу</a>
<?php else: ?>
<form method="post">
    <?= csrf_field() ?>
    <div class="field"><label for="email">E-mail</label>
        <input class="input" id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" required autofocus></div>
    <button class="btn btn-primary btn-lg btn-block" type="submit">Отправить ссылку</button>
</form>
<?php endif; ?>

<?php
$alt = 'Вспомнили пароль? <a href="/login.php">Войти</a>';
require __DIR__ . '/includes/footer_auth.php';
