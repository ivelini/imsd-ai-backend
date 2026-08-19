# Запись catalog_prices: полная цена города

> Sources: ADR 0002, 2026-08-13; Проект (db-schema.md, architecture.md §9), 2026-08-19
> Raw: [adr-0002-catalog-prices.md](../../raw/project/adr-0002-catalog-prices.md); [db-schema.md](../../raw/project/db-schema.md)

## Overview

`catalog_prices` — предрассчитанная строка «остаток × город»: финальная цена и стабильный диапазон сроков доставки. Единственный источник полной цены для фильтрации, сортировки, карточки товара. `UNIQUE (stock_id, city_id)` — один остаток × один город = одна строка.

## Поля

| Поле | Описание |
|------|----------|
| `stock_id` | FK → stocks (привязка к товару и складу) |
| `city_id` | FK → cities |
| `price` | `round(базовая цена города − скидка, -2)` — наценка склада + наценка города + акция + округление до 100 ₽ |
| `delivery_min` | Минимальный срок (рабочих дней): `MIN(days_before) + city_delivery_days` |
| `delivery_max` | Максимальный срок: `MAX(days_after) + max_idle + city_delivery_days` |

## Использование

- **Каталог:** `WHERE city_id = ? AND price BETWEEN ? AND ?` — один индекс, без расчётов на лету.
- **Карточка товара:** один lookup по `catalog_prices` для города пользователя.
- **Фильтр доставки:** по `delivery_min` (бакеты `DeliveryDaysType`).

## Пересчёт

Таблица пишется `upsert()` **без Eloquent-событий** — Observer-инвалидация кеша не срабатывает, только явный `forget()` из импорт-джобов (ImportMasterJob / PointImportJob). Триггеры пересчёта и полный путь формирования цены — в [Каталог: ценообразование](../concepts/catalog-pricing.md).

## See Also

- [Каталог: ценообразование](../concepts/catalog-pricing.md)
- [Сроки доставки](../concepts/delivery-times.md)
- [Шина (TireProduct)](tire-product.md)
- [Кеширование](../concepts/caching.md)
