<?php
require_once __DIR__ . '/../includes/config.php';
$u = require_login();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM servers WHERE id = ? AND user_id = ?');
$st->execute([$id, $u['id']]);
$s = $st->fetch();
if (!$s) { flash('Сервер не найден.', 'err'); header('Location: /user/servers.php'); exit; }

// ---- POST-действия ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $act = $_POST['act'] ?? '';

    if ($act === 'power') {
        $target = $_POST['target'] ?? '';
        // «Перезагрузить» на выключенном сервере = включить
        if ($target === 'restart' && $s['power'] !== 'on') $target = 'start';
        $new = match ($target) { 'stop' => 'off', 'start', 'restart' => 'on', default => $s['power'] };
        db()->prepare('UPDATE servers SET power=? WHERE id=?')->execute([$new, $id]);
        $msg = ['start' => 'Сервер запущен.', 'stop' => 'Сервер остановлен.', 'restart' => 'Сервер перезагружается…'][$target] ?? 'Готово.';
        flash($msg);
        notify((int)$u['id'], $msg, "Сервер «{$s['name']}» ({$s['ip']})", $target === 'stop' ? 'warn' : 'ok', '/user/server.php?id=' . $id);
    }
    elseif ($act === 'settings') {
        db()->prepare('UPDATE servers SET name=?, auto_backup=?, backup_time=?, firewall=?, open_ports=?, ssh_keys=?, note=? WHERE id=?')
           ->execute([trim($_POST['name']) ?: $s['name'], isset($_POST['auto_backup']) ? 1 : 0,
                      preg_match('/^\d{2}:\d{2}$/', $_POST['backup_time'] ?? '') ? $_POST['backup_time'] : '03:00',
                      isset($_POST['firewall']) ? 1 : 0,
                      preg_replace('/[^0-9,\ ]/', '', $_POST['open_ports'] ?? ''),
                      trim($_POST['ssh_keys'] ?? ''), trim($_POST['note'] ?? ''), $id]);
        flash('Настройки сохранены. Правила файрвола применены.');
    }
    elseif ($act === 'rebuild') {
        $osNew = in_array($_POST['os_new'] ?? '', os_images(), true) ? $_POST['os_new'] : $s['os'];
        db()->prepare("UPDATE servers SET os=?, status='active', power='on' WHERE id=?")->execute([$osNew, $id]);
        flash('Сервер переустановлен: ' . $osNew . '. Данные на диске удалены.');
        notify((int)$u['id'], 'Система переустановлена', "«{$s['name']}»: установлена {$osNew}. Старые данные удалены безвозвратно.", 'warn', '/user/server.php?id=' . $id);
    }
    elseif ($act === 'resize') {
        $PL = plans();
        $pn = $_POST['plan_new'] ?? '';
        if (isset($PL[$pn])) {
            $p = $PL[$pn];
            db()->prepare('UPDATE servers SET plan=?, cpu=?, ram=?, disk=? WHERE id=?')->execute([$pn, $p['cpu'], $p['ram'], $p['disk'], $id]);
            flash('Ресурсы обновлены до тарифа ' . $pn . '. Требуется перезагрузка.');
            notify((int)$u['id'], 'Тариф изменён на ' . $pn, "«{$s['name']}»: {$p['cpu']} vCPU / " . fmt_ram($p['ram']) . " / {$p['disk']} ГБ. Перезагрузите сервер для применения.", 'info', '/user/server.php?id=' . $id);
        } else { flash('Неизвестный тариф.', 'err'); }
    }
    elseif ($act === 'make_backup') {
        $gb = round(2.1 + (random_int(1, 40)) / 10, 1);
        db()->prepare("INSERT INTO backups (server_id,kind,size_gb) VALUES (?, 'Ручная копия', ?)")->execute([$id, $gb]);
        flash('Резервная копия создана (' . $gb . ' ГБ).');
        notify((int)$u['id'], 'Бэкап создан', "Ручная копия «{$s['name']}» — {$gb} ГБ.", 'ok', '/user/server.php?id=' . $id);
    }
    elseif ($act === 'restore_backup') {
        $bkid = (int)($_POST['bk'] ?? 0);
        $q = db()->prepare('SELECT * FROM backups WHERE id=? AND server_id=?');
        $q->execute([$bkid, $id]);
        if ($q->fetch()) {
            flash('Восстановление запущено: диск «' . $s['name'] . '» будет возвращён к состоянию копии.');
            notify((int)$u['id'], 'Восстановление из бэкапа', "Сервер «{$s['name']}» восстанавливается из резервной копии.", 'warn', '/user/server.php?id=' . $id);
        } else { flash('Копия не найдена.', 'err'); }
    }
    elseif ($act === 'delete') {
        db()->prepare('DELETE FROM servers WHERE id=?')->execute([$id]);
        flash('Сервер «' . $s['name'] . '» удалён без возможности восстановления.');
        notify((int)$u['id'], 'Сервер удалён', "«{$s['name']}» ({$s['ip']}) удалён безвозвратно.", 'err');
        header('Location: /user/servers.php'); exit;
    }
    header('Location: /user/server.php?id=' . $id);
    exit;
}

