# Эксплуатация: команды, окружение, очереди, runbooks

> Sources: Проект (operations.md), 2026-08-19; Код (app/Jobs), 2026-08-19; диагностика env_file и локали, 2026-09-10
> Raw: [operations.md](../../raw/project/operations.md); [2026-09-10-env-locale-recreate.md](../../raw/project/2026-09-10-env-locale-recreate.md)

## Overview

Всё через Docker (`make ...`); artisan — только через контейнер (`docker compose exec backend-app php artisan ...`). Планировщик (`routes/console.php`): `promotions:sync` каждые 5 минут, `slots:generate` каждые 15 минут (сетка слотов шиномонтажа, withoutOverlapping); импорты запускаются через админку и диспатчатся в очередь (database). Реальный состав Job'ов сверен с кодом.

## Команды (Makefile)

`make up` / `down` / `build` — стек; `make bash` — bash в контейнер; `make fresh` — `migrate:fresh --seed` (эталонные данные: склады, расписания, регионы/города); `make lint` / `lint-fix` — pint; `make phpstan` — анализ level 6; `make test`; `make docs` — генерация API-документации.

## Окружение (значимое)

| Переменная | Назначение | По умолчанию |
|---|---|---|
| `CACHE_TTL_REFERENCES` | TTL кеша справочников | 3600 |
| `QUEUE_CONNECTION` | Очередь (импорты — Jobs) | database |
| `BROADCAST_CONNECTION` | Шина событий (уведомления админки) | reverb |
| `REVERB_*` | Reverb-сервер (websocket) | — |
| `TIRE_IMPORT_CHUNK_SIZE` | Строк XLSX на ChunkJob | 500 |
| `TIRE_IMPORT_DISK` / `POINT_IMPORT_DISK` | Диски JSON-чанков импорта | local |
| `SMS_STUB` / `SMS_STUB_CODE` | Заглушка SMS: фикс. код вместо провайдера | true / 1234 |
| `SMS_PROVIDER` | Провайдер SMS (v1 не выбран, заглушка LogSmsSender) | — |
| `SMS_RESEND_COOLDOWN_SECONDS` | Кулдаун повторной отправки кода | 60 |

Секреты в репозиторий не коммитятся — только имена.

**Правка `.env` требует пересоздания контейнеров.** `backend/.env` подключён через `env_file` (backend-app, backend-queue, backend-scheduler, reverb): значения попадают в переменные процесса при создании контейнера и там замораживаются, а Laravel переменные процесса не перезаписывает из файла. Поэтому правка без `docker compose up -d` (или `--force-recreate` для этих сервисов) не действует — симптом: `docker compose exec backend-app env | grep APP_...` показывает старое значение. Локаль интерфейса — `APP_LOCALE=ru`; переводы Filament берутся из `vendor/filament/*/resources/lang/ru`, своего каталога `lang/` в проекте нет.

## Очереди и импорты (фактические классы)

| Job | Что делает |
|---|---|
| `CatalogImport\ImportMasterJob` | Разбор XLSX → чанки JSON → Batch ChunkJob'ов |
| `CatalogImport\ChunkJob` | Импорт чанка: товары, остатки, цены |
| `CatalogImport\ModelImportJob` | Импорт моделей товаров |
| `GeoImport\PointImportJob` | Импорт точек выдачи → пересчёт `catalog_prices` |
| `VehicleImport\VehicleImportMasterJob` | Импорт автомобилей/совместимости |
| `Booking\SendBookingCodeSms` | SMS с кодом подтверждения записи (tries 3, backoff 10/60) |

Воркер: `docker compose exec backend-app php artisan queue:work`.

## Runbooks

- **Импорт повис** — проверить очередь (`queue:monitor` / таблица `jobs`), перезапустить воркер; повторный запуск защищён от дублей (ImportType).
- **Кеш справочников устарел** — TTL 1 час; инвалидация Observer'ами при изменении справочников, вручную — `php artisan cache:clear`.
- **Цены не пересчитались** — `PopulateCatalogPrices` вызывается после импорта (ImportMasterJob/PointImportJob); отдельной команды нет.
- **Уведомления не приходят** — проверить Reverb и `BROADCAST_CONNECTION`; уведомления пишутся в БД (`notifications`), websocket — реальное время.
- **Правка в `.env` не применилась** — контейнер держит старые переменные процесса: пересоздать `docker compose up -d` (см. «Окружение»).
- **Сетка слотов не пополняется** — проверить `slots:generate` в планировщике; вручную — `php artisan slots:generate` (идемпотентна).

## See Also

- [Импорт каталога из XLSX](xlsx-import-pipeline.md)
- [Кеширование](caching.md)
- [Каталог: ценообразование](catalog-pricing.md)
- [Архитектура приложения](architecture-layers.md)
