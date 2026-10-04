<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
$u = current_user();
if (!$u) { http_response_code(401); echo json_encode(['out'=>'','err'=>'auth']); exit; }

$body = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
$sent = $body['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if ($sent !== csrf_token()) { http_response_code(400); echo json_encode(['out'=>'','err'=>'csrf']); exit; }

$sid  = (int)($body['server'] ?? 0);
$cmd  = trim((string)($body['cmd'] ?? ''));
$boot = !empty($body['boot']);

$st = db()->prepare('SELECT * FROM servers WHERE id=? AND user_id=?');
$st->execute([$sid, $u['id']]);
$s = $st->fetch();
if (!$s) { http_response_code(404); echo json_encode(['out'=>'Сервер не найден.','err'=>'404']); exit; }

// генератор псевдо-хоста на основе ip+id — стабильные «файлы»
$seed = crc32($s['ip'] . '|' . $s['name']);
function rnd(int &$seed): int { $seed = ($seed * 1103515245 + 12345) & 0x7fffffff; return $seed; }

$out = [];
if ($boot) {
    $kern = str_contains(mb_strtolower($s['os']), 'ubuntu') ? '5.15.0-91-generic'
         : (str_contains(mb_strtolower($s['os']), 'debian') ? '6.1.0-13-amd64' : '5.14.0-284.el9.x86_64');
    if ($s['power'] !== 'on') {
        echo json_encode(['out'=>"Сервер выключен. Включите его кнопкой «Включить», затем нажмите «Подключиться».",'cls'=>'er']); exit;
    }
    $out[] = ['c'=>'dim','t'=>'GNU/Linux ' . $kern . ' (' . e($s['name']) . ') — out-of-band console'];
    $out[] = ['c'=>'ok','t'=>'[  OK  ] Started Network Manager.'];
    $out[] = ['c'=>'ok','t'=>'[  OK  ] Reached target Multi-User System.'];
    $out[] = ['c'=>'ok','t'=>'[  OK  ] Listening on SSH Daemon.'];
    $out[] = ['c'=>'','t'=>'Последний вход: ' . date('D M j H:i:s %Y') . ' из 10.0.0.5 (pts/0)'];
    echo json_encode(['out'=>$out]); exit;
}

if ($cmd === '') { echo json_encode(['out'=>[]]); exit; }

// журнал реальных команд веб-консоли (для history и аудита)
db()->prepare('INSERT INTO console_log (server_id, body) VALUES (?, ?)')->execute([$sid, mb_substr($cmd, 0, 200)]);

$host = preg_replace('/[^a-z0-9\-]/i', '', explode('.', mb_strtolower($s['name']))[0]) ?: 'srv';
$ps = function() use (&$seed) {
    $rows = [
        ['1','root','systemd','1.2','0.1','S','/usr/lib/systemd/systemd --system'],
        ['412','root','sshd: /usr/sbin/sshd','0.3','0.4','Ss',''],
        ['884','www-data','nginx: worker process','1.8','2.2','S','nginx: worker process'],
    ];
    if (rnd($seed) % 2) $rows[] = ['1024','postgres','postgres: writer','0.9','6.1','Ss','postgres: checkpointer'];
    $rows[] = ['2048','root','top -b -n 1','0.0','0.1','R+',''];
    return $rows;
};

$argv = preg_split('/\s+/', $cmd);
$c = mb_strtolower(array_shift($argv));

switch ($c) {
    case 'help':
        foreach (['Доступные команды:','  help whoami hostname uname uptime date pwd ls cd cat echo touch mkdir rm mv cp','  ps top df free netstat ss ip ping curl systemctl journalctl apt/yum/dnf','  history clear reboot exit'] as $l)
            $out[] = ['c'=>'dim','t'=>$l];
        break;
    case 'whoami': $out[]=['c'=>'','t'=>'root']; break;
    case 'hostname': $out[]=['c'=>'','t'=>$host]; break;
    case 'uname':
        $flag = $argv[0] ?? '';
        $kern = str_contains(mb_strtolower($s['os']),'ubuntu')?'5.15.0-91-generic':(str_contains(mb_strtolower($s['os']),'debian')?'6.1.0-13-amd64':'5.14.0-284.el9');
        $out[]=['c'=>'','t'=>$flag==='-a' ? "Linux {$host} {$kern} #1 SMP Debian x86_64 GNU/Linux" : 'Linux'];
        break;
    case 'uptime':
        $up = 3 + abs($seed % 40);
        $out[]=['c'=>'','t'=>sprintf('%s up %d days, %2d:%02d,  1 user,  load average: %.2f, %.2f, %.2f',
            date('H:i:s'), $up, rnd($seed)%24, rnd($seed)%60, 0.12+rnd($seed)%40/100, 0.08+rnd($seed)%30/100, 0.05+rnd($seed)%20/100)];
        break;
    case 'ps':
        $out[]=['c'=>'dim','t'=>'  PID USER     %CPU %MEM STAT COMMAND'];
        foreach ($ps() as $r) $out[]=['c'=>'','t'=>sprintf('%5s %-8s %4s %4s %-4s %s',...$r)];
        break;
    case 'df':
        $used = 8 + rnd($seed)%50;
        $out[]=['c'=>'dim','t'=>'Filesystem      Size  Used Avail Use% Mounted on'];
        $out[]=['c'=>'','t'=>sprintf('/dev/vda1        %dG  %d%%  %dG  %d%% /', $s['disk'], round($s['disk']*$used/100), $s['disk']-round($s['disk']*$used/100), $used)];
        $out[]=['c'=>'','t'=>'tmpfs            1.9G     0  1.9G   0% /dev/shm'];
        break;
    case 'free':
        $ramM = $s['ram']; $usedM = (int)($ramM*(0.3+rnd($seed)%40/100));
        $out[]=['c'=>'dim','t'=>'               total        used        free      shared  buff/cache   available'];
        $out[]=['c'=>'','t'=>sprintf('Mem:       %8d     %8d     %8d      %8d     %8d     %8d',$ramM,$usedM,$ramM-$usedM,12, 90000, $ramM-$usedM+40000)];
        break;
    case 'top':
        $out[]=['c'=>'dim','t'=>'top - '.date('H:i:s').' up 1 day, load average: 0.14, 0.09, 0.03'];
        $out[]=['c'=>'','t'=>'Tasks:  84 total,   1 running,  83 sleeping'];
        $out[]=['c'=>'','t'=>'%Cpu(s):  '.(2+rnd($seed)%8).'.0 us,  1.0 sy,  '.(80+rnd($seed)%15).'.0 id'];
        foreach ($ps() as $r) $out[]=['c'=>'','t'=>sprintf('%5s %8s %4s %4s   S  %s',...array_slice($r,0,7))];
        break;
    case 'netstat': case 'ss':
        $out[]=['c'=>'dim','t'=>'Proto Recv-Q Send-Q Local Address           State       Service'];
        foreach (explode(',', str_replace(' ','',$s['open_ports'])) as $p) {
            $svc = ['22'=>'ssh','80'=>'http','443'=>'https','3306'=>'mysql','5432'=>'postgresql','8080'=>'http-alt'][$p] ?? 'unknown';
            $out[]=['c'=>'','t'=>sprintf('tcp        0      0 0.0.0.0:%-15s LISTEN      %s', $p, $svc)];
        }
        if (!$s['firewall']) $out[]=['c'=>'wr','t'=>'ВНИМАНИЕ: файрвол отключён — открыты все порты узла.'];
        break;
    case 'ip':
        $out[]=['c'=>'','t'=>'1: lo: <LOOPBACK,UP> mtu 65536'];
        $out[]=['c'=>'','t'=>'    inet 127.0.0.1/8 scope host lo'];
        $out[]=['c'=>'','t'=>'2: eth0: <BROADCAST,MULTICAST,UP> mtu 1500'];
        $out[]=['c'=>'','t'=>'    inet '.$s['ip'].'/24 brd '.preg_replace('/\.\d+$/','.255',$s['ip']).' scope global eth0'];
        break;
    case 'ping':
        $tgt = $argv[0] ?? '8.8.8.8';
        for ($i=1;$i<=4;$i++) $out[]=['c'=>'','t'=>sprintf('64 bytes from %s: icmp_seq=%d ttl=57 time=%d.%d ms',$tgt,$i,2+rnd($seed)%30,rnd($seed)%1000)];
        $out[]=['c'=>'dim','t'=>sprintf('--- %s ping statistics --- 4 transmitted, 4 received, 0%% packet loss',$tgt)];
        break;
    case 'curl':
        $url = end($argv) ?: '';
        if (preg_match('#^(https?://)#',$url)) {
            $out[]=['c'=>'dim','t'=>'HTTP/1.1 200 OK  Date: '.gmdate('D, d M Y H:i:s').' GMT  Server: nginx'];
        } else $out[]=['c'=>'er','t'=>'curl: (3) URL rejected: Bad hostname — укажите http(s)://…'];
        break;
    case 'systemctl':
        $sub=$argv[0]??'status'; $unit=preg_replace('/\.service$/','',$argv[1]??'');
        if ($sub==='status'&&$unit) $out[]=['c'=>'','t'=>"● {$unit}.service - {$unit}\n   Active: active (running) since ".date('D')." ".date('H:i:s')."; 3 days ago\n   Main PID: 412 ({$unit})"];
        elseif ($sub==='list-units') { foreach (['cron','nginx','ssh','fail2ban'] as $un) $out[]=['c'=>'ok','t'=>"● {$un}.service loaded active running {$un} daemon"]; }
        else $out[]=['c'=>'dim','t'=>'Usage: systemctl status|list-units [unit]'];
        break;
    case 'journalctl':
        foreach ([['ok','Accepted publickey for root from 10.0.0.5'],['dim','systemd[1]: Started Session 2 of user root.'],['wr','Failed to open config /etc/app.conf (fallback applied)'],['ok','Backup snapshot '.$s['backup_time'].' completed']] as $l)
            $out[]=['c'=>$l[0],'t'=>date('H:i:s').' '.$host.' '.$l[1]];
        break;
    case 'apt': case 'apt-get': case 'yum': case 'dnf':
        $sub=$argv[0]??'';
        if ($sub==='update') $out[]=['c'=>'ok','t'=>'Все списки пакетов актуальны. Обновлений нет.'];
        elseif ($sub==='install') $out[]=['c'=>'er','t'=>'Демо-режим: установка пакетов из веб-консоли отключена. Используйте SSH: ssh root@'.$s['ip']];
        else $out[]=['c'=>'dim','t'=>'Usage: '.$c.' update|install <package>'];
        break;
    case 'history':
        $rows = db()->prepare('SELECT body FROM console_log WHERE server_id=? ORDER BY id DESC LIMIT 20');
        $rows->execute([$sid]);
        $real = array_reverse($rows->fetchAll(PDO::FETCH_COLUMN));
        if ($real) { foreach ($real as $i => $h) $out[] = ['c' => 'dim', 't' => sprintf('%4d  %s', $i + 1, $h)]; }
        else { foreach (['ls -la', 'apt update', 'systemctl status nginx', 'df -h', 'free -m'] as $i => $h) $out[] = ['c' => 'dim', 't' => sprintf('%4d  %s', $i + 1, $h)]; }
        break;
    case 'reboot':
        db()->prepare("UPDATE servers SET power='on' WHERE id=?")->execute([$sid]);
        $out[]=['c'=>'wr','t'=>'Broadcast message: The system is going down for reboot NOW!'];
        $out[]=['c'=>'ok','t'=>'Сервер перезагружается — через несколько секунд будет доступен.'];
        break;
    case 'exit':
        $out[]=['c'=>'dim','t'=>'logout / Connection to console closed.']; break;
    case 'clear':
        $out[]=['c'=>'','t'=>'__CLEAR__']; break;
    case 'date': $out[]=['c'=>'','t'=>date('D M j H:i:s T Y')]; break;
    case 'pwd': $out[]=['c'=>'','t'=>'/root']; break;
    case 'echo':
        $out[]=['c'=>'','t'=>implode(' ', $argv)]; break;
    case 'ls': {
        $arg = $argv[0] ?? '';
        if ($arg === '-la' || $arg === '-l' || $arg === '-al') $long = true; else $long = false;
        $files = ['.','..','backup','nginx.conf','console.log','index.html','.ssh','setup.sh'];
        foreach ($files as $f) {
            if ($arg !== '' && !in_array($arg, ['-la','-l','-al'], true) && strpos($f, $arg) !== 0) continue;
            if (!$long) { $out[]=['c'=>'','t'=>$f]; continue; }
            $isDir = in_array($f, ['.','..','.ssh'], true);
            $sz = $isDir ? 4096 : rnd($seed) % 512000 + 128;
            $perm = $isDir ? 'drwxr-xr-x' : '-rw-r--r--';
            $out[]=['c'=>'','t'=>sprintf('%s 2 root root %8d %s %s', $perm, $sz, date('M j H:i', time() - rnd($seed)%8640000), $f)];
        }
        break; }
    case 'cat': {
        $f = $argv[0] ?? '';
        if ($f === '') { $out[]=['c'=>'er','t'=>'cat: отсутствие аргумента — usage: cat <file>']; break; }
        if ($f === 'nginx.conf') {
            foreach (['server {','    listen 80;','    server_name '.$host.';','    root /var/www/html;','    location / { proxy_pass http://127.0.0.1:8080; }','}'] as $l) $out[]=['c'=>'','t'=>$l];
        } elseif ($f === 'index.html') {
            $out[]=['c'=>'','t'=>'<!DOCTYPE html><html><body><h1>It works!</h1></body></html>'];
        } elseif ($f === 'setup.sh') {
            foreach (['#!/bin/bash','apt update && apt install -y nginx fail2ban ufw','ufw allow 22,80,443/tcp','systemctl enable --now nginx'] as $l) $out[]=['c'=>'grn','t'=>$l];
        } elseif ($f === 'console.log') {
            $st = db()->prepare('SELECT body FROM console_log WHERE server_id=? ORDER BY id DESC LIMIT 5');
            $st->execute([$sid]);
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $l) $out[]=['c'=>'dim','t'=>date('H:i:s').' exec: '.$l];
        } else {
            $out[]=['c'=>'er','t'=>"cat: {$f}: No such file or directory"];
        }
        break; }
    case 'touch': case 'mkdir': case 'cp': case 'mv':
        if (($argv[0] ?? '') === '') { $out[]=['c'=>'er','t'=>$c.': missing operand']; break; }
        $out[]=['c'=>'ok','t'=>$c.': выполнено']; break;
    case 'rm':
        $args = array_slice($argv, 1);
        if ($args === []) { $out[]=['c'=>'er','t'=>'rm: missing operand']; break; }
        // защита: rm рекурсивно-принудительно по корню или ключевым каталогам
        $joined = implode(' ', $args);
        if (preg_match('/-[a-z]*r[a-z]*f?|-rf|--recursive/i', $joined) && preg_match('#(\s|^)/(\s|$|/?\*?$)|/etc|/boot|/usr|/var\b#i', $joined)) {
            $out[]=['c'=>'er','t'=>'rm: отказ: удаление корневой или системной файловой системы заблокировано защитой панели'];
            break;
        }
        $out[]=['c'=>'ok','t'=>'rm: выполнено']; break;
    case 'cd': $out[]=['c'=>'','t'=>'']; break;
    default:
        $out[]=['c'=>'er','t'=>"{$c}: command not found — введите help для списка команд."];
}
echo json_encode(['out'=>$out]);
