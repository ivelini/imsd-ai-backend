# Публичный листинг шин GET /api/catalog/tires

> Источник: реализация 2026-08-19 (план `.claude/plans/rippling-sniffing-cray.md`), состояние кода на момент изменения.

## Контракт

`GET /api/catalog/tires` — пагинированный список шин. Query-параметры = контракт `TireFilterValuesRequest` (width[], profile[], diameter[], season, studded, brand=slug, country=slug, delivery=бакет, price_min/max, city_id) + `page` (дефолт 1), `per_page` (дефолт 48, лимит 10–100), `sort_by=price` (только цена), `sort_dir` (asc|desc, дефолт desc). Ответ: `{data: [шины], meta: {current_page, last_page, per_page, total}}`.

Элемент шины: id, name, brand {id, name} (вложенный Resource, whenLoaded), width, profile, diameter, season (русское название через аксессор season_label), is_studded, price, delivery_min, delivery_max (агрегаты города), images [{id, url}] (список, whenLoaded).

## Отбор и агрегация

- Условия: `is_published=true`, есть stock `quantity>0`, **есть цена города** (`catalog_prices` с price NOT NULL) — товары без цены города в листинг не попадают (скоуп `byPriceRange` всегда, флаг `requireCityPrice`).
- Цена/сроки по стокам товара в городе: `price=MIN(price)`, `delivery_min=MIN`, `delivery_max=MAX` — одним батч-запросом по стокам страницы (join stocks→catalog_prices, groupBy stockable_id).
- Сортировка по цене — скалярный коррелированный подзапрос `CAST(MIN(cp.price) AS NUMERIC)` по стокам города; без sort_by — `id desc` (без join).
- Общая фильтрация — `TireProductBuilder::byCatalogFilters(cityId, filters, requireCityPrice)` (единый источник для фасетов и листинга).

## Кеш (ADR 0004)

`TireListCacheService`: ключ `tire-list:v2:{city|default}:{md5(фильтры+page+perPage+sort)}` + индекс ключей для forget(). В кеш — только чистый массив: `json_decode(Resource::collection(...)->toJson(), true)` — `resolve()` не рекурсивный, вложенные Resource ломались при unserialize (`__PHP_Incomplete_Class`). Версия v2 в ключе инвалидирует старый битый кеш. Инвалидация — в тех же точках, что у TireFilterValuesCacheService (6 обсерверов + ImportMasterJob + PointImportJob).
