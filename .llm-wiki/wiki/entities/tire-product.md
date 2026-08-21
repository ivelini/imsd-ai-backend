# Шина (TireProduct)

> Sources: Проект (db-schema.md), 2026-08-19; удаление supplier 2026-08-19; slug 2026-08-19; формула slug из характеристик 2026-08-20; евро-лейбл 2026-08-21; SEO-формулы name/slug 2026-08-21; origin и перенос description 2026-08-21
> Raw: [db-schema.md](../../raw/project/db-schema.md); [2026-08-19-drop-supplier.md](../../raw/project/2026-08-19-drop-supplier.md); [2026-08-19-product-slug.md](../../raw/project/2026-08-19-product-slug.md); [2026-08-20-season-default-slug.md](../../raw/project/2026-08-20-season-default-slug.md); [2026-08-21-tire-euro-label.md](../../raw/project/2026-08-21-tire-euro-label.md); [2026-08-21-tire-name-slug-format.md](../../raw/project/2026-08-21-tire-name-slug-format.md); [2026-08-21-product-origin-description-move.md](../../raw/project/2026-08-21-product-origin-description-move.md)

## Overview

`tire_products` — товар «шина»: конкретный типоразмер модели. Модель (`product_models`, e.g. «A503») связывает товары одного семейства; одна модель — много продуктов с разными типоразмерами. `brand_id` на продукте сохранён денормализованно для фильтрации.

## Поля

| Группа | Поля |
|--------|------|
| Идентификация | `id`, `brand_id` (FK, денормализовано), `model_id` (FK → product_models), `name` (отображаемое), `slug` (уникальный, URL карточки), `ean` (артикул) |
| Происхождение | `country_id` (FK → countries). `origin_id` (FK → product_origins, nullable): производитель/страна/год производства (с 2026-08-21). `supplier_id` удалён 2026-08-19 |
| Размеры | `width`, `profile`, `diameter` (diameter — string: может быть «16C», «R16») |
| Индексы | `load_index`, `speed_index` (из «86T» → 86, T) |
| Характеристики | `season` (winter/summer/all-season), `is_studded`, `is_runflat`, `is_xl`, `year` |
| Евро-лейбл | `euro_label` (jsonb: `{rollingResistance: A–G, wetGrip: A–G, noiseEmission: dB}` — value object `EuroLabel` + каст `EuroLabelCast`; мусор в БД → null) |
| Прочее | `image`. Колонка `description` удалена 2026-08-21 — описание живёт на модели (`product_models.description`, text из колонки XLSX `description`) |

`is_xl` и `year` при импорте не заполняются (нет в XLSX) — вручную в админке.

`euro_label` приходит из XLSX-колонки `description_euro_label` («D/C/71») через `RowMapper::parseEuroLabel`; невалидная строка → null (см. [Импорт каталога из XLSX](../concepts/xlsx-import-pipeline.md)).

`name` при импорте собирается по формуле (чистый `TireNameBuilder`, ADR 0001): «Шина {сезон_нж} {бренд} {модель} {width}/{profile} R{diameter} {load}{speed}» — сезон в нижнем регистре, индексы склеиваются из `load_index`+`speed_index` («91»+«T» → «91T»), части пропускаются при отсутствии; признаки «шипованная»/runflat в name не выводятся. Пример: «Шина зимняя Gislaved Soft Frost 200 195/55 R16 91T».

`slug` = `{brand-slug}-{model-slug}-{width}-{profile}-r{diameter}-{load}{speed}[-studded][-runflat]` (с 2026-08-21) — всё в lowercase («91T» → «91t»), единый разделитель дефис, части только при наличии/true; коллизия → суффикс `-2`. Смена подхода: раньше slug был только из характеристик (стабилен при смене названий), теперь SEO-адрес с брендом и моделью — URL меняется при переимпорте и смене названия модели. Генерируется ProductSlugService (БД-обвязка: slug бренда/модели по id) при создании/обновлении (админка, импорт — единый билдер). Пример: `gislaved-soft-frost-200-195-55-r16-91t-studded`.

## Связи

- Полиморф: `stocks` (остатки и цены по складам), `images`, `promotions`, `order_items` (`itemable`).
- Морф-тип: `tire` → `TireProduct` (AppServiceProvider).
- Парсинг размеров: `235/50 R18` → width=235, profile=50, diameter=18.
- `origin` (belongsTo → product_origins): производитель, страна, год производства из origin-колонок XLSX (см. [Происхождение товара (ProductOrigin)](product-origin.md)).

## Цена

Цена клиента — не на продукте: `stocks.purchase_price` → `stocks.price` (наценка склада) → `catalog_prices` по городу (наценка города, акции, округление). См. [Каталог: ценообразование](../concepts/catalog-pricing.md).

## Фасеты каталога

Публичный фильтр работает по полям шины: width, profile, diameter, season, studded, brand, country — см. [Публичный API каталога](../concepts/public-catalog-filter-api.md).

## See Also

- [Диск (WheelProduct)](wheel-product.md)
- [Запись catalog_prices](catalog-price.md)
- [Импорт каталога из XLSX](../concepts/xlsx-import-pipeline.md)
- [Каталог: ценообразование](../concepts/catalog-pricing.md)
