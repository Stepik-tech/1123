<?php
require_once __DIR__ . '/../includes/config.php';
$u = require_login();
$title  = 'Обзор';
$active = 'dashboard';
require __DIR__ . '/../includes/header.php';

$servers = db()->prepare('SELECT * FROM servers WHERE user_id = ? ORDER BY id');
$servers->execute([$u['id']]);
$servers = $servers->fetchAll();

$on  = array_filter($servers, fn($s) => $s['power'] === 'on');
$sumCpu = array_sum(array_column($servers, 'cpu'));
$sumRam = array_sum(array_column($servers, 'ram'));
$monthly = 0;
foreach ($servers as $s) $monthly += plan_price($s['plan']);

// псевдо-метрики: детерминированы от id сервера + текущей минуты
function fake_metric(int $seedBase, int $max): int
{
    return 8 + abs($seedBase * 7919 + (int)date('i') * 31) % $max;
}
?>
<div class="grid g4" style="margin-bottom:1.2rem">
    <div class="card stat"><div class="t">Серверы</div><div class="v"><?= count($servers) ?></div><div class="s"><?= count($on) ?> запущено</div></div>
    <div class="card stat"><div class="t">Суммарно vCPU</div><div class="v"><?= $sumCpu ?></div><div class="s">по всем серверам</div></div>
    <div class="card stat"><div class="t">Оперативная память</div><div class="v"><?= fmt_ram((int)$sumRam) ?></div><div class="s">по всем серверам</div></div>
    <div class="card stat"><div class="t">Аренда в месяц</div><div class="v"><?= fmt_money($monthly) ?></div><div class="s">баланс: <?= fmt_money((float)$u['balance']) ?></div></div>
</div>

<div class="page-head">
    <h2>Мои серверы</h2>
    <a class="btn btn-primary btn-sm" href="/user/server_new.php">+ Создать сервер</a>
</div>

<?php if (!$servers): ?>
    <div class="card"><div class="card-b" style="text-align:center;padding:2.5rem">
        <p style="color:var(--muted);margin-bottom:1rem">У вас пока нет серверов.</p>
        <a class="btn btn-primary" href="/user/server_new.php">Создать первый сервер</a>
    </div></div>
