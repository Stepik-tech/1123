<?php
require_once __DIR__ . '/../includes/config.php';
$u = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $act = $_POST['act'] ?? '';

    if ($act === 'topup') {
        $sum = (float)($_POST['sum'] ?? 0);
        if ($sum < 100 || $sum > 100000) {
            flash('Сумма пополнения — от 100 до 100 000 ₽.', 'err');
        } else {
            $method = in_array($_POST['method'] ?? '', ['card','sbp','crypto'], true) ? $_POST['method'] : 'card';
            db()->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$sum, $u['id']]);
            db()->prepare('INSERT INTO orders (user_id,title,amount,status) VALUES (?,?,?,?)')
               ->execute([$u['id'], 'Пополнение баланса (' . $method . ')', $sum, 'paid']);
            flash('Баланс пополнен на ' . fmt_money($sum) . '. (Демо: платёж подтверждён мгновенно.)');
        }
    }
    elseif ($act === 'pay_servers') {
        $st = db()->prepare('SELECT plan FROM servers WHERE user_id=?');
        $st->execute([$u['id']]);
        $total = array_sum(array_map('plan_price', array_column($st->fetchAll(), 'plan')));
        if ($total <= 0) { flash('Пополнять нечего — серверов нет.', 'err'); }
        elseif ($u['balance'] < $total) { flash('Недостаточно средств: не хватает ' . fmt_money($total - (float)$u['balance']) . '.', 'err'); }
        else {
            db()->prepare('UPDATE users SET balance = balance - ? WHERE id=?')->execute([$total, $u['id']]);
            db()->prepare('INSERT INTO orders (user_id,title,amount,status) VALUES (?,?,?,?)')
               ->execute([$u['id'], 'Аренда серверов за месяц', $total, 'paid']);
            flash('Аренда серверов оплачена: ' . fmt_money($total) . '.');
        }
    }
    header('Location: /user/billing.php'); exit;
}

$title  = 'Баланс и оплата';
$active = 'billing';
require __DIR__ . '/../includes/header.php';

$hist = db()->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 20');
$hist->execute([$u['id']]);
$hist = $hist->fetchAll();

$st = db()->prepare('SELECT plan FROM servers WHERE user_id=?');
$st->execute([$u['id']]);
$monthly = array_sum(array_map('plan_price', array_column($st->fetchAll(), 'plan')));
?>
<div class="grid g3" style="margin-bottom:1.3rem">
    <div class="card stat"><div class="t">Текущий баланс</div><div class="v"><?= fmt_money((float)$u['balance']) ?></div><div class="s">доступно к списанию</div></div>
    <div class="card stat"><div class="t">Аренда в месяц</div><div class="v"><?= fmt_money($monthly) ?></div><div class="s">следующее списание — 1 числа</div></div>
    <div class="card stat"><div class="t">Статус</div><div class="v" style="font-size:1.15rem"><?= $u['balance'] >= $monthly ? '<span class="badge b-on"><i class="dot"></i>Оплачено</span>' : '<span class="badge b-err"><i class="dot"></i>Нужно пополнить</span>' ?></div><div class="s">&nbsp;</div></div>
</div>

<div class="grid g2" style="align-items:start">
<div class="card"><div class="card-h"><h2>Пополнить баланс</h2></div><div class="card-b">
<form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="topup">
    <div class="field"><label>Способ оплаты</label>
        <select name="method">
            <option value="card">Банковская карта</option>
            <option value="sbp">СБП (по QR-коду)</option>
            <option value="crypto">Криптовалюта (USDT)</option>
        </select></div>
    <div class="field"><label>Сумма, ₽</label>
        <input class="input" type="number" name="sum" min="100" max="100000" step="50" value="1000" required>
        <div class="hint">Комиссия 0%. Зачисление мгновенное (демо-режим).</div></div>
    <div style="display:flex;gap:.45rem;margin-bottom:1rem;flex-wrap:wrap">
        <?php foreach ([500,1000,3000,5000] as $q): ?>
        <button type="button" class="btn btn-sm" onclick="document.querySelector('[name=sum]').value=<?= $q ?>"><?= $q ?> ₽</button>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-primary">Пополнить</button>
</form>
</div></div>

<div class="card"><div class="card-h"><h2>Оплата аренды</h2></div><div class="card-b">
    <p style="color:var(--muted);margin-bottom:1rem">Ежемесячно с баланса списывается стоимость аренды всех серверов: <b><?= fmt_money($monthly) ?></b>.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="pay_servers">
        <button class="btn" <?= $u['balance'] < $monthly ? 'disabled' : '' ?>>Оплатить сейчас</button>
    </form>
    <p class="hint" style="margin-top:.8rem">Совет: держите запас ≥ стоимости двух месяцев, чтобы серверы не были приостановлены.</p>
</div></div>
</div>

<h2 style="margin:1.4rem 0 .7rem">История операций</h2>
<div class="card">
<?php if (!$hist): ?><div class="card-b" style="color:var(--muted)">Операций пока нет.</div>
<?php else: ?>
<table><thead><tr><th>ID</th><th>Дата</th><th>Описание</th><th>Сумма</th><th>Статус</th></tr></thead><tbody>
<?php foreach ($hist as $h): ?>
<tr><td>#<?= $h['id'] ?></td><td><?= e($h['created_at']) ?></td><td><?= e($h['title']) ?></td>
<td><b>+<?= fmt_money((float)$h['amount']) ?></b></td>
<td><span class="badge b-on"><i class="dot"></i><?= $h['status']==='paid'?'Выполнено':'Новая' ?></span></td></tr>
<?php endforeach; ?></tbody></table>
<?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
