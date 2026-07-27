# План: Админ-панель каталога — импорт шин

> **Дата:** 2026-07-27
> **Основание:** FR ADM-3.4 (Импорт товаров), `documentations/fr/admin-functional-spec.md`
> **Архитектура:** `documentations/architecture.md`, `CLAUDE.md`
> **Схема БД:** `documentations/db-schema.md`

---

## Цель

Реализовать в админ-панели (`routes/admin.php`, префикс `/api/admin`) функционал импорта шин из XLSX в каталог: загрузка файла → парсинг → создание/обновление товаров, остатков, справочников.

## Текущее состояние

- Код приложения — пустой Laravel (3 файла: `Controller.php`, `User.php`, `AppServiceProvider.php`).
- Миграции — только стандартные (users, cache, jobs).
- Документация — полная (`db-schema.md`, `architecture.md`, FR/TZ).
- Предыдущая реализация импорта (устаревшая схема: таблицы `tires`, `marks`, `seasons`) — не используется. Новая схема: `tire_products`, `brands`, `suppliers`, `countries`, `warehouses`, `stocks` (полиморф).

---

## Задачи

### Этап 1: Фундамент — миграции, модели, енумы, админ-авторизация

Без этого импорт некуда сохранять. Нужно создать схему БД каталога.

| № | Задача | Описание | Файлы |
|---|--------|----------|-------|
| 1.1 | Миграции Catalog | brands, suppliers, countries, warehouses, warehouse_markup_rules, tire_products, wheel_products, stocks, images, promotions | 10 миграций |
| 1.2 | Миграции Vehicle | vehicle_makes, vehicle_models, vehicle_modifications, vehicle_tire_sizes, vehicle_wheel_specs | 5 миграций |
| 1.3 | Миграции Delivery | regions, cities, city_price_rules, catalog_prices, delivery_points, delivery_point_coefficients, delivery_schedules, city_delivery_times | 8 миграций |
| 1.4 | Миграции Auth | admins, admin_roles, admin_role_user | 3 миграции |
| 1.5 | Миграции Order | orders, order_items | 2 миграции |
| 1.6 | Enums | ProductType, WheelType, SpecType, Season, PromotionType, DiscountType, OrderState, WeekDay — `app/Enums/` | 8 файлов |
| 1.7 | Casts | SeasonCast, WheelTypeCast — для типизации полей в моделях | 2 файла |
| 1.8 | Eloquent-модели | 24 модели: Brand, Supplier, Country, Warehouse, TireProduct, WheelProduct, Stock, Image, Promotion, VehicleMake, VehicleModel, VehicleModification, VehicleTireSize, VehicleWheelSpec, Region, City, DeliveryPoint, DeliveryPointCoefficient, DeliverySchedule, Admin, AdminRole, Order, OrderItem | 24 файла |
| 1.9 | Morph map | `tire → TireProduct`, `wheel → WheelProduct`, `article → Article` в `AppServiceProvider` | 1 файл (правка) |
| 1.10 | Админ-авторизация | Middleware + Sanctum setup + `routes/admin.php` + Admin/LoginController | 3-4 файла |

**Ожидаемый результат:** Полная схема БД каталога, модели с отношениями, админ может войти.

---

### Этап 2: Импорт шин (XLSX → БД)

Реализация пайплайна импорта по ADM-3.4. Используем `openspout/openspout` для потокового чтения XLSX.

**Поток данных:**
```
Upload XLSX → Validate file/columns → Parse XLSX → Split into chunks → Dispatch ChunkJobs (queue) → Each chunk: resolve references → upsert tire_products → upsert stocks
```

| № | Задача | Описание | Файлы |
|---|--------|----------|-------|
| 2.1 | Composer-зависимость | `composer require openspout/openspout:^4.0` | — |
| 2.2 | Config | `config/tire_import.php` — chunk_size (500), column_map, boolean_true, disk | 1 файл |
| 2.3 | DTOs | ImportTireRow (20 полей), ImportChunk (rows + batchId), ParseImportFileInput (4 поля), ParsedImportFileResult (headerColumns + chunkPaths + totalRows) | 4 файла |
| 2.4 | Services | ReferenceResolver (firstOrCreate для Brand, Supplier, Country, Warehouse), RowMapper (XLSX row → DTO), DescriptionBuilder (JSONB), SlugGenerator | 4 файла |
| 2.5 | Actions | ParseImportFile (читает XLSX, валидирует колонки, режет на чанки), UpsertTireProduct (updateOrCreate по ean), UpsertStock (updateOrCreate по composite key) | 3 файла |
| 2.6 | Preconditions | FileExists (файл на диске), FileColumnsValid (обязательные колонки) | 2 файла |
| 2.7 | Jobs | MasterJob (оркестратор: precondition → ParseImportFile → TireImport audit → dispatch ChunkJobs), ChunkJob (tries=3, backoff, обрабатывает чанк) | 2 файла |
| 2.8 | HTTP | UploadFileRequest (file, xlsx, max:50MB), ImportTireController (store → dispatch → 202), маршрут `POST /api/admin/catalog/tires/import` | 3 файла |
| 2.9 | Статус импорта | Миграция для `tire_imports` (audit-таблица), эндпоинт `GET /api/admin/catalog/tires/import/{id}` | 2 файла |
| 2.10 | Тесты | TireImportTest (файл из `documentations/import/tires.xlsx`) | 1 файл |

