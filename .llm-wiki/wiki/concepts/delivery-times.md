# Сроки доставки: графики отгрузки и расчёт

> Sources: Проект (architecture.md §10), 2026-08-19; ADR 0001, 2026-08-07
> Raw: [architecture.md](../../raw/project/architecture.md); [adr-0001-services.md](../../raw/project/adr-0001-services.md)

## Overview

Мы в Челябинске. Товар идёт: склад (крупный продавец) → мы → точка выдачи/курьер → клиент. Время склада до нас задаётся графиком отгрузки (`delivery_schedules`: день недели, время отсечки, `days_before`/`days_after`), время из Челябинска до города — `city_delivery_times.delivery_days`. Диапазон срока предрассчитан в `catalog_prices` (delivery_min/max), конкретный срок на момент просмотра считается на лету.

## Диапазон в catalog_prices (стабильный, не зависит от момента)

Для пары (stock, city):

```
delivery_min = MIN(days_before) + city_delivery_days
delivery_max = MAX(days_after) + city_delivery_days + max_idle
```

где `max_idle` — самый длинный перерыв между днями отгрузки (сколько ждать, если не успели к отсечке).

Пример: склад ПН(3/5), ВТ(4/5), ПТ(2/4), город с доставкой 2 дня → min = 2+2 = 4, max = 5+3+2 = 10.

## Конкретный срок на момент просмотра (карточка, корзина)

```
Текущий день недели + время
  ├─ отгрузка сегодня и now ≤ cutoff_time → days_before + city_delivery_days
  ├─ отгрузка сегодня, после отсечки       → следующий день отгрузки: days_after + city_delivery_days
  └─ отгрузки сегодня нет                  → ближайший день отгрузки: days_after + city_delivery_days
```

Пример (ПН до 15:00, ПТ до 10:00, доставка города 2 дня): ПН 14:00 → 5 дней; ПН 16:00 → 6 дней; СР → 6 дней.

## Фильтрация каталога по доставке

Фильтр оперирует `delivery_min` (лучший случай), чтобы не отсекать товары, пришедшие быстро при своевременном заказе:

```
до 3 дней  → delivery_min <= 3
3–7 дней   → delivery_min BETWEEN 3 AND 7
от 7 дней  → delivery_min >= 7
```

В публичном API бакеты — enum `DeliveryDaysType` (0 / 1–3 / 4–5 / 6+) — см. [Публичный API каталога](public-catalog-filter-api.md).

## Чистая реализация (ADR 0001)

Алгоритм «ближайший день отгрузки по расписанию» был реализован дважды и разошёлся в деталях. Канон — `DeliveryInfoService::nextShipmentDays()` как чистая функция над коллекцией расписаний; `DeliveryTimeCalculator` (расчёт на лету) делегирует ему. БД-обвязка (предзагрузка расписаний) — снаружи, в Action. Это позволяет Unit-тесты без БД.

## See Also

- [Каталог: ценообразование](catalog-pricing.md)
- [Запись catalog_prices](../entities/catalog-price.md)
- [Город и география](../entities/geo-city.md)
- [Архитектура приложения](architecture-layers.md)
