<?php
require_once __DIR__ . '/../includes/config.php';
$u = require_login();

$PLANS = plans();
$OS = os_images();
$REGIONS = regions();
$prePlan = isset($_GET['plan']) && isset($PLANS[$_GET['plan']]) ? $_GET['plan'] : 'Standard';
$PLANLIST = [];
foreach ($PLANS as $pname => $pd) $PLANLIST[] = [$pname, $pd['cpu'], $pd['ram'], $pd['disk'], $pd['price'], $pd['desc']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['name'] ?? '');
    $plan = $_POST['plan'] ?? 'Standard';
    $os   = $_POST['os'] ?? 'Ubuntu 22.04 LTS';
    $rg   = $_POST['region'] ?? 'ru-msk-1';
    if (!in_array($os, $OS, true) || !isset($REGIONS[$rg])
        || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]{2,40}$/', $name)) {
        flash('Проверьте имя сервера (латиница, цифры, дефисы, от 3 символов) и корректность параметров.', 'err');
        header('Location: /user/server_new.php'); exit;
    }
    if (!isset($PLANS[$plan])) $plan = 'Standard';
    $p = $PLANS[$plan];
    [$cpu, $ram, $disk, $price] = [$p['cpu'], $p['ram'], $p['disk'], $p['price']];
    $bal = (float)$u['balance'];
    if ($bal < $price) {
        flash('Недостаточно средств: аренда стоит ' . fmt_money($price) . ', на балансе ' . fmt_money($bal) . '. Пополните баланс — первый месяц тестовых серверов списывается с него.', 'err');
        header('Location: /user/billing.php'); exit;
    }
    db()->prepare('UPDATE users SET balance = balance - ? WHERE id=?')->execute([$price, $u['id']]);
    db()->prepare('INSERT INTO orders (user_id,title,amount,status) VALUES (?,?,?,?)')
       ->execute([$u['id'], 'Создание сервера ' . $name . ' (' . $plan . ')', $price, 'paid']);

    $ip = long2ip(crc32($name . microtime(true)) % 250 * 256 + 11);
    db()->prepare('INSERT INTO servers (user_id,name,os,plan,cpu,ram,disk,region,ip,status,power,note)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
       ->execute([$u['id'], $name, $os, $plan, $cpu, $ram, $disk, $rg, $ip, 'active', 'on', trim($_POST['note'] ?? '')]);

    $newId = (int)db()->lastInsertId();
    notify((int)$u['id'], 'Сервер «' . $name . '» запущен', "Тариф {$plan}, регион {$rg}, IP {$ip}. Списано " . fmt_money($price) . " за первый месяц.", 'ok', '/user/server.php?id=' . $newId);
    flash("Сервер «{$name}» создан. IP: {$ip}. Списано " . fmt_money($price) . " за первый месяц.");
    header('Location: /user/server.php?id=' . $newId);
    exit;
}

$title  = 'Новый сервер';
$active = 'new';
require __DIR__ . '/../includes/header.php';
?>
<div class="steps">
    <div class="step done"><b>1. Тариф</b></div>
    <div class="step done"><b>2. Образ ОС</b></div>
    <div class="step done"><b>3. Регион</b></div>
    <div class="step done"><b>4. Имя и запуск</b></div>
</div>

