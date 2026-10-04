<?php
require_once __DIR__ . '/../includes/config.php';
$u = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $act = $_POST['act'] ?? '';
    if ($act === 'read_all') {
        db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$u['id']]);
        flash('Все уведомления отмечены как прочитанные.');
    } elseif ($act === 'clear') {
        db()->prepare('DELETE FROM notifications WHERE user_id=?')->execute([$u['id']]);
        flash('История уведомлений очищена.');
    }
    header('Location: /user/notifications.php'); exit;
}

$title  = 'Уведомления';
$active = 'notifications';
require __DIR__ . '/../includes/header.php';
$items = user_notifications((int)$u['id'], 100);
?>
<div class="page-head">
    <p style="color:var(--muted)">Всего: <b><?= count($items) ?></b> · непрочитанных: <b><?= unread_count((int)$u['id']) ?></b></p>
    <div style="display:flex;gap:.5rem">
        <?php if ($items): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="read_all"><button class="btn btn-sm">Прочитать все</button></form>
        <form method="post" data-confirm="Очистить всю историю уведомлений?"><?= csrf_field() ?><input type="hidden" name="act" value="clear"><button class="btn btn-sm">Очистить</button></form>
        <?php endif; ?>
    </div>
</div>

<div class="card">
<?php if (!$items): ?>
    <div class="card-b" style="text-align:center;padding:2.5rem;color:var(--muted)">
        Уведомлений нет. Здесь появятся события: запуск и остановка серверов, бэкапы, платежи, ответы поддержки.
    </div>
<?php else: ?>
    <div class="notif-list">
    <?php foreach ($items as $n): ?>
        <a class="nf-item <?= $n['is_read'] ? '' : 'new' ?>" id="n<?= (int)$n['id'] ?>"
           href="<?= $n['link'] ? e($n['link']) : '#' ?>">
            <span class="np-ic t-<?= e($n['type']) ?>"><svg viewBox="0 0 24 24"><?php
                echo match($n['type']) {
                    'ok'   => '<path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/>',
                    'warn' => '<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>',
                    'err'  => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>',
                    default=> '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>'
                }; ?></svg></span>
            <span class="nf-txt"><b><?= e($n['title']) ?></b><p><?= e($n['message']) ?></p>
                <em><?= time_ago($n['created_at']) ?> · <?= e(date('d.m.Y H:i', strtotime($n['created_at'] . ' UTC'))) ?> UTC</em></span>
            <?php if (!$n['is_read']): ?><span class="nf-unread"></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
