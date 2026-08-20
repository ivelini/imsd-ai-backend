# Публичный API каталога: фасетный фильтр и листинг шин

> Sources: Memory-заметка, 2026-08-14; реализация 2026-08-19 (city_id + инвалидация); реализация 2026-08-19 (листинг, ADR 0004); реализация 2026-08-20 (справочник городов); реализация 2026-08-20 (model в листинге); Scramble public-api.json, 2026-08
> Raw: [public-catalog-filter-api.md](../../raw/project/public-catalog-filter-api.md); [2026-08-19-tire-filter-city-id.md](../../raw/project/2026-08-19-tire-filter-city-id.md); [2026-08-19-public-tire-list.md](../../raw/project/2026-08-19-public-tire-list.md); [2026-08-20-city-reference.md](../../raw/project/2026-08-20-city-reference.md); [2026-08-20-tire-list-model.md](../../raw/project/2026-08-20-tire-list-model.md)

## Overview

Три эндпоинта публичного каталога шин на одном контракте фильтров:

- `GET /api/reference/city` — справочник всех городов для дропдаунов: `{label: name, value: id, slug}` (см. раздел «Справочник городов»).
- `GET /api/reference/filter/tire` — фасеты: width, profile, diameter, season, studded, brand, country, delivery, price. Принимает query-фильтры и сужает фасеты до доступных значений (аналог GetTireDimensions): с выбранным `season` в фасете `width` остаются только ширины доступных шин.
- `GET /api/catalog/tires` — пагинированный листинг (см. раздел «Листинг»).

## Справочник городов (GET /api/reference/city)

Без query-параметров. Ответ: `{data: list<{label: string, value: int, slug: string|null}>}` — label = name, value = id, slug для URL карточек. Сортировка — по имени города.

- Реализация: `GetCityReference` (Action, `City::orderBy('name')`) → `CityReferenceResource::collection()` — формат «фасетных» элементов справочников, как brand/country в фильтре.
- Кеш не используется: один select без вычислений (правило «не добавлять кеш молча»).

## Соглашения контракта (общие)

- **Город** — `city_id` (`nullable`, `integer`, `exists:cities,id`); фасеты delivery/price считаются по `catalog_prices` выбранного города. Без `city_id` — город по умолчанию `config/shop.php` (`default_city` = Челябинск).
- **Цена и сроки** — из `catalog_prices` (ADR 0002), без пересчёта на лету.
- **Бакеты доставки** — enum `DeliveryDaysType`: 0 / 1–3 / 4–5 / 6+.
- **`brand` / `country`** — value = slug (не id, не имя).
- **`studded`** — value = 'studded' / 'not_studded'.
- **`width[]`** — множественный query-параметр.
- **Фильтрация** — единый скоуп `TireProductBuilder::byCatalogFilters(cityId, filters, requireCityPrice)`: общий источник для фасетов и листинга (размеры, сезон, шипы, brand/country по slug, delivery-бакет, price-диапазон по catalog_prices).

## Листинг (GET /api/catalog/tires)

Query-параметры = контракт фильтров + `page` (дефолт 1), `per_page` (дефолт 48, лимит 10–100), `sort_by=price` (только цена), `sort_dir` (asc|desc, дефолт desc). Ответ: `{data: [шины], meta: {current_page, last_page, per_page, total}}` (без links).

- **Отбор:** `is_published=true`, есть stock `quantity>0`, **есть цена города** — `byCatalogFilters(..., requireCityPrice: true)` превращает пустой price-диапазон в «есть цена города», товары без цены в листинг не попадают.
- **Цена/сроки элемента:** по стокам товара в городе `price=MIN(price)`, `delivery_min=MIN`, `delivery_max=MAX` — одним батч-запросом по стокам страницы (join stocks→catalog_prices, groupBy stockable_id), transient-атрибуты на моделях.
- **Сортировка по цене** — скалярный коррелированный подзапрос `CAST(MIN(cp.price) AS NUMERIC)` по стокам города (CAST обязателен для sqlite); без `sort_by` — `id desc` без join.
- **Структура элемента:** id, name, slug, brand {id, name} (вложенный Resource, whenLoaded), model {id, name, slug} (вложенный Resource `ProductModelReferenceResource`, whenLoaded; null, если `model_id` не задан), width, profile, diameter, season (русское название через аксессор `season_label`, не объект), is_studded, price, delivery_min, delivery_max, images [{id, url}] (список, whenLoaded). Вложенные сущности — компактными Resource-классами (правило «связанные сущности — через relation и вложенные Resource»).

## Кеш и инвалидация

- Фасеты: `TireFilterValuesCacheService`, ключ `tire-filter:{cityId|null→'default'}:{md5(filters)}`.
- Листинг: `TireListCacheService`, ключ `tire-list:v3:{city|default}:{md5(фильтры+page+perPage+sort)}`. **В кеш — только чистый массив** через JSON-roundtrip `json_decode(Resource::collection(...)->toJson(), true)`: `resolve()` не рекурсивный, вложенные Resource ломались при unserialize (`__PHP_Incomplete_Class`), версия в ключе инвалидирует несовместимый кеш при смене payload (v2 — битый кеш Resource-объектов; v3 — добавлено поле model, ADR 0004).
- Оба сервиса: `forget()` сбрасывает все варианты по индексу ключей (драйвер database — теги недоступны). `catalog_prices` пишется `upsert()` без Eloquent-событий — инвалидация только явным `forget()` в ImportMasterJob / PointImportJob + Observers (6 обсерверов). См. [Кеширование](caching.md).

## Слои

Чистая сборка фасетов — `Services/Catalog/Tire/TireFacetAssembler` (Unit-тесты, без БД); БД-обвязка — Action. Services разбиты по доменам (Catalog / Delivery / Catalog\Tire).

## Статус

Фасеты — 2026-08-14; листинг — 2026-08-19. Следующий шаг (не реализован): фасеты в ответе листинга (FR-2.2.9; counts из FR убраны — FR-2.2.4 переписан).

## See Also

- [Архитектура приложения](architecture-layers.md)
- [Каталог: ценообразование](catalog-pricing.md)
- [Сроки доставки](delivery-times.md)
- [Кеширование](caching.md)
- [Шина (TireProduct)](../entities/tire-product.md)