**Ожидаемый результат:** Админ загружает XLSX → товары и остатки в БД. Видит статус импорта.

---

### Этап 3 (следующие): Импорт дисков, CRUD каталога, управление остатками

После импорта шин — по аналогии импорт дисков, затем интерфейсы редактирования.

| № | Задача | Приоритет |
|---|--------|-----------|
| 3.1 | Импорт дисков (wheel_products) | После 2 |
| 3.2 | Brands CRUD (ADM-3.5) | После 2 |
| 3.3 | Suppliers CRUD (ADM-4.3) | После 2 |
| 3.4 | Warehouses CRUD (ADM-4.1) | После 2 |
| 3.5 | Список товаров каталога (ADM-3.1) | После 2 |
| 3.6 | Редактирование товара (ADM-3.2) | После 3.5 |
| 3.7 | Изображения товаров (ADM-3.3) | После 3.5 |
| 3.8 | Правила наценки складов (ADM-4.2) | После 3.4 |
| 3.9 | Акции (ADM-7) | После 3.6 |

---

## Маппинг импорта (XLSX шины → БД)

Источник: `documentations/db-schema.md` (таблица маппинга).

| Колонка XLSX | Таблица | Поле | Обработка |
|-------------|---------|------|-----------|
| `product_article` | tire_products | `ean` | Уникальный идентификатор |
| `vendor` | brands → tire_products | `brand_id` | FirstOrCreate по name, type=tire |
| `name` | tire_products | `name` | Модель (A503) |
| `season` | tire_products | `season` | Парсинг: "зимняя" → winter |
| `country` | countries → tire_products | `country_id` | FirstOrCreate по name |
| `width` | tire_products | `width` | int |
| `height` | tire_products | `profile` | int |
| `diameter` | tire_products | `diameter` | string |
| `load_speed_index` | tire_products | `load_index`, `speed_index` | Разделение: 86T |
| `is_runflat` | tire_products | `is_runflat` | "Да" → true |
| `is_spike` | tire_products | `is_studded` | "Да" → true |
| `supplier` | suppliers → tire_products | `supplier_id` | FirstOrCreate |
| `stock` | warehouses → stocks | `warehouse_id` | FirstOrCreate |
| `count` | stocks | `quantity` | int |
| `price` | stocks | `purchase_price` | decimal |
| `image` | images (TODO: Этап 3) | `path` | Скачать по URL |
| `vendor_description` | tire_products | `description` | JSONB |
| `promo_1..5` | promotions (TODO: Этап 3) | | |

---

## Ключевые архитектурные решения

1. **Полиморфные stocks**: `stockable_type` + `stockable_id` — одна таблица для шин и дисков. При импорте шин: `stockable_type = 'tire_product'`.
2. **Повторный импорт**: `updateOrCreate` по `ean` (артикулу). Если EAN совпадает — обновляем поля и остатки.
3. **Ценообразование**: После импорта `purchase_price` → найти правило наценки склада → рассчитать `price`. Пока rules не настроены — `price = purchase_price`.
4. **Чанки через JSON на диск**: Парсим XLSX, пишем чанки как JSON-файлы в `storage/app/import/`, в очередь летят только пути к чанкам. Экономит место в БД очереди.
5. **Очередь**: Database driver (замена на Redis — позже, когда понадобится Horizon).
6. **TODO: изображения** — загрузка по URL вынесена в отдельную задачу (после импорта товаров).
7. **TODO: акции** — promo_1-5 из XLSX сохраняются пока как JSONB в `tire_products` (или просто игнорируются до реализации ADM-7).

## Ожидаемый результат

После Этапов 1-2:
- Загруженный каталог шин из XLSX
- Админ может загружать новые прайсы
- Система показывает статус и ошибки импорта
- База готова для пользовательского каталога

---

## Референсы

- Предыдущая реализация (устаревшая): описана в памяти `tire-import-implementation` — пакет openspout, архитектура Action/Job/Precondition — референс для новой.
- Схема БД: `documentations/db-schema.md`
- FR импорта: `documentations/fr/admin-functional-spec.md` (ADM-3.4)
- Архитектура: `documentations/architecture.md`, `CLAUDE.md`
