# NEBULA — Super Site (Motion & 3D Studio)

Сайт-портфолио студии моушн-дизайна и 3D с «вау»-эффектами.

## Плагины/библиотеки (установлены, лежат локально в `vendor/`)
| Плагин | Зачем |
|---|---|
| **Three.js** (`scene.js`) | Фоновая 3D-сцена: стеклянный кристалл, орбитальные спутники, 1800 частиц-звёзд, свет и туман, реакция на мышь и скролл |
| **GSAP + ScrollTrigger** (`app.js`) | Прелоадер, интро текста по маске, побуквенные заголовки, параллакс карточек работ, счётчики, прогресс-бар |
| **Lenis** | Плавный инерционный скролл + плавные якоря меню |
| Дополнительно | Кастомный курсор, магнитные кнопки, 3D-tilt карточек со «бликом», бургер-меню, адаптив, prefers-reduced-motion |

## Запуск
```bash
cd site
python3 -m http.server 8090   # открыть http://localhost:8090
```
Важно: открывать через HTTP-сервер (ES-модули Three.js не работают с file://).

## Обновление версий плагинов
```bash
npm install three gsap lenis   # затем скопировать билды в vendor/
```

## Структура
- `index.html` — разметка (hero, works, services, pipeline, pricing, contact)
- `style.css` — дизайн-система на CSS-переменных
- `scene.js` — WebGL-сцена (module)
- `app.js` — вся интерактив-логика
- `vendor/` — three.module.js, three.core.js, gsap.min.js, ScrollTrigger.min.js, lenis.min.js
