# Публичный API каталога: фасетный фильтр шин

> Sources: Memory-заметка, 2026-08-14; Scramble public-api.json, 2026-08
> Raw: [public-catalog-filter-api.md](../../raw/project/public-catalog-filter-api.md)

## Overview

`GET /api/reference/filter/tire` — фасеты для каталога шин: width, profile, diameter, season, studded, brand, country, delivery, price. Эндпоинт принимает query-фильтры и сужает фасеты до доступных значений (аналог GetTireDimensions): с выбранным `season` в фасете `width` остаются только ширины доступных шин.

## Соглашения контракта

- **Город по умолчанию** — `config/shop.php` (`default_city` = Челябинск); тонкие настройки магазина кладутся туда.
- **Цена и сроки** — из `catalog_prices` (ADR 0002), без пересчёта на лету.
- **Бакеты доставки** — enum `DeliveryDaysType`: 0 / 1–3 / 4–5 / 6+.
- **`brand` / `country`** — value = slug (не id, не имя).
- **`studded`** — value = 'studded' / 'not_studded'.
- **`width[]`** — множественный query-параметр.

## Кеш и инвалидация

`TireFilterValuesCacheService`; ключ включает hash фильтров. `catalog_prices` пишется `upsert()` без Eloquent-событий — инвалидация кеша только явным `forget()` в ImportMasterJob / PointImportJob + Observers. См. [Кеширование](caching.md).

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