<form method="post" id="createForm">
<?= csrf_field() ?>
<div class="grid" style="grid-template-columns:1fr 320px;align-items:start;gap:1.2rem">
<div>
    <div class="card" style="margin-bottom:1.2rem"><div class="card-h"><h2>Шаг 1 — тариф</h2><span class="hint" style="margin:0">изменить можно позже в разделе «Настройка»</span></div>
    <div class="card-b">
        <div class="opt-grid" data-group="plan">
        <?php foreach ($PLANLIST as $i => $p): ?>
            <label class="opt <?= $p[0]===$prePlan?'sel':'' ?>" data-price="<?= $p[4] ?>" data-res="<?= $p[1] ?> vCPU · <?= fmt_ram($p[2]) ?> · <?= $p[3] ?> ГБ">
                <input type="radio" name="plan" value="<?= $p[0] ?>" hidden <?= $p[0]===$prePlan?'checked':'' ?>>
                <div class="oname"><?= $p[0] ?></div>
                <div class="odesc"><?= $p[1] ?> vCPU · <?= fmt_ram($p[2]) ?> · <?= $p[3] ?> ГБ NVMe<br><b><?= number_format($p[4],0,' ',' ') ?> ₽/мес</b></div>
            </label>
        <?php endforeach; ?>
        </div>
    </div></div>

    <div class="card" style="margin-bottom:1.2rem"><div class="card-h"><h2>Шаг 2 — образ системы</h2></div>
    <div class="card-b">
        <div class="opt-grid" data-group="os">
        <?php $first = true; foreach ($OS as $osName): ?>
            <label class="opt <?= $first?'sel':'' ?>">
                <input type="radio" name="os" value="<?= e($osName) ?>" hidden <?= $first?'checked':'' ?>>
                <div class="oname"><?= os_icon($osName) ?> <?= e($osName) ?></div>
            </label>
        <?php $first = false; endforeach; ?>
        </div>
    </div></div>

    <div class="card" style="margin-bottom:1.2rem"><div class="card-h"><h2>Шаг 3 — локация</h2></div>
    <div class="card-b">
        <div class="opt-grid" data-group="region">
        <?php $first = true; foreach ($REGIONS as $code => $desc): ?>
            <label class="opt <?= $first?'sel':'' ?>">
                <input type="radio" name="region" value="<?= $code ?>" hidden <?= $first?'checked':'' ?>>
                <div class="oname"><svg class="osi" viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg> <?= $code ?></div>
                <div class="odesc"><?= e($desc) ?></div>
            </label>
        <?php $first = false; endforeach; ?>
        </div>
    </div></div>

    <div class="card"><div class="card-h"><h2>Шаг 4 — параметры</h2></div>
    <div class="card-b">
        <div class="row">
            <div class="field"><label for="name">Имя сервера *</label>
                <input class="input" id="name" name="name" placeholder="web-prod-01" minlength="3" required>
                <div class="hint">латиница, цифры, дефисы — будет виден в панели и DNS-имени</div></div>
            <div class="field"><label for="note">Заметка</label>
                <input class="input" id="note" name="note" placeholder="например: под сайт и почту"></div>
        </div>
    </div></div>
</div>

<div class="summary">
    <div class="card"><div class="card-h"><h2>Ваш заказ</h2></div>
    <div class="card-b">
        <div class="sum-line"><span>Тариф</span><b id="sPlan">Standard</b></div>
        <div class="sum-line"><span>vCPU / RAM / диск</span><b id="sRes">2 / 2 ГБ / 40 ГБ</b></div>
        <div class="sum-line"><span>Образ</span><b id="sOs">Ubuntu 22.04 LTS</b></div>
        <div class="sum-line"><span>Регион</span><b id="sRegion">ru-msk-1</b></div>
        <div class="sum-total"><span>К оплате</span><span><span id="sPrice">590</span> ₽/мес</span></div>
        <p class="hint" style="margin:.7rem 0 1rem">Списание с баланса после нажатия. Сервер запускается автоматически.</p>
        <button class="btn btn-primary btn-lg" style="width:100%">Создать сервер</button>
    </div></div>
</div>
</div>
</form>

<script>
// интерактивный выбор карточек + живая сводка заказа
function resOf(p){ const o=document.querySelector(`input[name="plan"][value="${p}"]`).closest('.opt'); return o.dataset.res; }
document.querySelectorAll('.opt-grid').forEach(g => {
  g.addEventListener('click', ev => {
    const opt = ev.target.closest('.opt'); if (!opt) return;
    ev.preventDefault();
    g.querySelectorAll('.opt').forEach(o => o.classList.remove('sel'));
    opt.classList.add('sel');
    opt.querySelector('input').checked = true;
    update();
  });
});
function sel(name){ return document.querySelector(`input[name="${name}"]:checked`); }
function update(){
  const p = sel('plan').value, o = sel('os').value, r = sel('region').value;
  sPlan.textContent = p; sOs.textContent = o; sRegion.textContent = r;
  sRes.textContent = resOf(p);
  sPrice.textContent = Number(sel('plan').closest('.opt').dataset.price).toLocaleString('ru-RU');
}
update();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
