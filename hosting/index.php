<?php require_once __DIR__ . '/includes/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VDSmart — облачные серверы VDS/VPS | Официальный сайт</title>
<meta name="description" content="VDSmart — корпоративная платформа облачных серверов: NVMe, 9 регионов, защита от DDoS, SLA 99.95%, панель управления. Запуск сервера за 60 секунд.">
<link rel="stylesheet" href="<?= asset('assets/landing2.css') ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='2' fill='%230067b8'/><path d='M6 7h12v3H6zM6 14h12v3H6z' fill='white'/></svg>">
</head>
<body>

<header class="ld-header" id="lnHeader">
  <div class="wrap ld-nav">
    <a class="brand" href="/index.php"><span class="brand-mark"><?= logo_mark_svg() ?></span>VDSmart</a>
    <nav class="ld-links" id="lnNav">
      <a href="#products">Продукты</a>
      <a href="#security">Безопасность</a>
      <a href="#pricing">Тарифы</a>
      <a href="#regions">Регионы</a>
      <a href="#support">Поддержка</a>
      <a href="#faq">Вопросы</a>
    </nav>
    <div class="ld-cta">
      <?php if ($u = current_user()): ?>
        <a class="btn sm" href="/user/dashboard.php">Панель управления</a>
      <?php else: ?>
        <a class="linklogin" href="/login.php">Войти</a>
        <a class="btn sm" href="/register.php">Начать бесплатно</a>
      <?php endif; ?>
    </div>
    <button class="burger" id="burger" aria-label="Меню"><span></span><span></span><span></span></button>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="wrap hero-in">
    <div class="hero-txt">
      <h1>Облачные серверы<br>для серьёзных задач</h1>
      <p class="lead">VDSmart — платформа VDS/VPS для команд разработки, хостинг-провайдеров и бизнеса. NVMe-диски, защита от DDoS и панель управления, в которой сервер готов меньше чем за минуту.</p>
      <div class="hero-btns">
        <a class="btn lg" href="<?= $u ? '/user/server_new.php' : '/register.php' ?>">Создать аккаунт</a>
        <a class="btn outline lg" href="#pricing">Все тарифы</a>
      </div>
    </div>
    <div class="hero-art">
      <div class="hero-panel">
        <div class="hp-head"><span class="brand-mark"><?= logo_mark_svg() ?></span><b>VDSmart Cloud Platform</b></div>
        <div class="hp-grid">
          <div class="hp-cell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="7" rx="1"/><rect x="3" y="13" width="18" height="7" rx="1"/><circle cx="7" cy="7.5" r="1" fill="currentColor"/><circle cx="7" cy="16.5" r="1" fill="currentColor"/></svg><b>Запуск за 60 секунд</b><span>Автоматическая установка образа и выдача IP без модерации</span></div>
          <div class="hp-cell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg><b>Защита от DDoS</b><span>Фильтрация L3/L4 до 1 Тбит/с включена во все тарифы</span></div>
          <div class="hp-cell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg><b>9 дата-центров</b><span>Россия, Казахстан, Германия, Нидерланды, Турция</span></div>
          <div class="hp-cell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="1"/><path d="M3 10h18M7 15h4"/></svg><b>Почасовая оплата</b><span>Баланс, детализация до минуты, чеки для бухгалтерии</span></div>
        </div>
      </div>
    </div>
  </div>
  <div class="logos">
    <div class="wrap logos-in">
      <span>Нам доверяют:</span>
      <b>ИНТЕРСИСТЕМ</b><b>ЛАБОРАТОРИЯ КОДА</b><b>МЕДИАСЕРВ</b><b>ГЕЙТВЕЙ ОНЛАЙН</b><b>ФИНТЕХ ПРО</b><b>СТАРТРЕК</b>
    </div>
  </div>
</section>