// история копий: демо-ночные + реальные созданные
$q = db()->prepare('SELECT * FROM backups WHERE server_id=? ORDER BY id DESC');
$q->execute([$id]);
$backups = $q->fetchAll();
if (!$backups) {
    for ($d = 1; $d <= 5; $d++) {
        db()->prepare("INSERT INTO backups (server_id,kind,size_gb,created_at) VALUES (?,?,?,datetime('now',?))")
           ->execute([$id, $d === 1 ? 'Ночной снэпшот' : 'Ежедневный бэкап', round(2.1 + (($id * 13 + $d) % 40) / 10, 1), "-$d day"]);
    }
    $q->execute([$id]);
    $backups = $q->fetchAll();
}

$title  = $s['name'];
$active = 'servers';
require __DIR__ . '/../includes/header.php';

$cpu = 8 + abs($id * 7919 + (int)date('i') * 31) % 80;
$ram = 8 + abs(($id+100) * 7919 + (int)date('i') * 31) % 85;
?>
<div class="page-head">
    <div style="display:flex;align-items:center;gap:.8rem">
        <h1><?= os_icon($s['os']) ?> <?= e($s['name']) ?></h1>
        <span class="badge <?= $s['power']==='on'?'b-on':'b-off' ?>"><i class="dot"></i><?= $s['power']==='on'?'Запущен':'Остановлен' ?></span>
    </div>
    <div style="display:flex;gap:.45rem;flex-wrap:wrap">
      <?php $pw = fn($t,$l) => '<form method="post">'.csrf_field().'<input type="hidden" name="act" value="power"><input type="hidden" name="target" value="'.$t.'"><button class="btn btn-sm">'.e($l).'</button></form>'; ?>
      <?= $s['power']==='on' ? $pw('stop','Выключить') . $pw('restart','Перезагрузить') : $pw('start','Включить') ?>
    </div>
</div>

<div class="grid g4" style="margin-bottom:1.2rem">
    <div class="card stat"><div class="t">IP-адрес</div><div class="v" style="font-size:1.15rem"><code><?= e($s['ip']) ?></code></div><div class="s"><?= e($s['region']) ?></div></div>
    <div class="card stat"><div class="t">Тариф</div><div class="v" style="font-size:1.2rem"><?= e($s['plan']) ?></div><div class="s"><?= fmt_money(plan_price($s['plan'])) ?>/мес</div></div>
    <div class="card stat"><div class="t">CPU</div><div class="v" data-live="cpu"><?= $cpu ?>%</div><div class="meter"><i data-live-bar="cpu" style="width:<?= $cpu ?>%"></i></div></div>
    <div class="card stat"><div class="t">RAM</div><div class="v" data-live="ram"><?= $ram ?>%</div><div class="meter"><i data-live-bar="ram" style="width:<?= $ram ?>%"></i></div></div>
</div>

<div class="tabs">
    <a class="tab on" href="#overview">Обзор</a>
    <a class="tab" href="#console">Консоль</a>
    <a class="tab" href="#backups">Бэкапы</a>
    <a class="tab" href="#settings">Настройка</a>
    <a class="tab" href="#danger">Опасная зона</a>
