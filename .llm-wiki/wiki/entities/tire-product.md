# Шина (TireProduct)

> Sources: Проект (db-schema.md), 2026-08-19; удаление supplier 2026-08-19
> Raw: [db-schema.md](../../raw/project/db-schema.md); [2026-08-19-drop-supplier.md](../../raw/project/2026-08-19-drop-supplier.md)

## Overview

`tire_products` — товар «шина»: конкретный типоразмер модели. Модель (`product_models`, e.g. «A503») связывает товары одного семейства; одна модель — много продуктов с разными типоразмерами. `brand_id` на продукте сохранён денормализованно для фильтрации.

## Поля

| Группа | Поля |
|--------|------|
| Идентификация | `id`, `brand_id` (FK, денормализовано), `model_id` (FK → product_models), `name` (отображаемое), `ean` (артикул) |
| Происхождение | `country_id` (FK → countries). `supplier_id` удалён 2026-08-19 — не использовался в бизнес-логике |
| Размеры | `width`, `profile`, `diameter` (diameter — string: может быть «16C», «R16») |
| Индексы | `load_index`, `speed_index` (из «86T» → 86, T) |
| Характеристики | `season` (winter/summer/all-season), `is_studded`, `is_runflat`, `is_xl`, `year` |
| Прочее | `description` (HTML), `image` |

`is_xl` и `year` при импорте не заполняются (нет в XLSX) — вручную в админке.

## Связи

- Полиморф: `stocks` (остатки и цены по складам), `images`, `promotions`, `order_items` (`itemable`).
- Морф-тип: `tire` → `TireProduct` (AppServiceProvider).
- Парсинг размеров: `235/50 R18` → width=235, profile=50, diameter=18.

## Цена

Цена клиента — не на продукте: `stocks.purchase_price` → `stocks.price` (наценка склада) → `catalog_prices` по городу (наценка города, акции, округление). См. [Каталог: ценообразование](../concepts/catalog-pricing.md).

## Фасеты каталога

Публичный фильтр работает по полям шины: width, profile, diameter, season, studded, brand, country — см. [Публичный API каталога](../concepts/public-catalog-filter-api.md).

## See Also

- [Диск (WheelProduct)](wheel-product.md)
- [Запись catalog_prices](catalog-price.md)
- [Импорт каталога из XLSX](../concepts/xlsx-import-pipeline.md)
- [Каталог: ценообразование](../concepts/catalog-pricing.md)
