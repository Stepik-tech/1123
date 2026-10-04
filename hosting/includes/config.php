<?php
declare(strict_types=1);

// Совместимость с окружениями без mbstring
require_once __DIR__ . '/mb_compat.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('APP_NAME', 'VDSmart');
define('DB_PATH', __DIR__ . '/../data/app.sqlite');

// ---- Кэш ресурсов (версия = mtime style.css) --------------------------------
function asset(string $rel): string
{
    $file = __DIR__ . '/../' . ltrim($rel, '/');
    $v = file_exists($file) ? substr(sprintf('%x', (int)filemtime($file)), -6) : '1';
    return '/' . ltrim($rel, '/') . '?v=' . $v;
}

// ---- База данных -----------------------------------------------------------
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        if (!is_dir(dirname(DB_PATH))) {
            mkdir(dirname(DB_PATH), 0775, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        migrate($pdo);
        auto_login_from_cookie($pdo);
    }
    return $pdo;
}

// «Запомнить меня»: автологин по безопасной куке (хеш токена хранится в БД)
function auto_login_from_cookie(PDO $pdo): void
{
    if (!empty($_SESSION['uid']) || empty($_COOKIE['vds_remember'])) return;
    [$rid, $rtok] = array_pad(explode(':', (string)$_COOKIE['vds_remember'], 2), 2, '');
    if ($rid === '' || $rtok === '') return;
    $q = $pdo->prepare('SELECT user_id FROM remember_tokens WHERE user_id=? AND token_hash=? AND expires_at > datetime(\'now\')');
    $q->execute([(int)$rid, hash('sha256', $rtok)]);
    if ($tk = $q->fetchColumn()) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$tk;
    }
}

function migrate(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        pass TEXT NOT NULL,
        balance REAL NOT NULL DEFAULT 0,
        is_admin INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS servers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL REFERENCES users(id),
        name TEXT NOT NULL,
        os TEXT NOT NULL,
        plan TEXT NOT NULL,
        cpu INTEGER NOT NULL,
        ram INTEGER NOT NULL,
        disk INTEGER NOT NULL,
        region TEXT NOT NULL,
        ip TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'active',
        power TEXT NOT NULL DEFAULT 'on',
        auto_backup INTEGER NOT NULL DEFAULT 1,
        backup_time TEXT NOT NULL DEFAULT '03:00',
        firewall INTEGER NOT NULL DEFAULT 1,
        open_ports TEXT NOT NULL DEFAULT '22,80,443',
        ssh_keys TEXT NOT NULL DEFAULT '',
        note TEXT NOT NULL DEFAULT '',
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL REFERENCES users(id),
        title TEXT NOT NULL,
        amount REAL NOT NULL,
        status TEXT NOT NULL DEFAULT 'new',
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL REFERENCES users(id),
        type TEXT NOT NULL DEFAULT 'info',
        title TEXT NOT NULL,
        message TEXT NOT NULL DEFAULT '',
        link TEXT NOT NULL DEFAULT '',
        is_read INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL REFERENCES users(id),
        subject TEXT NOT NULL,
        category TEXT NOT NULL DEFAULT 'general',
        status TEXT NOT NULL DEFAULT 'open',
        priority TEXT NOT NULL DEFAULT 'normal',
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        answered_at TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ticket_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_id INTEGER NOT NULL REFERENCES tickets(id),
        author_id INTEGER NOT NULL REFERENCES users(id),
        body TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS backups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        server_id INTEGER NOT NULL REFERENCES servers(id),
        kind TEXT NOT NULL DEFAULT 'Ежедневный бэкап',
        size_gb REAL NOT NULL DEFAULT 2.5,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS console_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        server_id INTEGER NOT NULL REFERENCES servers(id),
        body TEXT NOT NULL DEFAULT '',
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL REFERENCES users(id),
        token_hash TEXT NOT NULL,
        expires_at TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pass_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        token_hash TEXT NOT NULL,
        expires_at TEXT NOT NULL,
        used INTEGER NOT NULL DEFAULT 0
    )");

    // миграция: таблица сессий для «Активных сессий» в профиле
    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL REFERENCES users(id),
        sid TEXT NOT NULL,
        ip TEXT NOT NULL DEFAULT '',
        ua TEXT NOT NULL DEFAULT '',
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        last_seen TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    // демо-данные: админ + тестовый юзер с сервером
    $cnt = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($cnt === 0) {
        $st = $pdo->prepare('INSERT INTO users (name,email,pass,balance,is_admin) VALUES (?,?,?,?,?)');
        $st->execute(['Администратор', 'admin@vdsmart.local', password_hash('admin123', PASSWORD_DEFAULT), 99999, 1]);
        $st->execute(['Иван Петров', 'ivan@demo.local', password_hash('demo123', PASSWORD_DEFAULT), 1240.50, 0]);

        $plans = [
            ['Start', 1, 1024, 20], ['Standard', 2, 2048, 40],
            ['Pro', 4, 8192, 100], ['Enterprise', 8, 16384, 240],
        ];
        $sts = $pdo->prepare(
            'INSERT INTO servers (user_id,name,os,plan,cpu,ram,disk,region,ip,status,power,note)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $sts->execute([2, 'web-prod-01', 'Ubuntu 22.04 LTS', 'Standard', 2, 2048, 40, 'ru-msk-1', '185.211.84.12', 'active', 'on', 'Основной сайт']);
        $sts->execute([2, 'db-master', 'Debian 12', 'Pro', 4, 8192, 100, 'ru-msk-1', '185.211.84.47', 'active', 'on', 'PostgreSQL 16']);
        $sts->execute([2, 'game-hs', 'Windows Server 2022', 'Enterprise', 8, 16384, 240, 'nl-ams-2', '45.140.200.9', 'rebuild', 'off', '']);

        $nt = $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,link,is_read,created_at) VALUES (?,?,?,?,?,?,datetime('now',?))");
        $nt->execute([2,'ok','Сервер web-prod-01 запущен','IP 185.211.84.12 назначен. Система Ubuntu 22.04 LTS готова к работе.','','1','-2 hours']);
        $nt->execute([2,'info','Ежедневный бэкап выполнен','Снэпшот db-master (3.4 ГБ) сохранён в регозане ru-msk-1.','','1','-7 hours']);
        $nt->execute([2,'warn','Плановые работы 12.10 03:00 UTC','В регионе nl-ams-2 будет обновлена сеть. Простой до 2 минут.','','0','-20 minutes']);
        $nt->execute([2,'info','Баланс пополнен на 1 000 ₽','Платёж принят, средства зачислены мгновенно.','','0','-10 minutes']);

        $tk = $pdo->prepare("INSERT INTO tickets (user_id,subject,category,status,priority,created_at,answered_at) VALUES (?,?,?,?,?,datetime('now',?),datetime('now',?))");
        $tk->execute([2,'Не проходит scp-выгрузка на game-hs','network','open','normal','-1 day','-22 hours']);
        $tid=(int)$pdo->lastInsertId();
        $tm = $pdo->prepare("INSERT INTO ticket_messages (ticket_id,author_id,body,created_at) VALUES (?,?,?,datetime('now',?))");
        $tm->execute([$tid,2,'Здравствуйте! При копировании бэкапа (~4 ГБ) соединение обрывается на 60%. Что проверить?','-1 day']);
        $tm->execute([$tid,1,'Добрый день! Ограничение на стороне сессии SSH при large files. Отправьте, пожалуйста, вывод `ulimit -a` — увеличим лимиты на узле.','-22 hours']);
    }
}

