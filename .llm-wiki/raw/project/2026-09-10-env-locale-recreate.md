# Русификация панели не применялась: env_file замораживается при создании контейнера

> Source: Диагностика кода и окружения (docker-compose.yml, backend/.env, docker inspect)
> Collected: 2026-09-10
> Published: 2026-09-10

## Симптом

В панели `/panel` кнопки и лейблы оставались английскими (Create, Edit, Delete), хотя в `backend/.env` стоит `APP_LOCALE=ru` / `APP_FALLBACK_LOCALE=ru`, а русские переводы в вендоре есть (`vendor/filament/*/resources/lang/ru` — actions, filament, forms, infolists, notifications, query-builder, schemas, support, tables, widgets).

## Причина

`backend/.env` подключён к сервисам через `env_file: ./backend/.env` в `docker-compose.yml` (backend-app, backend-queue, backend-scheduler, reverb). Значения `env_file` попадают в **переменные процесса** контейнера при его создании и остаются там замороженными до пересоздания. Laravel при бутстрапе читает переменные процесса и **не перезаписывает** их содержимым `.env`-файла — то есть процессное значение `APP_LOCALE` побеждает файл.

Правка `.env` без пересоздания контейнера на конфигурацию не влияет: `docker compose exec backend-app env | grep APP_LOCALE` показывал `en` при `APP_LOCALE=ru` в файле.

## Проверка и устранение

- Подтверждение гипотезы без пересоздания: `docker compose exec -e APP_LOCALE=ru backend-app php artisan about` → `Locale: ru` (в том же контейнере с прежним процессным env — `en`).
- Устранение: `docker compose up -d --force-recreate backend-app backend-queue backend-scheduler reverb` (сервисы с `env_file`). После — `artisan about` → `Locale: ru`, отрендеренная страница `/panel/login` отдаёт «Войти», «Пароль», «Запомнить меня»; английские вхождения `Password` в HTML — только внутренние атрибуты Alpine (`isPasswordRevealed`).
- Переводы резолвятся: `__('filament-actions::create.single.label')` → «Создать», `delete` → «Удалить».
- Тесты после смены локали: 439 passed (1307 assertions) — набор к локали не привязан.

Вывод в эксплуатацию — `documentations/operations.md`, раздел «Переменные окружения».