<!-- ПРОДУКТЫ / ВОЗМОЖНОСТИ -->
<section id="products">
  <div class="wrap">
    <div class="sec-head reveal">
      <span class="sec-kicker">Продукты</span>
      <h2>Всё, что нужно для работы вашей инфраструктуры</h2>
      <p>Единая панель управления серверами, сетями, резервными копиями и биллингом. Никаких отдельных договоров на каждую услугу.</p>
    </div>
    <div class="cards-grid">
      <?php
      $ic = [
        'server' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="7" rx="1"/><rect x="3" y="13" width="18" height="7" rx="1"/><circle cx="7" cy="7.5" r="1" fill="currentColor"/><circle cx="7" cy="16.5" r="1" fill="currentColor"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>',
        'backup' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 12a8 8 0 018-8 8 8 0 017 4"/><path d="M20 12a8 8 0 01-8 8 8 8 0 01-7-4"/><path d="M18 4v4h-4M6 20v-4h4"/></svg>',
        'console'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="16" rx="1"/><path d="M6 9l3 3-3 3M12 15h5"/></svg>',
        'net'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg>',
        'bill'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="1"/><path d="M3 10h18M7 15h4"/></svg>',
      ];
      $feats = [
        ['server','VDS на NVMe','Виртуальные серверы на процессорах AMD EPYC и дисках NVMe последнего поколения. Локальная производительность до 350 000 IOPS. Запуск — 60 секунд, апгрейд ресурсов — без потери данных и IP-адреса.','#pricing'],
        ['shield','Защита от DDoS','Фильтрация атак до 1 Тбит/с на уровне сети: L3/L4 бесплатно во всех тарифах. Для L7-атак (HTTP-flood, боты) подключается модуль Web-защиты с настройкой правил прямо в панели.','#security'],
        ['backup','Автоматические бэкапы','Ежедневные снимки дисков с хранением 14 дней в резервном дата-центре. Расписание, ручные копии и восстановление диска — в пару кликов на странице сервера. Хранение копий бесплатно.','#pricing'],
        ['net','Частная сеть VPC','Изолированный L2-канал между вашими серверами с пропусканием до 10 Гбит/с. Трафик внутри VPC не учитывается в лимиты и не выходит в публичную сеть. Подключение — галочкой при создании сервера.','#regions'],
        ['bill','Прозрачный биллинг','Почасовой учёт с детализацией до минуты, история операций и чеки для бухгалтерии. Пополнение картой, СБП или по счёту для юридических лиц. Неиспользованный остаток возвращается на баланс при удалении сервера.','#pricing'],
        ['support2','Поддержка 24/7','Инженеры отвечают в среднем за 15 минут — ночью, в выходные и праздники. Тикеты, приватный чат, статус сервисов и база знаний с пошаговыми инструкциями по каждой теме.','#support'],
      ];
      $ic['support2'] = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 13a8 8 0 0116 0"/><rect x="3" y="13" width="4" height="7" rx="2"/><rect x="17" y="13" width="4" height="7" rx="2"/><path d="M21 19v1a3 3 0 01-3 3h-4"/></svg>';
      foreach ($feats as $i => [$k,$t,$d,$href]): ?>
      <a class="card reveal" href="<?= $href ?>">
        <span class="card-ic"><?= $ic[$k] ?></span>
        <h3><?= $t ?></h3>
        <p><?= $d ?></p>
        <span class="textlink">Подробнее</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- БЕЗОПАСНОСТЬ -->
