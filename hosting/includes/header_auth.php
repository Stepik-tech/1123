<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'Авторизация') ?> — <?= APP_NAME ?></title>
<meta name="description" content="Панель управления облачными серверами VDSmart: создание, настройка и мониторинг VPS/VDS за 60 секунд.">
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='5' fill='%230f6cbd'/><path d='M6 7h12v3H6zM6 14h12v3H6z' fill='white'/></svg>">
</head>
<body class="auth-body">

<div class="auth-split">
  <aside class="auth-side">
    <a class="brand brand-light" href="/index.php"><?= logo_mark_svg() ?><span>VDSmart</span></a>
    <div class="auth-side-mid">
      <h2>Инфраструктура, которая не мешает работать</h2>
      <ul class="auth-perks">
        <li>Запуск сервера за 60 секунд в одном из 6 регионов</li>
        <li>SSD NVMe, резервирование RAID-10, ежедневные бэкапы</li>
        <li>Файрвол, SSH-ключи и веб-консоль прямо в панели</li>
        <li>Поддержка отвечает за 15 минут — круглосуточно</li>
      </ul>
    </div>
    <div class="auth-side-bot">
      <div class="trust-row"><span>99.95% SLA</span><span>Без скрытых платежей</span><span>Отмена в 1 клик</span></div>
    </div>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      <a class="brand brand-mobile" href="/index.php"><?= logo_mark_svg() ?><span>VDSmart</span></a>
