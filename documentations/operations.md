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
make docs          # сгенерировать документацию API (Scribe) в documentations/openapi/
```

Artisan — только через контейнер: `docker compose exec backend-app php artisan ...`

Filament-панель `/panel`: ассеты (css/js/шрифты) публикуются в `public/` автоматически при старте контейнера `backend-app` (`php artisan filament:assets` в entrypoint.sh) — при пересборке образа или после `composer update` панель без них отдаётся без стилей. Разово вручную: `docker compose exec backend-app php artisan filament:assets`.

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
| `SMS_STUB` | Заглушка SMS-кода записи на шиномонтаж (фиксированный код вместо провайдера) | true |
| `SMS_STUB_CODE` | Код заглушки (4 цифры) | 1234 |
| `SMS_PROVIDER` | Провайдер SMS (не выбран — `LogSmsSender` пишет код в лог) | — |
| `SMS_RESEND_COOLDOWN_SECONDS` | Кулдаун повторной отправки кода | 60 |
| `POSTMARK_API_KEY` и др. | Почтовые драйверы (не используются активно) | — |

Секреты в репозиторий не коммитятся — только имена.

**Правка `backend/.env` требует пересоздания контейнеров.** `.env` подключён через `env_file` в `docker-compose.yml` — значения попадают в переменные процесса **при создании** контейнера и там замораживаются. Laravel читает переменные процесса раньше `.env`-файла и не перезаписывает их, поэтому правка без пересоздания не действует. Пересоздать: `docker compose up -d` (пересоздаёт сам, если compose увидел изменение конфигурации) или принудительно `docker compose up -d --force-recreate backend-app backend-queue backend-scheduler reverb`. Симптом: значение не применилось, `docker compose exec backend-app env | grep APP_...` показывает старое.

Локаль интерфейса — `APP_LOCALE=ru` (переводы кнопок/лейблов Filament идут из `vendor/filament/*/resources/lang/ru`; своих `lang/` в проекте нет).

## Очереди и планировщик

Плановые задачи (`routes/console.php`): `promotions:sync` — каждые 5 минут (акционные цены), `slots:generate` — каждые 15 минут (сетка слотов записи на шиномонтаж на горизонт `booking_horizon_days`; идемпотентна). Контейнер `backend-scheduler` запускает `php artisan schedule:work`; без него не обновятся ни акционные цены, ни сетка слотов.

Импорты запускаются со страницы панели `/panel/catalog/import` и диспатчатся в очередь (database):

| Job | Что делает |
|---|---|
| `CatalogImport\MasterJob` / `WheelMasterJob` | Разбор XLSX → чанки JSON → `ChunkJob` |
| `CatalogImport\ChunkJob` / `WheelChunkJob` | Импорт чанка: товары, остатки, цены |
| `CatalogImport\ModelImportJob` | Импорт моделей товаров |
| `GeoImport\PointImportJob` | Импорт пунктов выдачи → пересчёт `catalog_prices` |
| `Booking\SendBookingCodeSms` | SMS с кодом подтверждения записи (tries 3, backoff 10/60) |

Воркер: `docker compose exec backend-app php artisan queue:work` (запуск — на усмотрение инфраструктуры).

## Ручные сценарии

- **«Импорт шин/дисков повис»** — проверить очередь: `php artisan queue:monitor` / таблица `jobs`; перезапустить воркер; повторный запуск импорта в админке защищён от дублей (ImportType).
- **«Кеш справочников устарел»** — TTL 1 час (`CACHE_TTL_REFERENCES`); принудительный сброс — инвалидация Observer'ами при изменении бренда/поставщика, вручную: `php artisan cache:clear`.
- **«Цены в каталоге не пересчитались»** — `PopulateCatalogPrices` вызывается после импорта (MasterJob/PointImportJob), при правке остатков и акций в панели, а также задачей `promotions:sync`; вручную — `php artisan promotions:sync` (пересчёт акционных товаров) или повторный импорт.
- **«Акция началась, но цены старые»** — проверить, работает ли `backend-scheduler` (`schedule:work`); пересчёт запускается каждые 5 минут. Ручной прогон: `php artisan promotions:sync`.
- **«Уведомления не приходят в админку»** — проверить Reverb (`REVERB_*`) и `BROADCAST_CONNECTION`; уведомления пишутся в БД (`notifications`), вебсокет — доставка в реальном времени.
- **«Сетка слотов не пополняется»** — проверить `backend-scheduler`; вручную — `php artisan slots:generate` или кнопка «Сгенерировать сетку» в ресурсе «Слоты» панели (кластер «Шиномонтаж»).
- **«Код записи не приходит»** — в v1 SMS-заглушка: `LogSmsSender` пишет код в лог (`SMS на 7…`); `SMS_STUB_CODE` фиксирует код. Подключение провайдера — `SMS_PROVIDER` + новая реализация контракта `Services\Booking\SmsSender`.

## Миграции и данные

`make fresh` — полный сброс БД с сидами (эталонные данные: склады, расписания отгрузки, регионы/города; справочники записи — каталог услуг/прайс, шаблон недели, настройки). Локально (`local`) дополнительно: сетка слотов (`BookingSlotSeeder`) и демо-записи вокруг сегодняшнего дня (`DemoBookingSeeder`). Продакшен-миграции — `php artisan migrate`.
