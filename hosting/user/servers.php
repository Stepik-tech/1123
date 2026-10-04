<?php
require_once __DIR__ . '/../includes/config.php';
$u = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id  = (int)($_POST['id'] ?? 0);
    $act = $_POST['act'] ?? '';
    $st  = db()->prepare('SELECT * FROM servers WHERE id = ? AND user_id = ?');
    $st->execute([$id, $u['id']]);
    if ($s = $st->fetch()) {
        switch ($act) {
            case 'start':
                db()->prepare("UPDATE servers SET power='on' WHERE id=?")->execute([$id]);
                flash("Сервер «{$s['name']}» запущен."); break;
            case 'stop':
                db()->prepare("UPDATE servers SET power='off' WHERE id=?")->execute([$id]);
                flash("Сервер «{$s['name']}» остановлен.", 'ok'); break;
            case 'delete':
                db()->prepare('DELETE FROM servers WHERE id=?')->execute([$id]);
                flash("Сервер «{$s['name']}» удалён. Средства за текущий период не возвращаются.", 'ok'); break;
        }
    }
    header('Location: /user/servers.php');
    exit;
}

$title  = 'Мои серверы';
$active = 'servers';
require __DIR__ . '/../includes/header.php';

$servers = db()->prepare('SELECT * FROM servers WHERE user_id = ? ORDER BY id DESC');
$servers->execute([$u['id']]);
$servers = $servers->fetchAll();
?>
<div class="page-head">
    <p style="color:var(--muted)">Всего серверов: <b><?= count($servers) ?></b></p>
    <a class="btn btn-primary" href="/user/server_new.php">+ Создать сервер</a>
</div>

<div class="card">
  <?php if (!$servers): ?>
    <div class="card-b" style="text-align:center;padding:2.5rem;color:var(--muted)">Список пуст — создайте первый сервер.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Сервер</th><th>IP-адрес</th><th>Тариф</th><th>Регион</th><th>Статус</th><th>Аренда</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($servers as $s): ?>
      <tr>
        <td><b><?= os_icon($s['os']) ?> <?= e($s['name']) ?></b><br><span style="font-size:.8rem;color:var(--faint)"><?= e($s['os']) ?></span></td>
        <td><code><?= e($s['ip']) ?></code></td>
        <td><?= e($s['plan']) ?> · <?= $s['cpu'] ?> vCPU / <?= fmt_ram((int)$s['ram']) ?></td>
        <td><?= e($s['region']) ?></td>
        <td>
          <span class="badge <?= $s['power']==='on'?'b-on':'b-off' ?>"><i class="dot"></i><?= $s['power']==='on'?'Запущен':'Остановлен' ?></span>
          <?php if ($s['status']!=='active'): ?><span class="badge b-warn"><?= status_label($s['status']) ?></span><?php endif; ?>
        </td>
        <td><?= fmt_money(plan_price($s['plan'])) ?>/мес</td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn btn-sm" href="/user/server.php?id=<?= $s['id'] ?>">Управление</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
