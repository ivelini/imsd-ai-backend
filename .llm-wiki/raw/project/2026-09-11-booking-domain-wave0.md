# Волна 0 переноса домена Booking (tireslot → backend)

> Source: код волны 0 переноса (backend, рабочее дерево) + план `.claude/plans/migration-tireslot-to-backend.md`
> Collected: 2026-09-11
> Published: 2026-09-11

## Решения плана переноса (согласованы с владельцем)

1. Публичный сайт записи — API `/api/booking` + страницы в SPA (репо frontend). Livewire-визард tireslot не переносится, `.template/`-мокап упраздняется.
2. Filament-админка Booking-кластера — в этом переносе (в tireslot её не было).
3. Репозиторий tireslot упраздняется после паритета по тестам.
4. Единый клиент — таблица `users` backend: `phone` (nullable unique), `email` → nullable, `password` → nullable. `bookings.user_id` вместо `customers`.
5. Префикс `booking_` для всех таблиц домена. Маппинг: `services`→`booking_services`, `price_rules`→`booking_price_rules`, `complex_services`/`complex_service_item`→`booking_complex_services`/`booking_complex_service_items`, `schedule_templates`→`booking_schedule_templates`, `slots`→`booking_slots`, pivot-снапшот `booking_services`→`booking_items` (модель `BookingItem`). `settings` — без префикса, системный KV.
6. Структура: модели `Models/Booking/*` плоско (Booking, BookingCode, BookingItem, BookingService, ComplexService, PriceRule, Slot, ScheduleTemplate), `Setting` → `Models/System/Setting`; enum `app/Enums/Booking/*` (BookingSource, BookingStatus, CodeStatus — Backed, CarType, ServiceCategory, WheelRadius), `SettingKeyEnum` → `app/Enums/System/`.
7. `bookings.operator_id` — FK на `admins` (оператор = сотрудник панели).
8. Волна 0 — только справочники; SlotSeeder/DemoBookingSeeder зависят от Actions и подключаются в волне 1.

## Состав волны 0

- Миграции: alter `users` + 10 таблиц (см. маппинг выше); `bookings` — снимок `radius/car_type/plate/total_price`, FK на `users`, `booking_slots`, `booking_codes`, `admins` (operator_id).
- Модели: 9 новых + `User` (phone, relation `bookings`).
- Enum: 6 в `Enums/Booking/` + `Enums/System/SettingKeyEnum` (ключи `reservation_timeout_min`=15 мин, `min_lead_time_h`=1 ч, `booking_horizon_days`=30 дней — дефолты; в settings также `cancel_free_before_h`, `shop_address`, `shop_phone`).
- Фабрики: `BookingFactory`, `BookingServiceFactory`, состояние `bookingClient()` у `UserFactory` (телефон вместо почты).
- Сидеры: `BookingCatalogSeeder` (реальный прайс: 5 работ × 9 радиусов × 3 типа = 135 правил, 5 допработ без правил, комплекс «Сезонный шиномонтаж» = 4 работы), `BookingScheduleSeeder` (Пн–Сб 9:00–19:00), `BookingSettingsSeeder`. Зарегистрированы в DatabaseSeeder безусловно (прод).
- Тесты: `tests/Feature/Booking/{SettingTest, BookingTest, DatabaseSeederTest}` — 9 тестов.

## Бизнес-правила, зафиксированные миграциями

- `booking_codes`: код одноразовый (`used_at`), TTL без поля = `created_at + reservation_timeout_min`; plaintext не хранится (`code_hash`).
- `booking_items`: снимок цены за единицу + `quantity` 1–4; `booking_price_rules` — уникальный куб (услуга, радиус, тип).
- `booking_slots`: уникальная пара (date, hour); закрытие привязывается к записи (`booking_id`, FK добавляется после bookings — цикл ссылок).
- `booking_complex_service_items`: услугу деактивируют, не удаляют (restrictOnDelete) — история записей сохраняется.
