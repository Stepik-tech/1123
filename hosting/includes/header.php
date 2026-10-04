<?php require_once __DIR__ . '/../includes/config.php'; $u = current_user(); $unreadN = $u ? unread_count((int)$u['id']) : 0; if ($u) touch_session((int)$u['id']); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'Панель') ?> — <?= APP_NAME ?></title>
<meta name="description" content="Личный кабинет VDSmart: управление облачными серверами, биллинг, настройки.">
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<meta name="csrf" content="<?= csrf_token() ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='5' fill='%230f6cbd'/><path d='M6 7h12v3H6zM6 14h12v3H6z' fill='white'/></svg>">
</head>
<body class="dash-body">

<button class="sidebar-toggle" id="sbToggle" aria-label="Меню">
  <svg viewBox="0 0 24 24"><path d="M3 6h18v2H3zM3 11h18v2H3zM3 16h18v2H3z"/></svg>
</button>
<div class="sidebar-backdrop" id="sbBackdrop"></div>

<aside class="sidebar" id="sidebar">
    <a class="logo" href="/user/dashboard.php"><span class="logo-mark"><?= logo_mark_svg() ?></span><span>VDSmart</span></a>

    <nav class="nav">
        <div class="nav-cap">Основное</div>
        <a class="nav-item <?= in_array($active ?? '', ['dash','dashboard'], true) ? 'on' : '' ?>" href="/user/dashboard.php">
            <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V13h-8v8zm0-18v6h8V3h-8z"/></svg>
            Обзор
        </a>
        <a class="nav-item <?= ($active ?? '') === 'servers' ? 'on' : '' ?>" href="/user/servers.php">
            <svg viewBox="0 0 24 24"><path d="M4 4h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 10h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2zm2-7a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm0 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/></svg>
            Мои серверы
        </a>
        <a class="nav-item <?= ($active ?? '') === 'new' ? 'on' : '' ?>" href="/user/server_new.php">
            <svg viewBox="0 0 24 24"><path d="M11 13H5v-2h6V5h2v6h6v2h-6v6h-2v-6z"/></svg>
            Новый сервер
        </a>

        <div class="nav-cap">Финансы</div>
        <a class="nav-item <?= ($active ?? '') === 'billing' ? 'on' : '' ?>" href="/user/billing.php">
            <svg viewBox="0 0 24 24"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4H4V6h16v2zm0 10H4v-6h16v6z"/></svg>
            Баланс и оплата
        </a>

        <div class="nav-cap">Поддержка</div>
        <a class="nav-item <?= ($active ?? '') === 'tickets' ? 'on' : '' ?>" href="/user/tickets.php">
            <svg viewBox="0 0 24 24"><path d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM7 9h10v2H7V9zm6 5H7v-2h6v2zm4-6h-2V6h2v2z"/></svg>
            Поддержка
        </a>

        <div class="nav-cap">Аккаунт</div>
        <a class="nav-item <?= ($active ?? '') === 'notifications' ? 'on' : '' ?>" href="/user/notifications.php">
            <svg viewBox="0 0 24 24"><path d="M12 22a2.2 2.2 0 0 0 2.2-2.2H9.8A2.2 2.2 0 0 0 12 22zm7-5.5v-1l-1.6-1.6V9.5c0-3.1-1.6-5.6-4.4-6.3V2.5a1.5 1.5 0 0 0-3 0v.7C7.2 3.9 5.6 6.4 5.6 9.5v4.4L4 15.5v1h15z"/></svg>
            Уведомления
            <?php if ($unreadN > 0): ?><span class="nav-badge"><?= $unreadN > 9 ? '9+' : $unreadN ?></span><?php endif; ?>
        </a>
        <a class="nav-item <?= ($active ?? '') === 'profile' ? 'on' : '' ?>" href="/user/profile.php">
            <svg viewBox="0 0 24 24"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5z"/></svg>
            Профиль
        </a>
        <?php if ($u && $u['is_admin']): ?>
        <div class="nav-cap">Администрирование</div>
        <a class="nav-item <?= ($active ?? '') === 'admin' ? 'on' : '' ?>" href="/admin/index.php">
            <svg viewBox="0 0 24 24"><path d="M12 1 3 5v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V5l-9-4zm1 15h-2v-2h2v2zm0-4h-2V7h2v5z"/></svg>
            Панель админа
        </a>
        <?php endif; ?>
    </nav>

    <div class="side-user">
        <div class="avatar"><?= e(mb_strtoupper(mb_substr($u['name'] ?? '?', 0, 1))) ?></div>
        <div class="side-user-info">
            <b><?= e($u['name'] ?? '') ?></b>
            <span><?= fmt_money((float)($u['balance'] ?? 0)) ?></span>
        </div>
        <a class="icon-btn" href="/logout.php" title="Выйти">
            <svg viewBox="0 0 24 24"><path d="M17 7l-1.4 1.4L18.2 11H8v2h10.2l-2.6 2.6L17 17l5-5-5-5zM4 5h8v2H6v10h6v2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/></svg>
        </a>
    </div>
