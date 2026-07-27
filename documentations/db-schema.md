# Схема БД — Интернет-магазин шин и дисков

## Домен: Catalog

| Таблица | Назначение | Ключевые поля |
|---------|------------|---------------|
| `brands` | Бренды товаров (Winter Drive...) | `id`, `name`, `slug`, `logo`, `description`, `type` (tire/wheel/both) |
| `suppliers` | Заводы-производители (Cordiant, Nokian…) | `id`, `name`, `code` |
| `countries` | Страны (ISO-3166) | `id`, `code`, `name` |
| `warehouses` | Склады — крупные продавцы, у которых мы покупаем | `id`, `name` |
| `warehouse_markup_rules` | Правила наценки по складам | `id`, `warehouse_id`, `price_from`, `price_to`, `coefficient` |
| `tire_products` | Шины | `id`, `brand_id`, **`name`**, `supplier_id`, **`country_id`**, `season`, `width`, `profile`, `diameter`, `load_index`, `speed_index`, `is_studded`, `is_runflat`, `is_xl`, `year`, `ean`, `description` |
| `wheel_products` | Диски | `id`, `brand_id`, **`name`**, `supplier_id`, **`country_id`**, `type` (alloy/steel/forged), `color`, `pcd`, `et`, `hub_diameter`, `width`, `diameter`, `ean` |
| `stocks` | Остатки и цены на складах (полиморф) | `id`, `stockable_type`, `stockable_id`, `warehouse_id`, `price`, `quantity`, `purchase_price` |
| `images` | Изображения (полиморф) | `id`, `imageable_type`, `imageable_id`, `path`, `sort`, `is_main` |
| `promotions` | Акции (полиморф) | `id`, `name`, `description`, `type`, `value`, `starts_at`, `ends_at`, `promotable_type`, `promotable_id` |

**Примечание:** `supplier_id` в товаре — кто произвёл. `warehouse_id` в stock — у кого купили. Это разные сущности.

**Изображения:** таблица `images` поддерживает несколько изображений на один товар (полиморф). При импорте загружается основное фото (`is_main = true`). В админке можно добавлять/удалять дополнительные фото и менять порядок (`sort`).

## Домен: Vehicle

| Таблица | Назначение | Ключевые поля |
|---------|------------|---------------|
| `vehicle_makes` | Марки автомобилей | `id`, `name` |
| `vehicle_models` | Модели (make_id) | `id`, `make_id`, `name`, `generation` |
| `vehicle_modifications` | Модификации (model_id) | `id`, `model_id`, `name` (двигатель + комплектация) |
| `vehicle_tire_sizes` | Типоразмеры шин для модификации | `id`, `modification_id`, `type` (SpecType), `width`, `profile`, `diameter` |
| `vehicle_wheel_specs` | Параметры дисков для модификации | `id`, `modification_id`, `type` (SpecType), `width`, `diameter`, `et`, `pcd`, `hub_diameter`, `bolts` |

**Примечание по `generation`:** номер поколения из CSV (CL II, MDX II (YD2)). Может быть пустым.

**SpecType enum:** `oem`, `replacement`, `tuning` (см. `app/Enums/SpecType.php`).

**Парсинг размеров при импорте:**
- Шины: `235/50 R18` → width=235, profile=50, diameter=18
- Диски: `7.5 x 18 ET45` → width=7.5, diameter=18, et=45
- PCD: `5*114.3` → pcd_lugs=5, pcd_diameter=114.3
- Болты: `12*1.5` → bolts_qty=12, bolts_size=1.5
- Несколько значений через `|` — создаём несколько строк с одним modification_id

## Домен: Geo / Delivery

| Таблица | Назначение |
|---------|------------|
| `regions` | Регионы | `id`, **`code`**, `name` |
| `cities` | Города (region_id) | `id`, `region_id`, `name`, `sort` |
| `city_price_rules` | Правила наценки по городам (city_id, price_from, price_to, markup) | |
| `catalog_prices` | Предрасcчитанные цены и сроки доставки (stock_id, city_id, price, delivery_min, delivery_max) | |
| `delivery_points` | Точки выдачи (city_id) | `id`, `city_id`, `address`, `phone`, `email`, `work_hours`, `info`, `pickup_from_truck` |
| `delivery_point_coefficients` | Коэффициенты доставки (price_from, price_to, product_type, coefficient) | |
| `delivery_schedules` | График отгрузки со складов (warehouse_id, day_of_week, cutoff_time, days_before, days_after) | |
| `city_delivery_times` | Время доставки из Челябинска в город клиента (city_id, delivery_days) | |

**Важно:** склад (`warehouses`) и точка выдачи (`delivery_points`) — разные сущности. Склад — кто продал нам товар. Точка выдачи — где клиент его забирает.

## Домен: Order

| Таблица | Назначение |
|---------|------------|
| `orders` | Заказы (user_id, status, total, delivery_type, payment_method, contact_info) |
| `order_items` | Позиции заказа (itemable polymorphic: tire/wheel) |

## Домен: Auth