// ---- Помощники -------------------------------------------------------------
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// ---- Уведомления ------------------------------------------------------------
function purge_expired_tokens(): void
{
    db()->exec("DELETE FROM remember_tokens WHERE expires_at <= datetime('now')");
    db()->exec("DELETE FROM pass_resets WHERE expires_at <= datetime('now')");
}

function notify(int $userId, string $title, string $message = '', string $type = 'info', string $link = ''): void
{
    db()->prepare('INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)')
       ->execute([$userId, $type, $title, $message, $link]);
}

function user_notifications(int $userId, int $limit = 30): array
{
    $st = db()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT ?');
    $st->bindValue(1, $userId, PDO::PARAM_INT);
    $st->bindValue(2, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

function unread_count(int $userId): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
}

function time_ago(?string $utc): string
{
    if (!$utc) return '';
    $t = strtotime($utc . ' UTC');
    $d = time() - $t;
    if ($d < 60) return 'только что';
    if ($d < 3600) return floor($d / 60) . ' мин назад';
    if ($d < 86400) return floor($d / 3600) . ' ч назад';
    if ($d < 604800) return floor($d / 86400) . ' дн назад';
    return date('d.m.Y', $t);
}

// регистрация сессии в «Активных сессиях» профиля
function touch_session(int $userId): void
{
    $sid = session_id();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 160);
    $st  = db()->prepare('SELECT id FROM sessions_log WHERE sid=?');
    $st->execute([$sid]);
    if ($row = $st->fetch()) {
        db()->prepare("UPDATE sessions_log SET last_seen=datetime('now'), ip=?, ua=? WHERE id=?")
           ->execute([$ip, $ua, $row['id']]);
    } else {
        db()->prepare('INSERT INTO sessions_log (user_id,sid,ip,ua) VALUES (?,?,?,?)')
           ->execute([$userId, $sid, $ip, $ua]);
    }
}