</div>

<!-- ОБЗОР -->
<div class="grid g2" id="overview" style="margin-bottom:1.4rem">
    <div class="card"><div class="card-h"><h2>Характеристики</h2></div><div class="card-b">
        <dl class="kv">
            <dt>Операционная система</dt><dd><?= os_icon($s['os']) ?> <?= e($s['os']) ?></dd>
            <dt>vCPU</dt><dd><?= $s['cpu'] ?> ядер</dd>
            <dt>Оперативная память</dt><dd><?= fmt_ram((int)$s['ram']) ?></dd>
            <dt>Диск NVMe</dt><dd><?= $s['disk'] ?> ГБ</dd>
            <dt>Регион размещения</dt><dd><?= e($s['region']) ?></dd>
            <dt>Создан</dt><dd><?= e($s['created_at']) ?> UTC</dd>
            <dt>Заметка</dt><dd><?= e($s['note'] ?: '—') ?></dd>
        </dl>
    </div></div>
    <div class="card"><div class="card-h"><h2>Быстрые действия</h2></div><div class="card-b">
        <p style="color:var(--muted);margin-bottom:.9rem">SSH для подключения:</p>
        <code style="display:block;font-size:.9rem;padding:.6rem .8rem;background:#171d27;color:#c9d4e3;border-radius:6px;margin-bottom:.5rem" id="sshLine">ssh root@<?= e($s['ip']) ?></code>
        <button class="btn btn-sm" type="button" onclick="copyText(sshLine.textContent, this)">Скопировать команду</button>
        <p style="color:var(--muted);margin:.8rem 0 .5rem">Пароль root (измените его после первого входа):</p>
        <code style="display:block;font-size:.9rem;padding:.6rem .8rem;background:#171d27;color:#c9d4e3;border-radius:6px" id="passLine">••••••••••••••••</code>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.5rem">
            <button class="btn btn-sm" type="button" id="revealBtn">Показать пароль</button>
            <button class="btn btn-sm" type="button" id="genBtn">Сгенерировать новый</button>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.9rem">
            <a class="btn btn-sm" href="#console">Открыть консоль</a>
            <a class="btn btn-sm" href="#backups">Бэкапы</a>
            <a class="btn btn-sm" href="#settings">Настроить файрвол</a>
        </div>
    </div></div>
</div>

<!-- КОНСОЛЬ -->
<div class="card" id="console" style="margin-bottom:1.4rem">
    <div class="card-h"><h2>Веб-консоль</h2><span class="hint" style="margin:0">out-of-band терминал гипервизора · введите help для списка команд</span></div>
    <div class="card-b">
        <?php if ($s['power'] !== 'on'): ?><div class="alert alert-warn" style="margin-bottom:.7rem">Сервер выключен — консоль недоступна. Сначала включите сервер кнопкой выше.</div><?php endif; ?>
        <div class="console <?= $s['power']!=='on'?'off':'' ?>" id="term" tabindex="0"><span class="dim"><?= $s['power']==='on' ? 'Консоль не подключена. Нажмите «Подключиться».' : 'Сервер остановлен.' ?></span></div>
        <form id="cmdForm" autocomplete="off" style="display:none;gap:.5rem;margin-top:.6rem;align-items:center">
            <span class="cmd-prompt" id="cmdPrompt">root@<?= e($s['name']) ?>:~#</span>
            <input id="cmdInput" class="input cmd-input" placeholder="введите команду, например: top" aria-label="Команда консоли">
            <button type="button" class="btn btn-sm" id="sendBtn">Выполнить</button>
        </form>
        <div style="margin-top:.7rem;display:flex;gap:.5rem;flex-wrap:wrap">
            <button class="btn btn-sm btn-primary" id="connBtn">Подключиться</button>
            <button class="btn btn-sm" id="clearBtn">Очистить</button>
            <span class="conn-state" id="connState"></span>
        </div>
    </div>
</div>

