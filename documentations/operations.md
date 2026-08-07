# Эксплуатация

## Запуск и команды

Всё — через Docker (PHP-хост не подходит: composer.lock требует >= 8.4).

```bash
make up            # поднять стек (docker compose up -d)
make down          # остановить
make build         # пересобрать образы
make bash          # bash в контейнер app
make fresh         # migrate:fresh --seed (пересоздать БД с сидами)
make lint          # pint --test (проверка стиля)
make lint-fix      # pint (исправить стиль)
make phpstan       # статический анализ (level 6)
make test          # php artisan test
```

Artisan — только через контейнер: `docker compose exec backend-app php artisan ...`

## Переменные окружения

Значимые (помимо стандартных Laravel: APP_*, DB_*, CACHE_*):

| Переменная | Назначение | По умолчанию |
|---|---|---|
| `CACHE_TTL_REFERENCES` | TTL кеша справочников (дропдауны) | 3600 |
| `QUEUE_CONNECTION` | Очередь (импорты — Jobs) | database |
| `BROADCAST_CONNECTION` | Шина событий (уведомления админки) | reverb |
| `REVERB_*` | Reverb-сервер (websocket уведомлений) | — |
| `TIRE_IMPORT_CHUNK_SIZE` | Строк XLSX на ChunkJob | 500 |
| `TIRE_IMPORT_DISK` | Диск JSON-чанков импорта шин | local |
| `POINT_IMPORT_DISK` | Диск чанков импорта пунктов выдачи | local |
| `POSTMARK_API_KEY` и др. | Почтовые драйверы (не используются активно) | — |

Секреты в репозиторий не коммитятся — только имена.

## Очереди и импорты

Крона нет (`routes/console.php` пуст). Импорты запускаются через админку и диспатчатся в очередь (database):

| Job | Что делает |
|---|---|
| `CatalogImport\MasterJob` / `WheelMasterJob` | Разбор XLSX → чанки JSON → `ChunkJob` |
| `CatalogImport\ChunkJob` / `WheelChunkJob` | Импорт чанка: товары, остатки, цены |
| `CatalogImport\ModelImportJob` | Импорт моделей товаров |
| `GeoImport\PointImportJob` | Импорт пунктов выдачи → пересчёт `catalog_prices` |

Воркер: `docker compose exec backend-app php artisan queue:work` (запуск — на усмотрение инфраструктуры).

## Ручные сценарии

- **«Импорт шин/дисков повис»** — проверить очередь: `php artisan queue:monitor` / таблица `jobs`; перезапустить воркер; повторный запуск импорта в админке защищён от дублей (ImportType).
- **«Кеш справочников устарел»** — TTL 1 час (`CACHE_TTL_REFERENCES`); принудительный сброс — инвалидация Observer'ами при изменении бренда/поставщика, вручную: `php artisan cache:clear`.
- **«Цены в каталоге не пересчитались»** — `PopulateCatalogPrices` вызывается после импорта (MasterJob/PointImportJob); вручную — через тот же импорт или пересчёт (нет отдельной команды — создать при необходимости).
- **«Уведомления не приходят в админку»** — проверить Reverb (`REVERB_*`) и `BROADCAST_CONNECTION`; уведомления пишутся в БД (`notifications`), вебсокет — доставка в реальном времени.

## Миграции и данные

`make fresh` — полный сброс БД с сидами (эталонные данные: склады, расписания отгрузки, регионы/города). Продакшен-миграции — `php artisan migrate`.
