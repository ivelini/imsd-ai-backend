# Город и география: регионы, города, точки выдачи

> Sources: Проект (db-schema.md), 2026-08-19; реализация 2026-08-20 (slug, публичный справочник городов)
> Raw: [db-schema.md](../../raw/project/db-schema.md); [architecture.md](../../raw/project/architecture.md); [2026-08-20-city-reference.md](../../raw/project/2026-08-20-city-reference.md)

## Overview

Гео-домен — четыре уровня: регионы → города → точки выдачи + правила наценки по городам и сроки доставки из Челябинска. Склад (`warehouses`) и точка выдачи (`delivery_points`) — **разные сущности**: склад — у кого купили товар, точка выдачи — где клиент его забирает.

## Сущности

| Таблица | Поля | Назначение |
|---------|------|------------|
| `regions` | `code` (уникальный), `name` | Регион (из XLSX точек выдачи) |
| `cities` | `region_id`, `name`, `slug`, `sort` | Города; `default_city` в `config/shop.php` (Челябинск) |

## Публичный вывод

`GET /api/reference/city` — справочник всех городов для дропдаунов: `{label: name, value: id, slug}`, сортировка по имени, без кеша. Slug города нужен для URL карточек — см. [Публичный API каталога](../concepts/public-catalog-filter-api.md).
| `delivery_points` | `city_id`, `address`, `phone`, `email`, `work_hours`, `info`, `pickup_from_truck` | Точки выдачи |
| `city_price_rules` | `city_id`, `price_from`, `price_to`, `markup` | Наценка города: фиксированные ₽ по диапазону нашей цены |
| `city_delivery_times` | `city_id`, `delivery_days`, `priority` | Срок из Челябинска до города (рабочих дней) |

## Роль в цене и доставке

- **Наличие товара в городе** = в городе есть хотя бы одна точка выдачи + товар есть на складе, с которым мы работаем.
- `city_price_rules` участвует в полной цене города: `MarkupRuleMatcher` + предрасчёт в `catalog_prices` — см. [Каталог: ценообразование](../concepts/catalog-pricing.md).
- `city_delivery_times` — в `delivery_min/max` строки `catalog_prices` — см. [Сроки доставки](../concepts/delivery-times.md).
- Если в городе клиента нет точки выдачи — доставка курьером.

## Импорт

Импорт точек (points.xlsx) создаёт регионы, города, точки выдачи, `city_price_rules` (колонки диапазонов `0-5000 … 15001-100000`) и `city_delivery_times`; при импорте точек — полный пересчёт `catalog_prices` (география меняется целиком).

## See Also

- [Каталог: ценообразование](../concepts/catalog-pricing.md)
- [Сроки доставки](../concepts/delivery-times.md)
- [Импорт каталога из XLSX](../concepts/xlsx-import-pipeline.md)
- [Запись catalog_prices](catalog-price.md)
