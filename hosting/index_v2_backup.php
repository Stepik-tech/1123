<?php require_once __DIR__ . '/includes/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'VDSmart') ?> — облачные серверы за 60 секунд</title>
<meta name="description" content="VDSmart — платформа управления VPS/VDS: SSD NVMe, 6 регионов, бэкапы, файрвол и веб-консоль. Первый сервер готов за минуту.">
<link rel="stylesheet" href="<?= asset('assets/landing.css') ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='5' fill='%230f6cbd'/><path d='M6 7h12v3H6zM6 14h12v3H6z' fill='white'/></svg>">
<script>window.VDS_LOGO = <?= json_encode(logo_mark_svg()) ?>;</script>
</head>
<body class="ld-body">

<div class="scroll-progress" id="scrollProgress"></div>

<header class="ld-header" id="lnHeader">
  <div class="wrap ld-nav">
    <a class="brand" href="/index.php"><span class="brand-mark"><?= logo_mark_svg() ?></span>VDSmart</a>
    <nav class="ld-links" id="lnNav">
      <a href="#advantages">Преимущества</a>
      <a href="#pricing">Тарифы</a>
      <a href="#regions">География</a>
      <a href="#reviews">Отзывы</a>
      <a href="#faq">Вопросы</a>
    </nav>
    <div class="ld-cta">
      <?php if ($u = current_user()): ?>
        <a class="btn ghost sm" href="/user/dashboard.php">Панель управления</a>
      <?php else: ?>
        <a class="linklogin" href="/login.php">Войти</a>
        <a class="btn primary sm" href="/register.php">Начать бесплатно</a>
      <?php endif; ?>
    </div>
    <button class="burger" id="burger" aria-label="Меню"><span></span><span></span><span></span></button>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="wrap hero-in">
    <div class="hero-txt">
      <span class="pill-top">✦ Новый регион: Алматы уже доступен</span>
      <h1>Серверы, которые<br><em>не подводят</em></h1>
      <p class="lead">VDSmart — облачная платформа для команд и разработчиков: запуск VPS за&nbsp;60&nbsp;секунд, NVMe-диски, ежедневные бэкапы и прозрачный биллинг. Без звонков менеджера и ручной модерации.</p>
      <div class="hero-btns">
        <a class="btn primary lg" href="<?= $u ? '/user/server_new.php' : '/register.php' ?>">Создать сервер бесплатно</a>
        <a class="btn glass lg" href="#pricing">Посмотреть тарифы</a>
      </div>
      <div class="hero-trust">
        <span><b>99.95%</b> SLA по годам</span>
        <span><b>15 мин</b> средний ответ поддержки</span>
        <span><b>0 ₽</b> за регистрацию</span>
      </div>
    </div>
    <div class="hero-art">
      <div class="win">
        <div class="win-bar"><i></i><i></i><i></i><span>panel.vdsmart.ru — web-prod-01</span></div>
        <div class="win-body">
          <div class="wrow"><span>CPU · 4 vCPU</span><b id="hpCpu">34%</b><div class="wbar"><i id="hpCpuBar" style="width:34%"></i></div></div>
          <div class="wrow"><span>RAM · 8 ГБ</span><b id="hpRam">61%</b><div class="wbar"><i id="hpRamBar" style="width:61%"></i></div></div>
          <div class="wrow"><span>NVMe · 100 ГБ</span><b>42%</b><div class="wbar"><i style="width:42%"></i></div></div>
          <div class="wrow"><span>Сеть out</span><b>86 Мбит/с</b><div class="wbar"><i style="width:27%"></i></div></div>
          <div class="wterm">$ ssh root@185.211.84.12<br><span class="ok">Welcome to Ubuntu 24.04 LTS</span><br>root@web-prod-01:~# <i class="cursor"></i></div>
        </div>
      </div>
      <div class="float-card f1"><b>Бэкап завершён</b><small>web-prod-01 · 03:00 MSK</small></div>
      <div class="float-card f2"><b>+ Новый сервер</b><small>run-prod-02 · ru-spb-1 · 41 с</small></div>
    </div>
  </div>
  <div class="logos">
    <div class="wrap logos-in">
      <span>нам доверяют команды из:</span>
      <b>ЯКЕБО</b><b>ПИКСИТ</b><b>LARAVEL.RU</b><b>ОЗОН-ЛАБ</b><b>ТИЛЬДИДА</b><b>ХАБЛФРИ</b><b>ДЖИТИМС</b>
    </div>
  </div>
</section>

