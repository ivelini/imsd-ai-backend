# Импорт каталога из XLSX: пайплайн

> Sources: Проект (db-schema.md), 2026-08-19; Memory-заметка, 2026-07-04; описания и евро-лейбл 2026-08-21; SEO-формулы name/slug 2026-08-21
> Raw: [db-schema.md](../../raw/project/db-schema.md); [2026-08-21-tire-euro-label.md](../../raw/project/2026-08-21-tire-euro-label.md); [2026-08-21-tire-name-slug-format.md](../../raw/project/2026-08-21-tire-name-slug-format.md)

## Overview

Загрузка XLSX → парсинг → чанки → параллельная обработка → каталог + остатки. Потоковое чтение через `openspout/openspout:^4`. Импортируются: шины, диски, модели товаров, автомобили (CSV), точки выдачи с ценами и сроками. Одна строка XLSX = одна позиция на складе.

## Конвейер

```
POST /api/admin/tires/import (auth:sanctum, xlsx ≤50MB)
  → Controller: файл на диск → dispatch ImportMasterJob → 202
CatalogImport\ImportMasterJob (тонкий оркестратор)
  → Preconditions: FileExists, FileColumnsValid
  → Action ParseImportFile: чтение XLSX, валидация колонок, JSON-чанки на диск (config: chunk_size 500)
  → ProductImport::create (аудит-трейл) + ImportStatusUpdater → Batch dispatch ChunkJob'ов (tries=3, backoff=[1,5,15])
CatalogImport\ChunkJob → upsert товаров/остатков (updateOrCreate по ключам)
```

Чанки пишутся как JSON на диск, а не в очередь — для дебаггабильности и экономии места в jobs table.

## Маппинг (XLSX → БД)

Ключевые правила — полная таблица в [db-schema.md §Маппинг](../../raw/project/db-schema.md), здесь суть:

- `product_article` → `ean`; `vendor` → поиск/создание бренда; `name` → модель (для дисков парсится `parseWheelModelName()`: убирает «Диск » и размерную часть). Slug модели (с 2026-08-21) — только из названия модели (`Str::slug(name)`), без префикса бренда; уникальность парой (brand_id, slug).
- Name товара (шины) с 2026-08-21 собирается по формуле TireNameBuilder: «Шина {сезон} {бренд} {модель} {размер} {индекс}» (SEO), slug — `{brand-slug}-{model-slug}-{width}-{profile}-r{diameter}-{load}{speed}[-studded][-runflat]`; диск — slug с точками→дефисами. Детали — в [Шина (TireProduct)](../entities/tire-product.md) и [Диск (WheelProduct)](../entities/wheel-product.md).
- Размеры: `235/50 R18` → width/profile/diameter; диски `7.5 x 18 ET45` → width/diameter/et; PCD `5*114.3` → 5×114.3; болты `12*1.5` → qty×size.
- `load_speed_index` → split: `86T` → load=86, speed=T.
- Булевы: «Да»/«Нет» → true/false (`config: boolean_true`).
- Сезоны: «зимняя» → winter, «летняя» → summer, «всесезон» → all-season; тип диска «Литые» → alloy.
- `stock` → склад (у кого купили). Колонка `supplier` (завод-изготовитель) удалена из маппинга 2026-08-19 вместе со справочником.
- `price` → `stocks.purchase_price`; наценка склада применяется при импорте → `stocks.price`; далее `catalog_prices` (см. [Каталог: ценообразование](catalog-pricing.md)).
- `image` — URL, скачивается и кладётся в `images` (`is_main=true`).
- Описания (`description_vendor`, `description_default`, `description_manufacture_country`, `description_manufacture_year`) — JSON-объект в `tire_products.description` (null, если пусто). С 2026-08-21 строка маппится `RowMapper::map()` в `TireRowProcessor` (раньше `ImportTireRow::fromArray()` терял описания — писалось `"[]"`).
- Евро-лейбл `description_euro_label` («D/C/71») → `tire_products.euro_label` jsonb `{rollingResistance, wetGrip, noiseEmission}` через `RowMapper::parseEuroLabel` (буквы A–G, шум 2–3 цифры; невалидно → null, строка не падает). Отдельная колонка, в JSON-описании не дублируется.
- Точки выдачи: регион/city → regions/cities; колонки диапазонов `0-5000 … 15001-100000` → `city_price_rules`; срок → `city_delivery_times`; адрес и контакты → `delivery_points`.
- Автомобили (CSV): марка → модели → модификации (поколение, годы); шины/диски OEM/замена/тюнинг — split по `|`, каждое значение → своя строка (`vehicle_tire_sizes` / `vehicle_wheel_specs`).

## Инвалидация после импорта

`catalog_prices` обновляется для затронутых остатков; при импорте точек — полный пересчёт. Кеши фильтра — явный `forget()` (ImportMasterJob / PointImportJob), см. [Кеширование](caching.md) и [Публичный API каталога](public-catalog-filter-api.md).

## Исходные файлы

`documentations/import/`: tires.xlsx (382), wheels.xlsx (431), models.xlsx, vehicle.csv (~450 строк), points.xlsx (80 городов).

## See Also

- [Шина (TireProduct)](../entities/tire-product.md)
- [Диск (WheelProduct)](../entities/wheel-product.md)
- [Каталог: ценообразование](catalog-pricing.md)
- [Город и география](../entities/geo-city.md)