<!-- БЭКАПЫ -->
<div class="card" id="backups" style="margin-bottom:1.4rem">
    <div class="card-h"><h2>Резервные копии</h2>
        <span class="badge <?= $s['auto_backup'] ? 'b-on' : 'b-off' ?>">Автобэкап в <?= e($s['backup_time']) ?></span></div>
    <table>
        <thead><tr><th>Дата создания</th><th>Тип</th><th>Размер</th><th>Статус</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($backups as $bk): ?>
            <tr>
                <td><?= e(date('Y-m-d H:i', strtotime($bk['created_at'] . ' UTC'))) ?> UTC</td>
                <td><?= e($bk['kind']) ?></td>
                <td><?= number_format($bk['size_gb'], 1, ',', ' ') ?> ГБ</td>
                <td><span class="badge b-on"><i class="dot"></i>Готов</span></td>
                <td style="text-align:right">
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?><input type="hidden" name="act" value="restore_backup"><input type="hidden" name="bk" value="<?= (int)$bk['id'] ?>">
                        <button class="btn btn-sm" data-confirm="Восстановить диск из копии от <?= e($bk['created_at']) ?>? Текущие данные будут заменены.">Восстановить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$backups): ?><tr><td colspan="5" style="color:var(--muted)">Копий пока нет — создайте первую вручную или дождитесь автобэкапа.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <div class="card-b" style="border-top:1px solid var(--line)">
        <form method="post" style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap">
            <?= csrf_field() ?><input type="hidden" name="act" value="make_backup">
            <button class="btn btn-primary btn-sm">Создать резервную копию сейчас</button>
            <span class="hint" style="margin:0">Хранение копий бесплатно, глубина истории — 14 дней.</span>
        </form>
    </div>
</div>

<!-- НАСТРОЙКА -->
<div class="card" id="settings" style="margin-bottom:1.4rem">
    <div class="card-h"><h2>Настройка сервера</h2></div>
    <div class="card-b">
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="settings">
        <div class="row">
            <div class="field"><label>Имя сервера</label><input class="input" name="name" value="<?= e($s['name']) ?>"></div>
            <div class="field"><label>Заметка</label><input class="input" name="note" value="<?= e($s['note']) ?>"></div>
        </div>

        <h2 style="margin:.4rem 0 .8rem">Резервное копирование</h2>
        <div class="row" style="align-items:center">
            <div class="field" style="display:flex;align-items:center;gap:.7rem">
                <label class="switch"><input type="checkbox" name="auto_backup" <?= $s['auto_backup']?'checked':'' ?>><span class="slider"></span></label>
                <label style="margin:0">Ежедневные автоматические бэкапы</label>
            </div>
            <div class="field" style="max-width:160px"><label>Время выполнения</label>
                <input class="input" type="time" name="backup_time" value="<?= e($s['backup_time']) ?>"></div>
        </div>

        <h2 style="margin:1rem 0 .8rem">Файрвол</h2>
        <div class="row" style="align-items:center">
            <div class="field" style="display:flex;align-items:center;gap:.7rem">
                <label class="switch"><input type="checkbox" name="firewall" <?= $s['firewall']?'checked':'' ?>><span class="slider"></span></label>
                <label style="margin:0">Включить сетевой экран</label>
            </div>
            <div class="field"><label>Открытые порты (через запятую)</label>
                <input class="input" name="open_ports" value="<?= e($s['open_ports']) ?>" placeholder="22,80,443">
                <div class="hint">Закрытые порты блокируются на уровне гипервизора. Порт 22 — SSH, 80/443 — веб.</div></div>
        </div>

        <h2 style="margin:1rem 0 .5rem">SSH-ключи</h2>
        <div class="field"><textarea name="ssh_keys" placeholder="ssh-ed25519 AAAA... user@machine&#10;ssh-rsa AAAAB3..."><?= e($s['ssh_keys']) ?></textarea>
            <div class="hint">По одному публичному ключу на строку. Ключи добавляются в authorized_keys при следующем включении.</div></div>

        <button class="btn btn-primary">Сохранить настройки</button>
    </form>
    </div>
</div>

