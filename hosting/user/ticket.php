<?php
require_once __DIR__ . '/../includes/config.php';
$u = require_login();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM tickets WHERE id=? AND user_id=?');
$st->execute([$id, $u['id']]);
$t = $st->fetch();
if (!$t) { flash('Обращение не найдено.', 'err'); header('Location: /user/tickets.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $body = trim($_POST['message'] ?? '');
    $act  = $_POST['act'] ?? '';
    if ($act === 'reply' && mb_strlen($body) >= 2) {
        db()->prepare('INSERT INTO ticket_messages (ticket_id,author_id,body) VALUES (?,?,?)')->execute([$id, $u['id'], $body]);
        db()->prepare("UPDATE tickets SET status='open' WHERE id=?")->execute([$id]);
        flash('Сообщение отправлено.');
    } elseif ($act === 'close') {
        db()->prepare("UPDATE tickets SET status='closed' WHERE id=?")->execute([$id]);
        flash('Обращение закрыто. Спасибо!');
    }
    header('Location: /user/ticket.php?id=' . $id); exit;
}

$q = db()->prepare('SELECT m.*, um.name AS author, um.is_admin FROM ticket_messages m JOIN users um ON um.id=m.author_id WHERE m.ticket_id=? ORDER BY m.id');
$q->execute([$id]);
$msgs = $q->fetchAll();

$title  = 'Обращение #' . $id;
$active = 'tickets';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <a href="/user/tickets.php" style="font-size:.88rem">&larr; Все обращения</a>
        <h1 style="margin-top:.3rem"><?= e($t['subject']) ?></h1>
        <p style="color:var(--muted);font-size:.88rem;margin-top:.2rem">#<?= $id ?> · создан <?= e($t['created_at']) ?> UTC</p>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center">
        <?php $cls = ['open'=>'b-warn','answered'=>'b-on','closed'=>'b-off'][$t['status']]; ?>
        <span class="badge <?= $cls ?>"><i class="dot"></i><?= ['open'=>'Открыто','answered'=>'Отвечено','closed'=>'Закрыто'][$t['status']] ?></span>
        <?php if ($t['status'] !== 'closed'): ?>
        <form method="post" data-confirm="Закрыть обращение?"><?= csrf_field() ?><input type="hidden" name="act" value="close">
            <button class="btn btn-sm">Закрыть</button></form>
        <?php endif; ?>
    </div>
</div>

<div class="card"><div class="card-b chat">
    <?php foreach ($msgs as $m): ?>
    <div class="msg <?= $m['is_admin'] ? 'sup' : 'me' ?>">
        <div class="msg-av"><?= e(mb_strtoupper(mb_substr(trim($m['author']), 0, 1))) ?></div>
        <div class="msg-body">
            <div class="msg-meta"><b><?= e($m['author']) ?></b><?php if ($m['is_admin']): ?><span class="badge b-on" style="margin-left:.4rem">Поддержка VDSmart</span><?php endif; ?>
                <span class="msg-time"><?= e($m['created_at']) ?> UTC</span></div>
            <p><?= nl2br(e($m['body'])) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div></div>

<?php if ($t['status'] !== 'closed'): ?>
<div class="card" style="margin-top:1rem"><div class="card-b">
<form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="reply">
    <div class="field"><label>Ваш ответ</label><textarea name="message" required placeholder="Уточнение, вывод команды, скриншот описать текстом…"></textarea></div>
    <button class="btn btn-primary">Отправить</button>
</form>
</div></div>
<?php else: ?>
<div class="card" style="margin-top:1rem"><div class="card-b" style="color:var(--muted)">
Обращение закрыто. Нужна помощь? <a href="/user/tickets.php">Создайте новое</a>.
</div></div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
