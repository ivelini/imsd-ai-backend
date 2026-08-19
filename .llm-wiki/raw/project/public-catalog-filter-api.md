# Публичный API каталога — фасетный фильтр шин

> Источник: заметка проекта (2026-08-14), состояние на момент реализации фильтра.
> Актуальный контракт: Scramble → `/docs/api` (UI), `documentations/scramble/public-api.json`.

## Реализованный эндпоинт

`GET /api/reference/filter/tire` — фасеты: width, profile, diameter, season, studded, brand, country, delivery, price.

Принимает query-фильтры (`width[]`, `season`, `brand`=slug, `country`=slug, `delivery`=бакет, `price_min`/`price_max`) и сужает фасеты до доступных значений (аналог GetTireDimensions).

## Соглашения контракта

- Город по умолчанию — `config/shop.php` (`default_city` = Челябинск; тонкие настройки магазина — туда).
- Цена и сроки — из `catalog_prices` (ADR 0002).
- Бакеты доставки — enum `DeliveryDaysType`: 0 / 1–3 / 4–5 / 6+.
- `brand` / `country` — value = slug.
- `studded` — value = 'studded' / 'not_studded'.

## Кеш и инвалидация

`TireFilterValuesCacheService`; ключ включает hash фильтров. `catalog_prices` пишется `upsert()` без Eloquent-событий — инвалидация только явным `forget()` в ImportMasterJob/PointImportJob + Observers.

## Слои

Чистая сборка фасетов — `Services/Catalog/Tire/TireFacetAssembler` (unit-тесты); БД-обвязка — Action. Services разбиты по доменам (Catalog / Delivery / Catalog\Tire).

## Следующий шаг (не реализовано)

Публичный листинг каталога с фасетами в ответе (FR-2.2.x; counts из FR убраны — FR-2.2.4 переписан).
