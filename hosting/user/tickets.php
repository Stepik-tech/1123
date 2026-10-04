<?php
require_once __DIR__ . '/../includes/config.php';
$u = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $act = $_POST['act'] ?? '';

    if ($act === 'create') {
        $subj = trim($_POST['subject'] ?? '');
        $body = trim($_POST['message'] ?? '');
        $cat  = in_array($_POST['category'] ?? '', ['billing','server','network','security','general'], true) ? $_POST['category'] : 'general';
        if (mb_strlen($subj) < 5 || mb_strlen($body) < 10) {
            flash('Опишите проблему подробнее: тема — от 5 символов, сообщение — от 10.', 'err');
        } else {
            db()->beginTransaction();
            db()->prepare('INSERT INTO tickets (user_id,subject,category) VALUES (?,?,?)')->execute([$u['id'], $subj, $cat]);
            $tid = (int)db()->lastInsertId();
            db()->prepare('INSERT INTO ticket_messages (ticket_id,author_id,body) VALUES (?,?,?)')->execute([$tid, $u['id'], $body]);
            // автоответ поддержки (демо)
            $ans = 'Здравствуйте, ' . $u['name'] . "! Обращение #{$tid} принято. Среднее время ответа инженера — 15 минут. Мы уже проверяем ваш вопрос.";
            db()->prepare("INSERT INTO ticket_messages (ticket_id,author_id,body,created_at) VALUES (?,1?,?,datetime('now','+40 seconds'))")->execute([$tid, $ans]);
            db()->prepare("UPDATE tickets SET status='answered', answered_at=datetime('now','+40 seconds') WHERE id=?")->execute([$tid]);
            db()->commit();
            notify((int)$u['id'], 'Поддержка ответила по обращению #' . $tid, $ans, 'info', '/user/ticket.php?id=' . $tid);
            flash('Обращение #' . $tid . ' создано. Ответьте в разделе «Мои обращения».');
        }
    }
    elseif ($act === 'reply') {
        $tid = (int)($_POST['id'] ?? 0);
        $body = trim($_POST['message'] ?? '');
        $st = db()->prepare('SELECT * FROM tickets WHERE id=? AND user_id=?');
        $st->execute([$tid, $u['id']]);
        $t = $st->fetch();
        if ($t && mb_strlen($body) >= 2) {
            db()->prepare('INSERT INTO ticket_messages (ticket_id,author_id,body) VALUES (?,?,?)')->execute([$tid, $u['id'], $body]);
            if ($t['status'] === 'closed') db()->prepare("UPDATE tickets SET status='open' WHERE id=?")->execute([$tid]);
            flash('Сообщение отправлено.');
        } else flash(mb_strlen($body) < 2 ? 'Пустое сообщение.' : 'Обращение не найдено.', 'err');
    }
    elseif ($act === 'close') {
        $tid = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE tickets SET status='closed' WHERE id=? AND user_id=?")->execute([$tid, $u['id']]);
        flash('Обращение #' . $tid . ' закрыто.');
    }
    header('Location: /user/tickets.php'); exit;
}

$title  = 'Поддержка';
$active = 'tickets';
require __DIR__ . '/../includes/header.php';

$list = db()->prepare('SELECT * FROM tickets WHERE user_id=? ORDER BY id DESC LIMIT 50');
$list->execute([$u['id']]);
$list = $list->fetchAll();

$CATS = ['general'=>'Общий вопрос','server'=>'Серверы и ОС','network'=>'Сеть и firewall','billing'=>'Биллинг','security'=>'Безопасность'];

$openCnt = count(array_filter($list, fn($t) => $t['status'] !== 'closed'));
?>
<div class="grid g2" style="align-items:start;grid-template-columns:1fr 400px">

<div class="card">
    <div class="card-h"><h2>Мои обращения</h2><span class="badge <?= $openCnt ? 'b-warn' : 'b-on' ?>"><?= $openCnt ?> открытых</span></div>
    <?php if (!$list): ?><div class="card-b" style="color:var(--muted)">Обращений ещё нет — создайте первое справа.</div>
    <?php else: ?>
    <table><thead><tr><th>#</th><th>Тема</th><th>Категория</th><th>Статус</th><th>Обновлено</th></tr></thead><tbody>
    <?php foreach ($list as $t): ?>
        <tr>
            <td>#<?= (int)$t['id'] ?></td>
            <td><a href="/user/ticket.php?id=<?= (int)$t['id'] ?>"><b><?= e($t['subject']) ?></b></a></td>
            <td><?= e($CATS[$t['category']] ?? $t['category']) ?></td>
            <td><?php $cls = ['open'=>'b-warn','answered'=>'b-on','closed'=>'b-off'][$t['status']] ?? 'b-off'; ?>
                <span class="badge <?= $cls ?>"><i class="dot"></i><?= ['open'=>'Открыто','answered'=>'Отвечено','closed'=>'Закрыто'][$t['status']] ?? $t['status'] ?></span></td>
            <td><?= time_ago($t['answered_at'] ?: $t['created_at']) ?></td>
        </tr>
    <?php endforeach; ?></tbody></table>
    <?php endif; ?>
</div>

<div class="card"><div class="card-h"><h2>Новое обращение</h2></div><div class="card-b">
<form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="create">
    <div class="field"><label>Куда направляем</label>
        <select name="category">
            <?php foreach ($CATS as $c => $l): ?><option value="<?= $c ?>"><?= e($l) ?></option><?php endforeach; ?>
        </select></div>
    <div class="field"><label>Тема</label><input class="input" name="subject" minlength="5" required placeholder="Например: не открывается порт 8080"></div>
    <div class="field"><label>Опишите проблему</label>
        <textarea name="message" minlength="10" required placeholder="Сервер, IP, что произошло, что уже пробовали…"></textarea></div>
    <button class="btn btn-primary">Отправить в поддержку</button>
    <p class="hint" style="margin-top:.7rem">Круглосуточно · среднее время ответа — 15 минут · ответ придёт уведомлением на сайт.</p>
</form>
</div></div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