<!-- ADVANTAGES -->
<section class="sec" id="advantages">
  <div class="wrap">
    <div class="sec-head"><span class="kicker">Почему VDSmart</span>
      <h2>Инфраструктура без сюрпризов</h2>
      <p>Мы собрали всё, что нужно для продакшена, в одном интерфейсе — и показали цену до того, как вы нажмёте «оплатить».</p></div>
    <div class="adv-grid">
      <article class="adv reveal big">
        <div class="adv-num">01</div>
        <h3>Запуск за 60 секунд</h3>
        <p>Тариф, образ ОС, регион — и сервер готов, IP назначен. Никаких «заявок до 3 рабочих дней» и ожидания сборки руками инженера.</p>
        <div class="adv-art art-a"><div class="tline"></div><div class="tline short"></div><div class="tcircle"></div></div>
      </article>
      <article class="adv reveal">
        <div class="adv-num">02</div>
        <h3>NVMe и RAID-10</h3>
        <p>Диски enterprise-класса с резервированием. IOPS в 40 раз выше обычных SATA-SSD — базы и сборщики не простаивают.</p>
      </article>
      <article class="adv reveal">
        <div class="adv-num">03</div>
        <h3>Автобэкапы с восстановлением в клик</h3>
        <p>Ежедневные ночные снимки, глубина 14 дней. Откатываете диск к нужному состоянию сами, без тикета и ожидания.</p>
      </article>
      <article class="adv reveal">
        <div class="adv-num">04</div>
        <h3>Файрвол, SSH-ключи, консоль</h3>
        <p>Порты и ключи настраиваются в панели, out-of-band консоль спасает, если сеть легла. Антиработная защита включена по умолчанию.</p>
      </article>
      <article class="adv reveal">
        <div class="adv-num">05</div>
        <h3>Прозрачный биллинг</h3>
        <p>Карта, СБП и крипта. История операций, аренда по датам, остаток возвращается на баланс при удалении сервера.</p>
      </article>
      <article class="adv reveal">
        <div class="adv-num">06</div>
        <h3>Инженеры 24/7, а не боты</h3>
        <p>Поддержка отвечает в среднем за 15 минут и помогает с миграцией, сетями и поиском причины нагрузки — бесплатно.</p>
      </article>
    </div>
  </div>
</section>

<!-- HOW -->
<section class="sec dark" id="how">
  <div class="wrap">
    <div class="sec-head"><span class="kicker light">Как это работает</span>
      <h2>Три шага до рабочего сервера</h2></div>
    <div class="steps">
      <div class="stp reveal"><b>1</b><h3>Регистрация</h3><p>E-mail и пароль за 40 секунд. Карта для тестового периода не нужна.</p></div>
      <i class="arr"></i>
      <div class="stp reveal"><b>2</b><h3>Конфигуратор</h3><p>Тариф, образ и регион с итоговой ценой ещё до подтверждения заказа.</p></div>
      <i class="arr"></i>
      <div class="stp reveal"><b>3</b><h3>В работе</h3><p>IP и доступы — в панели. Мониторинг, бэкапы  и scale-up ресурсов — там же.</p></div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section class="sec" id="pricing">
  <div class="wrap">
    <div class="sec-head"><span class="kicker">Тарифы</span>
      <h2>Честная цена. Навсегда.</h2>
      <p>Цена фиксируется при заказе и не меняется при продлении. Все тарифы включают NVMe, IPv6 и ежедневные бэкапы.</p>
      <div class="toggle" id="billToggle">
        <button class="on" data-m="m">Помесячно</button>
        <button data-m="y">На год <span class="save">−15%</span></button>
      </div>
    </div>
    <div class="plans-grid">
      <?php
      $PL = plans();
      $extra = ['Start'=>['1 IPv4 + IPv6'],'Standard'=>['1 IPv4 + IPv6','Автобэкапы ежедневно'],'Pro'=>['2 IPv4 + IPv6','Автобэкапы + анти-DDoS'],'Enterprise'=>['3 IPv4 + IPv6','Выделенный инженер 24/7']];
      foreach ($PL as $name => $p): $hot = $name === 'Standard'; ?>
      <article class="pcard reveal<?= $hot?' hot':'' ?>">
        <?php if ($hot): ?><span class="ptag">Выбирают чаще всего</span><?php endif; ?>
        <h3><?= $name ?></h3>
        <p class="pdesc"><?= $p['desc'] ?></p>
        <div class="pprice"><span class="pm" data-m="m"><?= number_format($p['price'], 0, '', ' ') ?> ₽</span><span class="py" data-m="y" hidden><?= number_format(round($p['price']*12*0.85), 0, '', ' ') ?> ₽</span><small class="unit"> /мес</small></div>
        <ul>
          <li><?= $p['cpu'] ?> vCPU</li>
          <li><?= fmt_ram($p['ram']) ?> RAM</li>
          <li><?= $p['disk'] ?> ГБ NVMe</li>
          <li>Канал <?= $p['net'] ?></li>
          <?php foreach ($extra[$name] as $x): ?><li><?= $x ?></li><?php endforeach; ?>
        </ul>
        <a class="btn <?= $hot?'primary':'outline' ?>" href="<?= $u ? '/user/server_new.php?plan='.$name : '/register.php' ?>">Выбрать <?= $name ?></a>
      </article>
      <?php endforeach; ?>
    </div>
    <p class="fineprint">НДС включён. Неиспользованный остаток возвращается на баланс. Тестовый период — 7 дней для новых аккаунтов.</p>
  </div>
</section>