<section class="security" id="security">
  <div class="wrap">
    <div class="sec-head reveal">
      <span class="sec-kicker">Безопасность и соответствие</span>
      <h2>Инфраструктура, которой доверяют данные</h2>
      <p>Мы строим безопасность по стандартам ISO/IEC 27001 и требованиям 152-ФЗ. Данные клиентов шифруются, а дата-центры работают под круглосуточной охраной.</p>
    </div>
    <div class="sec-grid">
      <div class="sec-list">
        <?php
        $checks = [
          ['Сертификация ISO/IEC 27001','Процессы управления информационной безопасностью сертифицированы независимым аудитором. Отчёт доступен по запросу.'],
          ['Соответствие 152-ФЗ и 242-ФЗ','Размещение данных в российских дата-центрах, уведомление в реестре операторов персональных данных.'],
          ['Шифрование трафика','TLS 1.3 по умолчанию в панели и API. Ключи SSH и пароли хранятся в bcrypt/PBKDF2, доступ к БД журналируется.'],
          ['Защита периметра','Собственная AS-система фильтрации, blackhole-рутинг volumetric-атак, rate-limit на уровне портов.'],
          ['Физическая охрана','Дата-центры уровня Tier III: контроль доступа по биометрии, видеонаблюдение 24/7, резервное питание N+1.'],
          ['Аудит и журналирование','Каждое действие в панели попадает в журнал безопасности. Экспорт событий — в разделе «Профиль».'],
        ];
        foreach ($checks as [$t,$d]): ?>
        <div class="sec-item reveal">
          <span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg></span>
          <div><b><?= $t ?></b><span><?= $d ?></span></div>
        </div>
        <?php endforeach; ?>
      </div>
      <aside class="cert-panel reveal">
        <h4>Стандарты и гарантии</h4>
        <div class="certs">
          <span class="cert">ISO/IEC 27001</span>
          <span class="cert">PCI DSS Level 2</span>
          <span class="cert">Tier III</span>
          <span class="cert">152-ФЗ</span>
          <span class="cert">SOC 2 Type II</span>
        </div>
        <div class="sla-box">
          <b>99.95% SLA</b>
          <span>Гарантия доступности, подтверждённая документально. При нарушении начисляем компенсацию на баланс автоматически — по каждому часу простоя.</span>
        </div>
      </aside>
    </div>
  </div>
</section>

<!-- ТАРИФЫ -->
<section id="pricing">
  <div class="wrap">
    <div class="sec-head reveal">
      <span class="sec-kicker">Тарифы</span>
      <h2>Выберите конфигурацию сервера</h2>
      <p>Цены указаны за месяц при помесячной оплате. Учёт — почасовой — платите только за время работы сервера.</p>
      <div class="billing-toggle">
        <span>Помесячно</span>
        <button class="switch" id="planSwitch" aria-label="Переключить период оплаты"></button>
        <span>Оплатить за год <span class="save-tag">−20%</span></span>
      </div>
    </div>
    <div class="plans">
      <?php foreach (plans() as $name => $p): ?>
      <div class="plan reveal <?= $name === 'Pro' ? 'featured' : '' ?>">
        <?php if ($name === 'Pro'): ?><span class="flag">Популярный выбор</span><?php endif; ?>
        <h3>VDSmart <?= $name ?></h3>
        <div class="spec"><?= $p['cpu'] ?> vCPU · <?= $p['ram'] >= 1024 ? ($p['ram']/1024).' ГБ' : $p['ram'].' МБ' ?> RAM · <?= $p['disk'] ?> ГБ NVMe</div>
        <div class="price"><span data-m="<?= (int)$p['price'] ?>"><?= number_format($p['price']) ?></span> <small>₽ / мес</small></div>
        <div class="per">или ≈ <?= number_format($p['price']/720, 2) ?> ₽ в час</div>
        <ul>
          <li>Защита от DDoS L3/L4 включена</li>
          <li>Скорость канала: <?= $p['net'] ?></li>
          <li>Ежедневные бэкапы, хранение 14 дней</li>
          <li>Управление снапшотами и восстановление из панели</li>
          <li><?= $name === 'Start' ? '1 IPv4-адрес' : ($name === 'Enterprise' ? 'Выделенный IP + VPC без лимитов' : 'IPv4 + частная сеть VPC') ?></li>
        </ul>
        <a class="btn <?= $name === 'Pro' ? '' : 'outline' ?>" href="<?= $u ? '/user/server_new.php?plan='.urlencode($name) : '/register.php' ?>">Выбрать <?= $name ?></a>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="reveal" style="margin-top:26px;color:var(--muted);font-size:.88rem">Требуется конфигурация под задачу? Соберите сервер вручную в панели: любое сочетание vCPU, памяти и диска. <a class="textlink" href="<?= $u ? '/user/server_new.php' : '/register.php' ?>">Конфигуратор</a></p>
  </div>
