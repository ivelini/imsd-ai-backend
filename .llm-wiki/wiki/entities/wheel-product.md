# Диск (WheelProduct)

> Sources: Проект (db-schema.md), 2026-08-19; удаление supplier 2026-08-19; slug 2026-08-19; публичный каталог и касты 2026-08-21; slug без точек 2026-08-21
> Raw: [db-schema.md](../../raw/project/db-schema.md); [2026-08-19-drop-supplier.md](../../raw/project/2026-08-19-drop-supplier.md); [2026-08-19-product-slug.md](../../raw/project/2026-08-19-product-slug.md); [2026-08-21-wheel-catalog.md](../../raw/project/2026-08-21-wheel-catalog.md); [2026-08-21-tire-name-slug-format.md](../../raw/project/2026-08-21-tire-name-slug-format.md)

## Overview

`wheel_products` — товар «диск». Структура повторяет шину (brand_id/model_id денормализовано), но геометрия своя: посадочные параметры и тип материала. Модель диска парсится из полного имени продукта (`parseWheelModelName()`: убирает префикс «Диск » и размерную часть).

## Поля

| Группа | Поля |
|--------|------|
| Идентификация | `id`, `brand_id`, `model_id` (FK → product_models), `name`, `slug` (уникальный, URL карточки), `ean` |
| Происхождение | `country_id`. `supplier_id` удалён 2026-08-19 — не использовался в бизнес-логике |
| Тип | `type` (alloy/steel/forged — литой/штампованный/кованый), `color` |
| Геометрия | `width`, `diameter`, `et` (вылет), `pcd` (напр. «5*114.3»), `hub_diameter` (DIA), `bolts` |
| Прочее | `description`, `image` |

Касты (с 2026-08-21): `width`, `et`, `hub_diameter` — `decimal:1` (стабильная строка '6.5'/'38.0' на чтении/записи; sqlite в тестах терял дробную часть). Фильтры `byWidths/byEts/byHubDiameters` нормализуют вход `number_format(1)` — сравнение со значениями БД в обеих средах.

`slug` = `{brand-slug}-{name}-{width}-{diameter}-{et}-{pcd}-{hub_diameter}` (`4*98` → `4x98`; с 2026-08-21 точки → дефисы: «7.5» → «7-5», «5*114.3» → «5x114-3», «58.6» → «58-6»; коллизия → суффикс `-2`). Генерируется ProductSlugService при создании/обновлении (админка, импорт).

## Парсинг при импорте

- `7.5 x 18 ET45` → width=7.5, diameter=18, et=45
- `5*114.3` → pcd_lugs=5, pcd_diameter=114.3
- `12*1.5` → bolts_qty=12, bolts_size=1.5
- «Литые»/«Штампованные»/«Кованые» → alloy/steel/forged
- Несколько значений через `|` → несколько строк с одним modification_id (совместимость с авто)

## Связи

Полиморф: `stocks`, `images`, `promotions`, `order_items` (`itemable`). Морф-тип: `wheel` → `WheelProduct`.

## Публичный каталог

С 2026-08-21: фасеты (`/api/reference/filter/wheel`) и листинг (`/api/catalog/wheels`) по образцу шин. Фильтры — `WheelProductBuilder::byCatalogFilters(cityId, filters, requireCityPrice)` (единственный источник для фасетов и листинга; brand/country по slug, delivery/price по stocks+catalog_prices морфа 'wheel'). См. [Публичный API каталога](../concepts/public-catalog-filter-api.md).

## See Also

- [Шина (TireProduct)](tire-product.md)
- [Запись catalog_prices](catalog-price.md)
- [Импорт каталога из XLSX](../concepts/xlsx-import-pipeline.md)
- [Каталог: ценообразование](../concepts/catalog-pricing.md)
- [Публичный API каталога](../concepts/public-catalog-filter-api.md)