<!-- REGIONS -->
<section class="sec gray" id="regions">
  <div class="wrap reg-in">
    <div class="reg-txt">
      <span class="kicker">География</span>
      <h2>Шесть регионов, близкий — ваш</h2>
      <p>Дата-центры Tier III с независимыми вводами электропитания и собственными AS. Переключение между регионами — прямо в панели, данные не покидают вашу юрисдикцию, если вы этого не хотите.</p>
      <a class="btn outline" href="<?= $u ? '/user/server_new.php' : '/register.php' ?>">Выбрать регион при создании</a>
    </div>
    <div class="reg-list">
      <?php foreach (regions() as $code => $desc): [$city] = explode(' ·', $desc); ?>
      <div class="rg-row reveal"><span class="rg-dot"></span><b><?= trim($city) ?></b><code><?= $code ?></code><em><?= str_replace('· ','',str_replace(trim($city).' ·','',$desc)) ?></em></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- REVIEWS -->
<section class="sec" id="reviews">
  <div class="wrap">
    <div class="sec-head"><span class="kicker">Отзывы</span><h2>Команды, которые переехали к нам</h2></div>
    <div class="rev-grid">
      <figure class="rev reveal"><blockquote>«Перенесли 14 серверов за выходные — помогли с миграцией бесплатно. Сборки CI ускорились втрое на NVMe.»</blockquote><figcaption><b>Артём К.</b><span>CTO, маркетплейс товаров для дома</span></figcaption></figure>
      <figure class="rev reveal"><blockquote>«Главное — предсказуемость. Цена не уплывает, бэкапы работают, поддержка отвечает быстрее, чем мы пишем тикеты.»</blockquote><figcaption><b>Мария В.</b><span>DevOps, финтех-стартап</span></figcaption></figure>
      <figure class="rev reveal"><blockquote>«Веб-консоль спасла нас, когда мы зарезали себе iptables. Открываем OOB-терминал и чиним за минуту.»</blockquote><figcaption><b>Сергей Д.</b><span>Соло-разработчик, SaaS-сервис</span></figcaption></figure>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="sec gray" id="faq">
  <div class="wrap narrow">
    <div class="sec-head"><span class="kicker">FAQ</span><h2>Частые вопросы</h2></div>
    <details class="fq"><summary>Когда придёт IP-адрес после создания сервера?</summary><p>Через 60–90 секунд адрес появится в карточке сервера, root-пароль — в панели и на e-mail. Ручная модерация не требуется.</p></details>
    <details class="fq"><summary>Можно ли перенести существующий сервер?</summary><p>Да. Поддержите тикет — инженер бесплатно поможет с миграцией данных и проверит связность после переезда.</p></details>
    <details class="fq"><summary>Что происходит при исчерпании баланса?</summary><p>Серверы приостанавливаются через 3 дня с уведомлениями на e-mail. Данные хранятся 30 дней, возобновление — сразу после пополнения.</p></details>
    <details class="fq"><summary>Есть ли IPv6 и reverse-DNS?</summary><p>IPv6 бесплатный на всех тарифах. PTR-запись настраивается самостоятельно в разделе «Сеть» или через поддержку.</p></details>
    <details class="fq"><summary>Как отменить услугу?</summary><p>Удалите сервер в «Опасной зоне» карточки — неиспользованный остаток вернётся на баланс и его можно вывести.</p></details>
  </div>
</section>

<!-- CTA -->
<section class="cta">
  <div class="wrap cta-in">
    <h2>Первый сервер — бесплатно 7 дней</h2>
    <p>Создайте аккаунт сейчас: карта не нужна, отмена в один клик.</p>
    <a class="btn white lg" href="<?= $u ? '/user/server_new.php' : '/register.php' ?>">Создать аккаунт</a>
    <span class="ctasub">уже зарегистрировано 12 400+ серверов</span>
  </div>
</section>

<footer class="ld-foot">
  <div class="wrap foot-grid">
    <div class="fcol brand-col">
      <a class="brand" href="/index.php"><span class="brand-mark"><?= logo_mark_svg() ?></span>VDSmart</a>
      <p>Облачная платформа для управления серверами.<br>Работаем с 2019 года.</p>
      <div class="badges"><span>Tier III</span><span>ISO 27001</span><span>99.95% SLA</span></div>
    </div>
    <div class="fcol"><h4>Продукт</h4><a href="#advantages">Возможности</a><a href="#pricing">Тарифы</a><a href="#regions">Регионы</a><a href="#how">Как начать</a></div>
    <div class="fcol"><h4>Аккаунт</h4><a href="/login.php">Вход в панель</a><a href="/register.php">Регистрация</a><a href="/forgot.php">Забыли пароль</a><a href="/user/tickets.php">Поддержка</a></div>
    <div class="fcol"><h4>Контакты</h4><a href="mailto:support@vdsmart.example">support@vdsmart.example</a><a href="tel:+78005553535">8 800 555-35-35</a><span>Поддержка 24/7</span></div>
  </div>
  <div class="wrap foot-bot">© <?= date('Y') ?> VDSmart. Демонстрационный проект платформы.</div>
</footer>

<script src="<?= asset('assets/landing.js') ?>"></script>
</body>
</html>