</section>

<!-- РЕГИОНЫ -->
<section class="regions" id="regions">
  <div class="wrap">
    <div class="sec-head reveal">
      <span class="sec-kicker">География</span>
      <h2>Девять дата-центров в четырёх странах</h2>
      <p>Размещайте сервер ближе к аудитории. Пинг до каждого региона измерен с точки Moscow-IX, обновляется каждые 5 минут.</p>
    </div>
    <div class="rgrid">
      <?php
      $regions = [
        ['Москва','msk-1',4,'Основная площадка Tier III, Москва, м. Тропарёво'],
        ['Санкт-Петербург','spb-1',9,'Ленинский пр., резервирование N+1'],
        ['Екатеринбург','ekb-1',18,'Урал-центр'],
        ['Казань','kzn-1',13,'IT-парк'],
        ['Новосибирск','nsk-1',29,'Сибирский технопарк'],
        ['Алматы','ala-1',32,'Новый регион 2026'],
        ['Франкфурт','fra-1',46,'DE-CIX, равный обмен'],
        ['Амстердам','ams-1',51,'AMS-IX'],
        ['Стамбул','ist-1',64,'Точка присутствия Турции'],
      ];
      foreach ($regions as [$city,$code,$ping,$note]): ?>
      <div class="region reveal">
        <div class="region-top"><b><?= $city ?></b><code><?= $code ?></code></div>
        <div class="ping <?= $ping > 55 ? 'warn' : '' ?>"><i></i> ~<?= $ping ?> мс до Москвы</div>
        <div style="font-size:.83rem;color:var(--muted)"><?= $note ?></div>
        <div class="tld-row"><span>NVMe</span><span>DDoS-защита</span><span>VPC</span></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ОТЗЫВЫ / ПОДДЕРЖКА -->
<section id="support">
  <div class="wrap">
    <div class="sec-head reveal">
      <span class="sec-kicker">Клиенты и поддержка</span>
      <h2>Команды, которые работают на VDSmart каждый день</h2>
    </div>
    <div class="quotes">
      <?php
      $revs = [
        ['АК','Алексей Кравцов','CTO, маркетплейс «Волна»','Переехали с трёх старых провайдеров на VDSmart: стало меньше сюрпризов. Консоль спасла нас дважды, когда после обновления ядра не поднимался SSH.'],
        ['МС','Мария Соколова','DevOps, финтех-стартап','Почасовой биллинг и API закупок позволили автоматизировать тестовые окружения. Инженеры сами поднимают сервер под задачу и удаляют его вечером.'],
        ['ДВ','Дмитрий Волков','Основатель веб-студии','Как реселлеру мне важны брендированная панель и стабильность. За 14 месяцев — ни одного длительной аварии, SLA-компенсации получали дважды, за плановые работы.'],
      ];
      foreach ($revs as [$ini,$who,$role,$txt]): ?>
      <div class="quote reveal">
        <div class="stars">★★★★★</div>
        <p>«<?= $txt ?>»</p>
        <footer><span class="avatar"><?= $ini ?></span><div><b><?= $who ?></b><span><?= $role ?></span></div></footer>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="reveal" style="margin-top:34px;display:flex;gap:14px;flex-wrap:wrap;align-items:center;color:var(--muted);font-size:.9rem">
      Средний первый ответ в чате — <b style="color:var(--ink)">15 минут</b>. Инженеры поддержки работают 24/7, включая праздники.
      <a class="btn outline sm" href="/register.php">Задать вопрос</a>
    </div>
  </div>
</section>

