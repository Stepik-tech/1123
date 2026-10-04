<?php
require_once __DIR__ . '/../includes/config.php';
$u = require_login();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $act = $_POST['act'] ?? '';

    if ($act === 'profile') {
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (mb_strlen($name) < 2) $errors[] = 'Имя слишком короткое.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный e-mail.';
        if (!$errors) {
            $st = db()->prepare('SELECT id FROM users WHERE email=? AND id<>?');
            $st->execute([$email, $u['id']]);
            if ($st->fetch()) $errors[] = 'Этот e-mail занят другим аккаунтом.';
        }
        if (!$errors) {
            db()->prepare('UPDATE users SET name=?, email=? WHERE id=?')->execute([$name, $email, $u['id']]);
            flash('Профиль обновлён.');
            header('Location: /user/profile.php'); exit;
        }
    }
    elseif ($act === 'password') {
        $old = $_POST['old'] ?? ''; $new = $_POST['new'] ?? ''; $rep = $_POST['rep'] ?? '';
        if (!password_verify($old, $u['pass']))                    $errors[] = 'Текущий пароль неверен.';
        if (strlen($new) < 6)                                      $errors[] = 'Новый пароль короче 6 символов.';
        if ($new !== $rep)                                         $errors[] = 'Пароли не совпадают.';
        if (!$errors) {
            db()->prepare('UPDATE users SET pass=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            flash('Пароль изменён.');
            header('Location: /user/profile.php'); exit;
        }
    }
}

$title  = 'Профиль';
$active = 'profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="grid g2" style="align-items:start">
    <div class="card"><div class="card-h"><h2>Личные данные</h2></div><div class="card-b">
        <?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="profile">
            <div class="field"><label>Имя</label><input class="input" name="name" value="<?= e($u['name']) ?>" required></div>
            <div class="field"><label>E-mail (он же логин)</label><input class="input" type="email" name="email" value="<?= e($u['email']) ?>" required></div>
            <div class="field"><label>Дата регистрации</label><input class="input" value="<?= e($u['created_at']) ?> UTC" disabled></div>
            <button class="btn btn-primary">Сохранить</button>
        </form>
    </div></div>

    <div class="card"><div class="card-h"><h2>Смена пароля</h2></div><div class="card-b">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="password">
            <div class="field"><label>Текущий пароль</label><input class="input" type="password" name="old" required></div>
            <div class="row">
                <div class="field"><label>Новый пароль</label><input class="input" type="password" name="new" minlength="6" required></div>
                <div class="field"><label>Повторите новый</label><input class="input" type="password" name="rep" minlength="6" required></div>
            </div>
            <button class="btn">Изменить пароль</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD']==='POST' && $act==='sessions'):
            db()->prepare('DELETE FROM sessions_log WHERE user_id=? AND sid<>?')->execute([$u['id'], session_id()]);
            db()->prepare('DELETE FROM remember_tokens WHERE user_id=?')->execute([$u['id']]);
            setcookie('vds_remember','',['expires'=>time()-3600,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
            flash('Все прочие сессии завершены, «Запомнить меня» отозвано.');
            header('Location: /user/profile.php'); exit;
        endif; ?>
        <h2 style="margin:1.6rem 0 .6rem">Безопасность</h2>
        <dl class="kv">
            <dt>Двухфакторная защита</dt><dd><span class="badge b-off"><i class="dot"></i>Выключена (демо)</span></dd>
            <dt>Смена пароля</dt><dd>используйте форму выше — старые куки «Запомнить меня» будут отозваны автоматически</dd>
            <dt>Активных серверов</dt><dd><?= db()->query('SELECT COUNT(*) FROM servers WHERE user_id='.(int)$u['id'])->fetchColumn() ?></dd>
        </dl>

        <h2 style="margin:1.6rem 0 .6rem">Активные сессии</h2>
        <?php $sess = db()->prepare('SELECT * FROM sessions_log WHERE user_id=? ORDER BY last_seen DESC LIMIT 8'); $sess->execute([$u['id']]); $sess=$sess->fetchAll(); ?>
        <?php if ($sess): ?>
        <table style="margin-bottom:.9rem"><thead><tr><th>Устройство / IP</th><th>Был активен</th><th></th></tr></thead><tbody>
        <?php foreach ($sess as $ss): $cur = $ss['sid'] === session_id(); ?>
        <tr><td><span style="font-size:.85rem"><?= e($cur ? 'Текущая сессия' : ($ss['ua'] ?: 'Неизвестное устройство')) ?><br><span style="color:var(--faint)"><?= e($ss['ip']) ?></span></span></td>
            <td><?= time_ago($ss['last_seen']) ?><?= $cur?' <span class="badge b-on"><i class="dot"></i>сейчас</span>':'' ?></td><td></td></tr>
        <?php endforeach; ?></tbody></table>
        <form method="post" data-confirm="Завершить все прочие сессии и отозвать «Запомнить меня»?">
            <?= csrf_field() ?><input type="hidden" name="act" value="sessions">
            <button class="btn btn-sm btn-danger">Выйти на всех других устройствах</button>
        </form>
        <?php else: ?><p style="color:var(--muted)">Записей о сессиях пока нет.</p><?php endif; ?>
    </div></div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
