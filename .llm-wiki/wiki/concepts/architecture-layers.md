# Архитектура приложения: слои и путь запроса

> Sources: Проект, 2026-08-19; Memory-заметка, 2026-08-07
> Raw: [architecture.md](../../raw/project/architecture.md); [db-schema.md](../../raw/project/db-schema.md)

## Overview

Laravel-монолит (PHP 8.5, PostgreSQL 17, Redis 7, Docker) — e-commerce шин и дисков. Код строго разделён на слои: HTTP-обвязка → бизнес-проверки → бизнес-логика → сериализация. Полный путь запроса: `Middleware → FormRequest → Controller → Cache Service? → Preconditions → Action → Response`.

## Полный путь запроса

```
HTTP → Middleware → FormRequest → Controller → Cache Service? → Preconditions → Action::execute(DTO) → Resource → HTTP
```

Зона ответственности слоёв:

| Слой | Отвечает за |
|------|-------------|
| FormRequest | Валидация формата полей (типы, exists, unique). Никаких бизнес-правил |
| Controller | Оркестрация: кеш? → Preconditions → Action → ответ. Без бизнес-логики |
| Cache Service | `remember(fn → Action)` до Action; hit → вернуть, miss → Action → put |
| Preconditions | `ensure*()` → `DomainException` с HTTP-кодом: «можно ли продолжать?» |
| Action | Единственная операция: `execute(DTO): DTO\|void\|array`, без HTTP и кеша |
| Response | Сериализация в JSON |

## Ключевые принципы

- **Action не проверяет данные** — данные уже прошли FormRequest и Preconditions.
- **Action не знает о кеше** — кеш инфраструктурная забота контроллера.
- **Action — `final readonly class`**, не вызывает другие Action (исключение: одна транзакция через инжектированный `Connection`).
- **CQS**: команда не возвращает данные, запрос не меняет состояние.
- **Разделение чтения и записи** в Action.
- **Явное DI** в конструкторе; фасады запрещены (исключения: `DB::raw`/`DB::table` для выразительного SQL, `Storage` для URL, `Log`).
- **≤3 параметра** метода — больше группируется в DTO (`final readonly class`, только данные).
- **Fail fast**: проверки контрактов на входе, не возвращать null.
- **Preconditions**: если проверок ≥3 — цепочка handler-ов через `setNext()`.

## Домены и структура

Директории Models (и зеркально — Actions/, Preconditions/, DTOs/, Http/*, Enums/, Services/): `Admin/`, `Catalog/`, `Cart/`, `Order/`, `Geo/`, `Warehouse/`, `Vehicle/`, `Article/`, `Content/`, `Common/`, `System/`.

Морф-мапа (AppServiceProvider): `tire → TireProduct`, `wheel → WheelProduct`, `article → Article`.

API: `/api/admin` — `auth:sanctum`; `/api` — публичные + клиентские.

## Аутентификация и доступ

- Клиенты: Sanctum-токены (email + password).
- Администраторы: Sanctum-токены (email + password).
- Гости: `device_id` в заголовке (генерируется на фронте — для корзины/избранного/сравнения).
- Rate limit: 60 req/min публичные, 120 — авторизованные.
- API без версионирования (`/api`, не `/api/v1`) — обратная совместимость при изменениях.

## Корзина

Гостевая: не требует регистрации — привязка по `device_id` клиента. Сознательное упрощение первой версии: нет промокодов, бонусов, отзывов.

## Enums (Backed Enum, `app/Enums/`)

| Enum | Значения |
|------|----------|
| `ProductType` | tire, wheel |
| `WheelType` | alloy, steel, forged |
| `SpecType` | oem, replacement, tuning |
| `Season` | winter, summer, all-season |
| `PromotionType` | percent, fixed, gift, special |
| `DiscountType` | — |
| `OrderState` | pending, paid, processing, shipped, delivered, cancelled, refunded |
| `WeekDay` | 0–6 |
| `ImportType` | Tire, Wheel, Point, Model |

## Response — что выбрать

| Тип | Когда |
|-----|-------|
| `JsonResponse` | Action вернул массив, контроллер обернул в `['data' => ...]` |
| `JsonSerializable` DTO | Простой 1:1 mapping без вложенных связей |
| Resource | Сложная трансформация (`whenLoaded`, вложенные структуры) |

Resource — только маппинг полей: никаких вычислений, запросов, вызовов сервисов (вычисленное кладётся в `setRelation()` до передачи).

## ADR по слоям

- [Каталог: ценообразование](catalog-pricing.md) — единый матчинг наценок (ADR 0002), цена из `catalog_prices`.
- Чистые алгоритмы в Services (ADR 0001): БД-обвязка снаружи, каноническая реализация одна — см. [Сроки доставки](delivery-times.md).

## See Also

- [Бизнес-модель](business-model.md)
- [Заказ: жизненный цикл](order-lifecycle.md)
- [Каталог: ценообразование](catalog-pricing.md)
- [Сроки доставки](delivery-times.md)
- [Публичный API каталога](public-catalog-filter-api.md)
- [Кеширование](caching.md)
- [Шина (TireProduct)](../entities/tire-product.md)
- [Диск (WheelProduct)](../entities/wheel-product.md)
