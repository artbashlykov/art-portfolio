# ART Portfolio

Плагин WordPress для портфолио в виде сетки карточек с живым превью внутренних страниц сайта через iframe.

**Версия:** 1.3.0  
**Требования:** WordPress 6.0+, PHP 7.4+

**Официальный репозиторий:** [https://github.com/artbashlykov/art-portfolio](https://github.com/artbashlykov/art-portfolio)

**Материалы автора:** [https://forge.artbashlykov.ru](https://forge.artbashlykov.ru)

## Возможности

- Работы портфолио: превью-изображение, бейдж, описание, параметры проекта
- Подборки работ (как рубрики) и фильтры в галерее
- Привязка к странице, записи или другому публичному типу текущего сайта
- Gutenberg-блок **АРТ Портфолио: Галерея** и шорткод `[art_portfolio]`
- Пагинация галереи: по умолчанию 10 работ на страницу
- Справка по шорткоду `[art_portfolio]` в меню ART Portfolio → Шорткод
- Цвета карточки и текст кнопки настраиваются в блоке
- Live Preview: настоящий iframe, создаётся только после наведения или первого касания
- Режим превью `art_portfolio_preview=1` не меняет оригинальную страницу

## Установка из репозитория

1. Скопируйте папку `art-portfolio` в `wp-content/plugins/`.
2. Если нужен Gutenberg-блок, выполните `npm install` и `npm run build` (готовые файлы уже лежат в `build/`).
3. Активируйте плагин в админке WordPress.
4. Добавьте работы в **ART Portfolio** и вставьте блок или шорткод.

## Сборка блока

```bash
npm install
npm run build
```

Для разработки:

```bash
npm run start
```

## Обновления (GitHub Releases)

Плагин использует [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker). Для приватного репозитория в `wp-config.php` можно задать:

```php
define( 'ART_PORTFOLIO_GITHUB_TOKEN', 'your-github-token' );
```

Zip релиза должен называться `art-portfolio.zip` (без версии в имени файла).

## Лицензия

GPL v2 or later. См. [LICENSE](LICENSE).
