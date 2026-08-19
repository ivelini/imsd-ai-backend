# Заказ: жизненный цикл

> Sources: Проект (architecture.md §8), 2026-08-19
> Raw: [architecture.md](../../raw/project/architecture.md)

## Overview

Статусная машина заказа: 5 основных статусов + отмена и возврат. Статусы меняют либо система (автоматически), либо менеджер. Enum `OrderState` — `app/Enums/`.

## Статусы и переходы

```
pending ──→ paid ──→ processing ──→ shipped ──→ delivered
   │         │         │
   └─────────┴─────────┴──→ cancelled (клиент или менеджер, с причиной)
                          shipped ──→ refunded (менеджер, возврат средств)
                          delivered ──→ refunded
```

| Статус | Кто устанавливает | Действие |
|--------|-------------------|----------|
| `pending` | Система | Клиент оформил заказ |
| `paid` | Система | Платёжный шлюз подтвердил оплату |
| `processing` | Менеджер | Подтвердил заказ |
| `shipped` | Менеджер | Передал ТК + трек-номер |
| `delivered` | Менеджер | Клиент получил |
| `cancelled` | Клиент / Менеджер | Отмена (с причиной) |
| `refunded` | Менеджер | Возврат средств |

Границы: отмена — из статусов Новый / Оплачен / В обработке; возврат — из В доставке / Доставлен.

## Хранение

`orders` (user_id, status, total, delivery_type, payment_method, contact_info) + `order_items` (полиморфный `itemable`: tire/wheel).

## See Also

- [Бизнес-модель](business-model.md)
- [Архитектура приложения](architecture-layers.md)
- [Шина (TireProduct)](../entities/tire-product.md)
