# Запись на шиномонтаж: домен Booking

> Sources: Проект, 2026-09-11
> Raw: [2026-09-11-booking-domain-wave0.md](../../raw/project/2026-09-11-booking-domain-wave0.md); [2026-09-11-booking-domain-wave1.md](../../raw/project/2026-09-11-booking-domain-wave1.md)

## Overview

Домен Booking перенесён из сервиса tireslot в монолит (ADR 0012): запись на шиномонтаж — слот-сетка, визард выбора времени/услуг, подтверждение SMS-кодом. Публичный UI — SPA через `/api/booking` (волна 2), админка — кластер Filament (волна 3). Волна 0 — фундамент: 10 таблиц с префиксом `booking_`, модели `Models/Booking/*`, enum `Enums/Booking/*`, системные настройки (`settings` KV, `Models/System/Setting` + `SettingKeyEnum`). Волна 1 — доменный слой: Services/Actions/Preconditions/DTO, SMS-job, планировщик. Клиент — единая `users` (телефон-first, без пароля/почты для записи).

## Таблицы

| Таблица | Назначение |
|---|---|
| `booking_services` | Каталог услуг (name, category, is_active, base_price — «от N ₽») |
| `booking_price_rules` | Куб цен (услуга × радиус × тип) с UNIQUE; подбор — точным совпадением, без fallback |
| `booking_complex_services` + `_items` | Комплексы без своей цены (клик отмечает состав ×4); в запись не попадают |
| `booking_schedule_templates` | Неделя: weekday 0 (пн)–6 (вс), open/close; оба null → выходной |
| `booking_slots` | Часовые окна (date+hour, UNIQUE); is_closed + привязка к записи (booking_id) |
| `booking_codes` | SMS-код: code_hash (не plaintext), used_at — одноразовость, TTL = created_at + reservation_timeout_min |
| `bookings` | Запись: снимок radius/car_type/plate/total_price, user_id, slot_id, booking_code_id, operator_id → admins, статус |
| `booking_items` | Состав записи: снимок цены за единицу + quantity 1–4 (услугу деактивируют, не удаляют — история) |
| `settings` | Системный KV монолита (System): ключи и дефолты — `SettingKeyEnum`, чтение — `Setting::get()` |

## Клиент

Единый клиент монолита — `users` (ADR 0012): `phone` nullable unique, `email`/`password` nullable. При записи клиент ищется/создаётся по каноничному телефону (`firstOrCreate`, канон «7XXXXXXXXXX» — `Support\Phone`). Заказы (`orders.user_id`) и записи (`bookings.user_id`) ссылаются на одну сущность.

## Enum и правила

- `BookingStatus`: confirmed → arrived/done/no_show/cancelled (статусная машина записи).
- `BookingSource`: site | admin (канал создания).
- `CarType::bookable()` — passenger/crossover/suv; truck вне прайса (запись по звонку).
- `WheelRadius` R13–R21 (R22 в прайсе нет — не предлагается).
- `CodeStatus`: valid/used/expired/invalid (результат проверки кода).
- `SettingKeyEnum`: дефолты 15 мин (код), 1 ч (минимальное время до начала), 30 дней (горизонт); в settings также `cancel_free_before_h`, `shop_address`, `shop_phone`.

## Доменный слой (волна 1)

- **Прайс** — `Services/Booking/PriceCalculator::calculate(services, radius, carType, quantities): Quote` (строки + итог, копейки). Точное совпадение (услуга × радиус × тип); нет правил → `base_price`; комбинации нет → `DomainException` 422 (потерянное правило — баг данных, не fallback). Количество 1–4 вне границ — `InvalidArgumentException`.
- **Доступность** — `SlotAvailabilityReader`: карта дат с доступными слотами, слоты дня от первого часа (`now + min_lead_time_h`, округление вверх), горизонт `booking_horizon_days`, закрытые помечаются busy.
- **Коды** — `BookingCodeService`: `issue()` (plain-код для SMS, в БД — sha256 с `app.key`), `verify()` → `CodeVerification` (valid/used/expired/invalid; свежайшая выдача с тем же хэшем), TTL = `reservation_timeout_min`. Доставка — `Jobs/Booking/SendBookingCodeSms` (очередь, tries 3) через контракт `SmsSender` → `LogSmsSender` (dev-драйвер, биндинг в AppServiceProvider; стаб-режим — `config('sms.stub.is_active')`).
- **Создание записи** — `Actions/Booking/ConfirmBooking::execute(ConfirmBookingInput): Booking`: транзакция (`Connection`), слот `lockForUpdate` (финальная антигонка, `DomainException` 409 — предпроверка `EnsureSlotSelectable` быстрее, но TOCTOU-незащищена), клиент `firstOrCreate` по phone, снимок параметров и цены (пересчёт `PriceCalculator`), `closeSlot` → слот закрыт и привязан к записи, код помечен `used_at` атомарно.
- **Preconditions** — `EnsureCodeVerifiable` (invalid → 422, expired → 409; used пропускается — контроллер вернёт существующую запись), `EnsureSlotSelectable` (409), `EnsurePricingCombinationExists` (валидация прайсом).
- **Сетка** — `Actions/Booking/GenerateSlotGrid::execute()`: идемпотентно по шаблону недели на горизонт; прошлое не трогает; открытые пустые строки вне шаблона удаляет, закрытые и с записями сохраняет. Команда `slots:generate` + планировщик каждые 15 минут (withoutOverlapping).
- **Поддержка** — `Support/` (чистые функции): `Phone` (канон «7XXXXXXXXXX»), `Money`, `RussianDate`.

## Сидеры

`BookingCatalogSeeder` (реальный прайс-лист: 5 работ × 9 радиусов × 3 типа = 135 правил, 5 допработ без правил по base_price, комплекс «Сезонный шиномонтаж»; услуги вне прайса деактивируются), `BookingScheduleSeeder` (Пн–Сб 9:00–19:00), `BookingSettingsSeeder` — вызываются из DatabaseSeeder безусловно. `BookingSlotSeeder` (сетка через GenerateSlotGrid) и `DemoBookingSeeder` (неделя вокруг today: клиенты, история done/cancelled/no_show, будущие confirmed) — local-only.

## See Also

- [Архитектура приложения: слои и путь запроса](architecture-layers.md)
- [Админ-панель на Filament](admin-panel-filament.md)
- [Каталог: ценообразование](catalog-pricing.md)