</aside>

<main class="main">
<header class="topbar">
    <div class="crumbs"><?= e($title ?? '') ?></div>
    <div class="top-actions">
        <button class="icon-btn bell" id="bellBtn" aria-label="Уведомления" aria-expanded="false">
            <svg viewBox="0 0 24 24"><path d="M12 22a2.2 2.2 0 0 0 2.2-2.2H9.8A2.2 2.2 0 0 0 12 22zm7-5.5v-1l-1.6-1.6V9.5c0-3.1-1.6-5.6-4.4-6.3V2.5a1.5 1.5 0 0 0-3 0v.7C7.2 3.9 5.6 6.4 5.6 9.5v4.4L4 15.5v1h15z"/></svg>
            <?php if ($unreadN > 0): ?><span class="bell-dot" id="bellDot"><?= $unreadN > 9 ? '9+' : $unreadN ?></span><?php endif; ?>
        </button>
        <div class="notif-pop card" id="notifPop" hidden>
            <div class="np-head"><b>Уведомления</b><button class="np-mark" id="markAllBtn" type="button">Прочитать все</button></div>
            <div class="np-list" id="npList">
                <?php $lastNotif = null; foreach (user_notifications((int)$u['id'], 6) as $n): $lastNotif = $n['id']; ?>
                <a class="np-item <?= $n['is_read'] ? '' : 'new' ?>" href="<?= $n['link'] ? e($n['link']) : '/user/notifications.php#n' . (int)$n['id'] ?>" data-id="<?= (int)$n['id'] ?>">
                    <span class="np-ic t-<?= e($n['type']) ?>"><svg viewBox="0 0 24 24"><?php
                        echo match($n['type']) {
                            'ok'   => '<path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/>',
                            'warn' => '<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>',
                            'err'  => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>',
                            default=> '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>'
                        }; ?></svg></span>
                    <span class="np-txt"><b><?= e($n['title']) ?></b><small><?= e(mb_strimwidth($n['message'], 0, 70, '…')) ?></small>
                        <em><?= time_ago($n['created_at']) ?></em></span>
                </a>
                <?php endforeach; ?>
                <?php if (!$lastNotif): ?><div class="np-empty">Пока пусто — здесь появятся события по вашим серверам.</div><?php endif; ?>
            </div>
            <a class="np-foot" href="/user/notifications.php">Все уведомления</a>
        </div>
        <span class="bal-chip" title="Текущий баланс"><b><?= fmt_money((float)($u['balance'] ?? 0)) ?></b></span>
        <a class="btn btn-primary btn-sm" href="/user/billing.php">Пополнить</a>
    </div>
</header>
<input type="hidden" id="notifMaxRead" value="<?= (int)($lastNotif ?? 0) ?>">

<?php if ($f = get_flash()): ?>
    <div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?>
        <button class="alert-x" type="button" aria-label="Закрыть">&times;</button>
    </div>
<?php endif; ?>
<script>window.VDS_TOASTS = <?= json_encode(take_toasts(), JSON_UNESCAPED_UNICODE) ?>;</script>