<!-- FAQ -->
<section id="faq" style="background:var(--gray)">
  <div class="wrap">
    <div class="sec-head reveal">
      <span class="sec-kicker">Часто задаваемые вопросы</span>
      <h2>Ответы на главные вопросы</h2>
    </div>
    <div class="faq">
      <?php
      $faq = [
        ['Как быстро сервер будет готов?','Обычно 40–60 секунд после оплаты: образ развёртывается автоматически, IP выдаётся сразу, доступ в веб-консоль появляется до окончания установки.'],
        ['Какие операционные системы доступны?','Ubuntu, Debian, CentOS Stream, AlmaLinux, Rocky Linux, Windows Server. Также есть готовые образы: Docker, Nextcloud, игровые серверы.'],
        ['Что входит в защиту от DDoS?','Фильтрация L3/L4-атак объёмом до 1 Тбит/с включена бесплатно во все тарифы. Для L7 (HTTP-flood, боты) подключается модуль Web-защиты.'],
        ['Можно ли менять тариф без потери данных?','Да. Апгрейд занимает несколько минут и выполняется на лету; даунгрейд — после перезагрузки. Данные и IP сохраняются.'],
        ['Как работает оплата?','Пополняете баланс (карта, СБП, крипта, счёт для юрлиц), сервер списывает стоимость посекундно. Неиспользованный остаток возвращается на баланс при удалении.'],
        ['Есть ли договор и закрывающие документы?','Для юридических лиц — договор, счета и УПД через ЭДО. Самозанятые и физлица получают электронные чеки.'],
      ];
      foreach ($faq as [$q,$a]): ?>
      <div class="qa reveal"><button type="button"><?= $q ?></button><div class="ans"><?= $a ?></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ФИНАЛЬНЫЙ CTA -->
<section class="cta-final">
  <div class="wrap">
    <h2>Первый сервер — бесплатно на 3 дня</h2>
    <p>Зарегистрируйтесь, получите 500 ₽ на балансе и запустите любой тариф Start. Карта не требуется.</p>
    <div class="cta-btns">
      <a class="btn white lg" href="/register.php">Создать аккаунт</a>
      <a class="btn dark lg" href="#pricing">Сравнить тарифы</a>
    </div>
  </div>
</section>

<footer class="site-footer">
  <div class="wrap">
    <div class="ft-top">
      <div class="ft-col"><h4>Компания</h4><a href="#products">О VDSmart</a><a href="#regions">Дата-центры</a><a href="#support">Клиенты</a><a href="/register.php">Партнёрам</a><a href="/register.php">Реселлерам</a></div>
      <div class="ft-col"><h4>Продукты</h4><a href="#pricing">VDS/VPS</a><a href="#products">Выделенные серверы</a><a href="#products">Объектное хранилище</a><a href="#products">Частная сеть VPC</a><a href="#security">DDoS-защита</a></div>
      <div class="ft-col"><h4>Ресурсы</h4><a href="#faq">База знаний</a><a href="#faq">API и документация</a><a href="#security">Статус сервисов</a><a href="#support">Блог</a><a href="#pricing">Калькулятор цен</a></div>
      <div class="ft-col"><h4>Поддержка</h4><a href="/register.php">Связаться с нами</a><a href="/user/tickets.php">Тикет-система</a><a href="#support">Чат 24/7</a><a href="#security">SLA и компенсации</a></div>
      <div class="ft-col"><h4>Правовая информация</h4><a href="/terms.php">Пользовательское соглашение</a><a href="/privacy.php">Политика конфиденциальности</a><a href="/privacy.php">Обработка данных (152-ФЗ)</a><a href="/terms.php">Реквизиты</a></div>
    </div>
    <div class="ft-bot">
      <span>© <?= date('Y') ?> ООО «ВДСмарт», ИНН 7700000000 · ОГРН 1137700000000</span>
      <a href="mailto:support@vdsmart.ru">support@vdsmart.ru</a>
      <a href="tel:+74950000000">+7 (495) 000-00-00</a>
      <span class="lang-pick">Русский (Россия)</span>
    </div>
  </div>
</footer>

<script src="<?= asset('assets/landing2.js') ?>"></script>
</body>
</html>
