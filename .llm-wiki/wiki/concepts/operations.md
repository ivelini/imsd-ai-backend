# Эксплуатация: команды, окружение, очереди, runbooks

> Sources: Проект (operations.md), 2026-08-19; Код (app/Jobs), 2026-08-19
> Raw: [operations.md](../../raw/project/operations.md)

## Overview

Всё через Docker (`make ...`); artisan — только через контейнер (`docker compose exec backend-app php artisan ...`). Крона нет (`routes/console.php` пуст) — импорты запускаются через админку и диспатчатся в очередь (database). Реальный состав Job'ов сверен с кодом.

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

Секреты в репозиторий не коммитятся — только имена.

## Очереди и импорты (фактические классы)

| Job | Что делает |
|---|---|
| `CatalogImport\ImportMasterJob` | Разбор XLSX → чанки JSON → Batch ChunkJob'ов |
| `CatalogImport\ChunkJob` | Импорт чанка: товары, остатки, цены |
| `CatalogImport\ModelImportJob` | Импорт моделей товаров |
| `GeoImport\PointImportJob` | Импорт точек выдачи → пересчёт `catalog_prices` |
| `VehicleImport\VehicleImportMasterJob` | Импорт автомобилей/совместимости |

Воркер: `docker compose exec backend-app php artisan queue:work`.

## Runbooks

- **Импорт повис** — проверить очередь (`queue:monitor` / таблица `jobs`), перезапустить воркер; повторный запуск защищён от дублей (ImportType).
- **Кеш справочников устарел** — TTL 1 час; инвалидация Observer'ами при изменении справочников, вручную — `php artisan cache:clear`.
- **Цены не пересчитались** — `PopulateCatalogPrices` вызывается после импорта (ImportMasterJob/PointImportJob); отдельной команды нет.
- **Уведомления не приходят** — проверить Reverb и `BROADCAST_CONNECTION`; уведомления пишутся в БД (`notifications`), websocket — реальное время.

## See Also

- [Импорт каталога из XLSX](xlsx-import-pipeline.md)
- [Кеширование](caching.md)
- [Каталог: ценообразование](catalog-pricing.md)
- [Архитектура приложения](architecture-layers.md)