| Таблица | Назначение |
|---------|------------|
| `users` | Клиенты (phone, email, password, name) |
| `admins` | Администраторы (email, password) |
| `admin_roles` | Роли администраторов |

## Полиморфные связи

- `stocks` — `stockable` → `tire_product` / `wheel_product`
- `images` — `imageable` → `tire_product` / `wheel_product`
- `promotions` — `promotable` → `tire_product` / `wheel_product`
- `order_items` — `itemable` → `tire_product` / `wheel_product`

**Morph-map в `AppServiceProvider`:** `tire → Tire`, `wheel → Wheel`, `article → Article`.

## Формат полей

- **`description`** в товарах — HTML. При импорте из XLSX берётся из колонок `vendor_description` / `description_default`.
- **`is_runflat`**, **`is_studded`**, **`pickup_from_truck`** — boolean. В XLSX: «Да» / «Нет».
- **`season`** — enum: winter, summer, all-season. В XLSX: «зимняя», «летняя», «всесезон».
- **`type`** в wheel_products — enum: alloy, steel, forged. В XLSX: «Литые», «Штампованные», «Кованые».
- **`type`** в brands — enum: tire, wheel, both.
- **`is_xl`** и **`year`** в tire_products — не заполняются при импорте (отсутствуют в XLSX). Заполняются вручную в админке.
- **`image`** при импорте — URL. Система скачивает файл, сохраняет локально/S3, записывает путь в `images.path`.

## Принципы миграций

- Одна миграция — одна таблица (исключение: pivot-таблицы)
- Данные — только в сидерах (не в миграциях)

**Исходные файлы импорта** находятся в `documentations/import/`:
- `tires.xlsx` — 382 товара (шины)
- `wheels.xlsx` — 431 товар (диски)
- `vehicle.csv` — ~450 строк (автомобили + совместимость)
- `points.xlsx` — 80 городов (точки выдачи + цены + сроки)

## Маппинг импорта (XLSX → БД)

### Товары (шины)

| XLSX (колонка) | Таблица | Поле | Примечание |
|----------------|---------|------|------------|
| `product_article` | tire_products | `ean` | Артикул |
| `vendor` | brands → tire_products | `brand_id` | Поиск/создание бренда по имени |
| `name` | tire_products | `name` | Модель (A503) |
| `season` | tire_products | `season` | Парсинг: зимняя→winter |
| `country` | countries → tire_products | `country_id` | Поиск/создание страны |
| `width`, `height`, `diameter` | tire_products | `width`, `profile`, `diameter` | |
| `load_speed_index` | tire_products | `load_index`, `speed_index` | Разделить: 86T → load=86, speed=T |
| `is_runflat` | tire_products | `is_runflat` | Да→true |
| `is_spike` | tire_products | `is_studded` | Да→true |
| `supplier` | suppliers → tire_products | `supplier_id` | Поиск/создание поставщика |
| `stock` | warehouses → stocks | `warehouse_id` | Поиск/создание склада |
| `count` | stocks | `quantity` | |
| `price` | stocks | `purchase_price` | |
| `image` | images | `path` | Загрузить по URL, is_main=true |
| `vendor_description` | tire_products | `description` | HTML-описание |
| `promo_1..5` | promotions | — | Маркетинговые тексты (опционально) |

### Товары (диски) — аналогично шинам, ключевые отличия:

| XLSX | Таблица | Поле |
|------|---------|------|
| `pcd1` + `pcd2` | wheel_products | `pcd` → `5*114.3` |
| `dia` | wheel_products | `hub_diameter` |
| `et` | wheel_products | `et` |
| `type` (Литые) | wheel_products | `type` → alloy |

### Автомобили (CSV)

| Колонка CSV | Таблица | Поле |
|-------------|---------|------|
| Марка | vehicle_makes | `name` |
| Модель | vehicle_models | `name` |
| Поколение | vehicle_models | `generation` |
| Модификация (двигатель) | vehicle_modifications | `name` |
| Год | vehicle_modifications | — (каждый год — отдельная строка) |
| Шины OEM/замена/тюнинг | vehicle_tire_sizes | split по `\|`, парсинг `235/50 R18` |
| Диски OEM/замена/тюнинг | vehicle_wheel_specs | split по `\|`, парсинг `7.5 x 18 ET45` |
| PCD, DIA, Болты | vehicle_wheel_specs | `pcd`, `hub_diameter`, `bolts` |

### Точки выдачи (XLSX)

| Колонка XLSX | Таблица | Поле |
|-------------|---------|------|
| `code` | regions | `code` |
| `region_name` | regions | `name` |
| `city_name` | cities | `name` |
| `0-5000` … `15001-100000` | city_price_rules | Парсинг имени колонки → price_from/price_to, значение → markup |
| `Срок доставки` | city_delivery_times | `delivery_days` |
| `address` | delivery_points | `address` |
| `work_days` + `weekend` | delivery_points | `work_hours` |
| `Телефон` | delivery_points | `phone` |
| `Эл.почта` | delivery_points | `email` |
| `Доп.информация` | delivery_points | `info` |
| `Выдача с борта` | delivery_points | `pickup_from_truck` |