<!-- ОПАСНАЯ ЗОНА -->
<div class="card danger-zone" id="danger">
    <div class="card-h"><h2 style="color:var(--err)">Опасная зона</h2></div>
    <div class="card-b" style="display:flex;flex-direction:column;gap:1.1rem">

        <form method="post" style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center">
            <?= csrf_field() ?><input type="hidden" name="act" value="rebuild">
            <div><b>Переустановить систему</b><br><span style="color:var(--muted);font-size:.88rem">Диск будет переразбит, все данные удалятся.</span></div>
            <div style="display:flex;gap:.5rem">
                <select name="os_new" style="width:auto">
                    <?php foreach (['Ubuntu 22.04 LTS','Ubuntu 24.04 LTS','Debian 12','CentOS Stream 9','AlmaLinux 9','Windows Server 2022'] as $o): ?>
                        <option <?= $o===$s['os']?'selected':'' ?>><?= e($o) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-danger" onclick="return confirm('Переустановить сервер? Данные будут удалены.')">Переустановить</button>
            </div>
        </form>

        <form method="post" style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;border-top:1px solid var(--line);padding-top:1.1rem">
            <?= csrf_field() ?><input type="hidden" name="act" value="resize">
            <div><b>Изменить тариф</b><br><span style="color:var(--muted);font-size:.88rem">Ресурсы применятся после перезагрузки, цена — со следующего периода.</span></div>
            <div style="display:flex;gap:.5rem">
                <select name="plan_new" id="planNew" style="width:auto">
                    <?php foreach ([['Start',1,1024,20],['Standard',2,2048,40],['Pro',4,8192,100],['Enterprise',8,16384,240]] as $p): ?>
                        <option value="<?= $p[0] ?>" data-cpu="<?= $p[1] ?>" data-ram="<?= $p[2] ?>" data-disk="<?= $p[3] ?>" <?= $p[0]===$s['plan']?'selected':'' ?>><?= $p[0] ?> — <?= fmt_money(plan_price($p[0])) ?>/мес</option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="cpu" id="rCpu"><input type="hidden" name="ram" id="rRam"><input type="hidden" name="disk" id="rDisk">
                <button class="btn" onclick="syncPlan()">Применить</button>
            </div>
        </form>

        <form method="post" style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;border-top:1px solid var(--line);padding-top:1.1rem">
            <?= csrf_field() ?><input type="hidden" name="act" value="delete">
            <div><b style="color:var(--err)">Удалить сервер</b><br><span style="color:var(--muted);font-size:.88rem">Необратимо. Сервер, данные и IP будут освобождены.</span></div>
            <button class="btn btn-danger" onclick="return confirm('Удалить сервер «<?= e($s['name']) ?>» навсегда?')">Удалить навсегда</button>
        </form>
    </div>
</div>

<script>
// ===== Тариф: синхронизация скрытых полей ресурсов =====
window.syncPlan = function(){
  const sel=document.getElementById('planNew'); if(!sel) return;
  const o=sel.selectedOptions[0];
  document.getElementById('rCpu').value=o.dataset.cpu;
  document.getElementById('rRam').value=o.dataset.ram;
  document.getElementById('rDisk').value=o.dataset.disk;
};
syncPlan();

// ===== Пароль root: показ, копирование, генерация =====
let ROOT_PASS = null;
function randPass(){
  const up='ABCDEFGHJKLMNPQRSTUVWXYZ', lo='abcdefghijkmnpqrstuvwxyz', dg='23456789', sy='!@#$%*+-=?';
  const pick = s => s[Math.floor(Math.random()*s.length)];
  let p = pick(up)+pick(up)+pick(lo)+pick(lo)+pick(lo)+pick(lo)+pick(dg)+pick(dg)+pick(sy)+sy.repeat(Math.floor(Math.random()*3));
  return p.split('').sort(()=>Math.random()-0.5).join('');
}
function copyText(txt, btn){
  const done = () => { if(!btn) return; const o=btn.textContent; btn.textContent='Скопировано'; btn.disabled=true; setTimeout(()=>{btn.textContent=o;btn.disabled=false;},1600); };
  if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(txt).then(done).catch(()=>fallbackCopy(txt,done));
  else fallbackCopy(txt, done);
}
function fallbackCopy(txt, done){
  const ta=document.createElement('textarea'); ta.value=txt; ta.style.position='fixed'; ta.style.opacity='0';
  document.body.appendChild(ta); ta.select();
  try{ document.execCommand('copy'); }catch(e){}
  ta.remove(); done();
}
(function(){
  const el=document.getElementById('passLine'), btn=document.getElementById('revealBtn'), genBtn=document.getElementById('genBtn');
  if(!el||!btn||!genBtn) return;
  let shown=false;
  btn.addEventListener('click',()=>{
    if(!ROOT_PASS) ROOT_PASS=randPass();
    shown=!shown;
    el.textContent = shown ? ROOT_PASS : '••••••••••••••••';
    btn.textContent = shown ? 'Скрыть пароль' : 'Показать пароль';
  });
  genBtn.addEventListener('click',()=>{
    ROOT_PASS=randPass(); shown=true;
    el.textContent=ROOT_PASS; btn.textContent='Скрыть пароль';
    toast('Новый пароль root сгенерирован. Скопируйте его сейчас — после перезагрузки страницы он не сохранится.','ok');
  });
})();

