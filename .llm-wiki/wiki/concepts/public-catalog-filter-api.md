# Публичный API каталога: фасетный фильтр шин

> Sources: Memory-заметка, 2026-08-14; реализация 2026-08-19 (city_id + инвалидация); Scramble public-api.json, 2026-08
> Raw: [public-catalog-filter-api.md](../../raw/project/public-catalog-filter-api.md); [2026-08-19-tire-filter-city-id.md](../../raw/project/2026-08-19-tire-filter-city-id.md)

## Overview

`GET /api/reference/filter/tire` — фасеты для каталога шин: width, profile, diameter, season, studded, brand, country, delivery, price. Эндпоинт принимает query-фильтры и сужает фасеты до доступных значений (аналог GetTireDimensions): с выбранным `season` в фасете `width` остаются только ширины доступных шин.

## Соглашения контракта

- **Город** — `city_id` (`nullable`, `integer`, `exists:cities,id`); фасеты delivery/price считаются по `catalog_prices` выбранного города. Без `city_id` — город по умолчанию `config/shop.php` (`default_city` = Челябинск).
- **Цена и сроки** — из `catalog_prices` (ADR 0002), без пересчёта на лету.
- **Бакеты доставки** — enum `DeliveryDaysType`: 0 / 1–3 / 4–5 / 6+.
- **`brand` / `country`** — value = slug (не id, не имя).
- **`studded`** — value = 'studded' / 'not_studded'.
- **`width[]`** — множественный query-параметр.

## Кеш и инвалидация

`TireFilterValuesCacheService`: ключ `tire-filter:{cityId|null→'default'}:{md5(filters)}` — город и фильтры в ключе. `catalog_prices` пишется `upsert()` без Eloquent-событий — инвалидация кеша только явным `forget()` в ImportMasterJob / PointImportJob + Observers. `forget()` сбрасывает все варианты фильтров по индексу ключей `tire-filter:index` (драйвер database — теги недоступны). См. [Кеширование](caching.md).

## Слои

Чистая сборка фасетов — `Services/Catalog/Tire/TireFacetAssembler` (Unit-тесты, без БД); БД-обвязка — Action. Services разбиты по доменам (Catalog / Delivery / Catalog\Tire).

## Статус

Реализован 2026-08-14. Следующий шаг (не реализован): публичный листинг каталога с фасетами в ответе (FR-2.2.x; counts из FR убраны — FR-2.2.4 переписан).

## See Also

- [Архитектура приложения](architecture-layers.md)
- [Каталог: ценообразование](catalog-pricing.md)
- [Сроки доставки](delivery-times.md)
- [Кеширование](caching.md)
- [Шина (TireProduct)](../entities/tire-product.md)