<?php else: ?>
<div class="grid g3">
    <?php foreach ($servers as $s):
        $cpu = fake_metric((int)$s['id'], 80);
        $ram = fake_metric((int)$s['id'] + 100, 85);
        $dsk = fake_metric((int)$s['id'] + 200, 60) + 20;
        $mc = fn($v) => $v > 85 ? ' err' : ($v > 65 ? ' warn' : '');
    ?>
    <div class="card srv">
        <div class="srv-top">
            <div>
                <div class="srv-name"><?= os_icon($s['os']) ?> <?= e($s['name']) ?></div>
                <div class="srv-os"><?= e($s['os']) ?> · <?= e($s['region']) ?></div>
            </div>
            <span class="badge <?= $s['power'] === 'on' ? 'b-on' : 'b-off' ?>"><i class="dot"></i><?= $s['power'] === 'on' ? 'Запущен' : 'Остановлен' ?></span>
        </div>
        <div class="srv-ip">IP: <code><?= e($s['ip']) ?></code></div>
        <div class="srv-metrics">
            <div class="mrow"><span>CPU</span><span data-live="cpu"><?= $cpu ?>%</span></div>
            <div class="meter"><i data-live-bar="cpu" style="width:<?= $cpu ?>%"></i></div>
            <div class="mrow" style="margin-top:.5rem"><span>RAM</span><span data-live="ram"><?= $ram ?>%</span></div>
            <div class="meter"><i data-live-bar="ram" style="width:<?= $ram ?>%"></i></div>
            <div class="mrow" style="margin-top:.5rem"><span>Диск</span><span><?= $dsk ?>% из <?= $s['disk'] ?> ГБ</span></div>
            <div class="meter<?= $mc($dsk) ?>"><i style="width:<?= $dsk ?>%"></i></div>
        </div>
        <div class="srv-foot">
            <a class="btn btn-sm btn-primary" href="/user/server.php?id=<?= $s['id'] ?>">Управление</a>
            <form method="post" action="/user/servers.php" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button class="btn btn-sm" name="act" value="<?= $s['power'] === 'on' ? 'stop' : 'start' ?>">
                    <?= $s['power'] === 'on' ? 'Выключить' : 'Включить' ?>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grid g2" style="align-items:start;margin-top:1.4rem">
    <div class="card"><div class="card-h"><h2>Последние события</h2><a href="/user/notifications.php" style="font-size:.85rem">Все уведомления →</a></div>
        <div class="feed">
            <?php foreach (user_notifications((int)$u['id'], 4) as $n): ?>
                <div class="f-item"><span class="np-ic sm t-<?= e($n['type']) ?>"><svg viewBox="0 0 24 24"><?php
                    echo match($n['type']) {
                        'ok' => '<path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/>',
                        'warn' => '<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>',
                        'err' => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>',
                        default => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>'
                    }; ?></svg></span>
                    <div><b><?= e($n['title']) ?></b><span><?= time_ago($n['created_at']) ?></span></div>
                </div>
            <?php endforeach; ?>
            <?php if (!user_notifications((int)$u['id'], 1)): ?><div class="f-item"><div><b>Событий пока нет</b><span>Действия с серверами появятся здесь.</span></div></div><?php endif; ?>
        </div>
    </div>
    <div class="card"><div class="card-h"><h2>Идеи для ваших проектов</h2><span class="badge b-on">подсказки</span></div>
        <div class="card-b ideas">
            <div class="idea"><span class="idea-num">1</span><div><b>Хостинг сайтов: «белый экран» не страшен</b>
                <p>nginx + автобэкап перед деплоем: если обновление сломало сайт — откатываетесь копией за минуту.</p></div></div>
            <div class="idea"><span class="idea-num">2</span><div><b>Игровой сервер на 20+ слотов</b>
                <p>Enterprise-узел в Амстердаме: низкий пинг до Европы, защита от DDoS уже включена.</p></div></div>
            <div class="idea"><span class="idea-num">3</span><div><b>VPN-выход для тестов</b>
                <p>Стартовый VPS + WireGuard: проверяйте рекламу и геосервисы с «чистого» IP за 5 минут.</p></div></div>
            <div class="idea"><span class="idea-num">4</span><div><b>Файрвол против брутфорса</b>
                <p>Оставьте открытыми только 22/80/443 — сканеры и подбор паролей отсекаются на уровне узла.</p></div></div>
        </div>
    </div>
</div>

<script>
// живые метрики: каждые 5 секунд новые значения (детерминированы от id + минуты)
(function(){
  const srv = <?= json_encode(array_map(fn($x)=>['id'=>(int)$x['id'],'cpu'=>fake_metric((int)$x['id'],80),'ram'=>fake_metric((int)$x['id']+100,85)], $servers)) ?>;
  setInterval(()=>{
    document.querySelectorAll('.srv').forEach(card=>{
      const name=(card.querySelector('.srv-name')||{}).textContent||'';
      const m=srv.find(s=>name.trim().toLowerCase().includes(String(s.id)) || false);
    });
    // простой проход по всем data-live: слегка дрейфуем вокруг базового значения
    document.querySelectorAll('[data-live]').forEach(el=>{
      const base=parseInt(el.textContent)||10;
      let v=base+(Math.floor(Math.random()*13)-6);
      v=Math.max(3,Math.min(96,v));
      el.textContent=v+'%';
      const bar=document.querySelector('[data-live-bar="'+el.dataset.live+'"]');
      if(bar){bar.style.width=v+'%';bar.parentElement.className='meter'+(v>85?' err':v>65?' warn':'');}
    });
  },5000);
})();
</script>

<?php require __DIR__ . '/../includes/footer.php';