// ===== Интерактивная веб-консоль (реальный запрос к /api/console.php) =====
(function(){
  const term=document.getElementById('term'), form=document.getElementById('cmdForm'),
        input=document.getElementById('cmdInput'), connBtn=document.getElementById('connBtn'),
        clearBtn=document.getElementById('clearBtn'), state=document.getElementById('connState');
  const CSRF=document.querySelector('meta[name=csrf]').content, SID=<?= (int)$s['id'] ?>;
  let connected=false, busy=false, hist=[], hi=-1;

  function esc(s){const d=document.createElement('span');d.textContent=s;return d.innerHTML;}
  function print(c,t){const s=document.createElement('span');if(c)s.className=c;s.textContent=t+'\n';term.appendChild(s);term.scrollTop=term.scrollHeight;}
  function api(payload){
    return fetch('/api/console.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
      .then(r=>r.json());
  }
  function render(lines){
    if(!Array.isArray(lines)) lines=[{c:'er',t:String(lines)}];
    for(const l of lines){ if(l.t==='__CLEAR__'){term.innerHTML='';continue;} print(l.c,l.t); }
  }
  function connect(){
    if(busy) return;
    if(term.classList.contains('off')){ state.textContent='сервер выключен — сначала включите его'; state.className='conn-state bad'; return; }
    busy=true; connBtn.disabled=true; state.textContent='подключение…';
    api({server:SID,boot:true,csrf:CSRF}).then(d=>{
      term.innerHTML=''; render(d.out);
      connected=true; form.style.display='flex'; input.focus();
      state.textContent=d.err||d.cls ? '' : 'соединено · pts/0';
      state.className='conn-state '+(d.cls==='er'?'bad':'good');
      connBtn.textContent='Переподключиться'; connBtn.disabled=false; busy=false;
    }).catch(()=>{state.textContent='ошибка соединения';state.className='conn-state bad';connBtn.disabled=false;busy=false;});
  }
  function run(cmd){
    if(!connected||busy) return; busy=true;
    print('','root@'+<?= json_encode($s['name']) ?>+':~# '+cmd);
    hist.push(cmd); hi=hist.length;
    api({server:SID,cmd:cmd,csrf:CSRF}).then(d=>{
      render(d.out); busy=false;
      if(cmd.trim()==='exit'){connected=false;form.style.display='none';connBtn.textContent='Подключиться';state.textContent='отключено';state.className='conn-state';}
      else input.focus();
    }).catch(()=>{print('er','console: network error');busy=false;input.focus();});
  }
  connBtn.addEventListener('click',connect);
  clearBtn.addEventListener('click',()=>{term.innerHTML='';});
  form.addEventListener('submit',e=>{e.preventDefault();const v=input.value.trim();input.value='';if(v)run(v);});
  input.addEventListener('keydown',e=>{
    if(e.key==='ArrowUp'){e.preventDefault();if(hi>0){hi--;input.value=hist[hi]||'';}}
    if(e.key==='ArrowDown'){e.preventDefault();if(hi<hist.length){hi++;input.value=hist[hi]||'';if(hi===hist.length)input.value='';}}
  });
  term.addEventListener('click',()=>{if(connected)input.focus();});
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