function current_user(): ?array
{
    if (!isset($_SESSION['uid'])) {
        return null;
    }
    static $u = false;
    if ($u === false) {
        $st = db()->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
    }
    return $u ?: null;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        header('Location: /login.php');
        exit;
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if (!$u['is_admin']) {
        http_response_code(403);
        exit('Доступ запрещён.');
    }
    return $u;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function check_csrf(): void
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($sent !== csrf_token()) {
        http_response_code(400);
        exit('Неверный CSRF-токен. Обновите страницу.');
    }
}

// ---- Тосты (всплывающие уведомления) --------------------------------------
function toast(string $msg, string $type = 'ok'): void
{
    static $types = ['ok' => 'success', 'info' => 'info', 'warn' => 'warning', 'err' => 'error'];
    $_SESSION['toasts'][] = ['msg' => $msg, 'type' => $types[$type] ?? 'info'];
}

function take_toasts(): array
{
    $list = $_SESSION['toasts'] ?? [];
    unset($_SESSION['toasts']);
    return $list;
}

function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function fmt_money(float $v): string
{
    return number_format($v, 2, '.', ' ') . ' ₽';
}

function fmt_ram(int $mb): string
{
    return $mb >= 1024 && $mb % 1024 === 0 ? ($mb / 1024) . ' ГБ' : $mb . ' МБ';
}

function status_label(string $s): string
{
    return ['active' => 'Активен', 'rebuild' => 'Переустановка', 'suspended' => 'Приостановлен'][ $s] ?? $s;
}

function plan_price(string $plan): float
{
    return ['Start' => 290, 'Standard' => 590, 'Pro' => 1290, 'Enterprise' => 2990][$plan] ?? 590;
}

// ---- Тарифы: единый источник правды для всей платформы ---------------------
function plans(): array
{
    return [
        'Start'      => ['cpu' => 1, 'ram' => 1024,  'disk' => 20,  'price' => 290,  'net' => '200 Мбит/с', 'desc' => 'Тесты, боты, пет-проекты'],
        'Standard'   => ['cpu' => 2, 'ram' => 2048,  'disk' => 40,  'price' => 590,  'net' => '500 Мбит/с', 'desc' => 'Сайты и небольшие приложения'],
        'Pro'        => ['cpu' => 4, 'ram' => 8192,  'disk' => 100, 'price' => 1290, 'net' => '1 Гбит/с',   'desc' => 'Нагрузка, базы данных, CI/CD'],
        'Enterprise' => ['cpu' => 8, 'ram' => 16384, 'disk' => 240, 'price' => 2990, 'net' => '1 Гбит/с',   'desc' => 'Игры, видео, высокие нагрузки'],
    ];
}

function os_images(): array
{
    return ['Ubuntu 22.04 LTS', 'Ubuntu 24.04 LTS', 'Debian 12', 'CentOS Stream 9', 'AlmaLinux 9', 'Windows Server 2022'];
}

function regions(): array
{
    return [
        'ru-msk-1' => 'Москва · ~2 мс',
        'ru-spb-1' => 'Санкт-Петербург · ~8 мс',
        'eu-fra-1' => 'Франкфурт · ~35 мс',
        'nl-ams-2' => 'Амстердам · ~42 мс',
        'tr-ist-1' => 'Стамбул · ~55 мс',
        'kz-ala-1' => 'Алматы · ~30 мс',
    ];
}

// SVG-иконки ОС вместо эмодзи (монохром, корпоративный стиль)
function os_icon(string $os): string
{
    $l = mb_strtolower($os);
    if (str_contains($l, 'windows')) {
        return '<svg class="osi" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5.6 10.4 4.6v7.1H3V5.6zM11.6 4.4 21 3v8.7h-9.4V4.4zM3 12.9h7.4V20L3 19v-6.1zm8.6 0H21V21l-9.4-1.4v-6.7z"/></svg>';
    }
    if (str_contains($l, 'centos') || str_contains($l, 'alma')) {
        return '<svg class="osi" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3.5"/></svg>';
    }
    if (str_contains($l, 'debian')) {
        return '<svg class="osi" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c4.9 0 8.5 4 7.6 8.6-.7 3.6-3.8 5.2-6.2 4.6-1.8-.5-2.4-2.1-1.6-3.3.7-1 .1-1.7-.9-1.4-2 .6-3.4 2.6-2.8 5 .3 1.2 0 2-.8 2.3C3.9 17 2.6 13.2 4 9.6 5.4 5.9 8.5 3 12 3z"/></svg>';
    }
    // ubuntu / linux по умолчанию
    return '<svg class="osi" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3"/><path d="M12 3v4M19.5 7.5l-3.4 2M19.5 16.5l-3.4-2M12 21v-4M4.5 16.5l3.4-2M4.5 7.5l3.4 2" stroke="currentColor" stroke-width="2"/></svg>';
}

// Логотип-знак: серверные стойки без эмодзи
function logo_mark_svg(): string
{
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 10h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2zm2-7a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm0 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/></svg>';
}
