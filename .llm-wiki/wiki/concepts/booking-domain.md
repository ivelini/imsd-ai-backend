# Запись на шиномонтаж: домен Booking

> Sources: Проект, 2026-09-11
> Raw: [2026-09-11-booking-domain-wave0.md](../../raw/project/2026-09-11-booking-domain-wave0.md)

## Overview

Домен Booking перенесён из сервиса tireslot в монолит (ADR 0012): запись на шиномонтаж — слот-сетка, визард выбора времени/услуг, подтверждение SMS-кодом. Публичный UI — SPA через `/api/booking` (волна 2), админка — кластер Filament (волна 3). Волна 0 — фундамент: 10 таблиц с префиксом `booking_`, модели `Models/Booking/*`, enum `Enums/Booking/*`, системные настройки (`settings` KV, `Models/System/Setting` + `SettingKeyEnum`). Клиент — единая `users` (телефон-first, без пароля/почты для записи).

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

## Сидеры

`BookingCatalogSeeder` (реальный прайс-лист: 5 работ × 9 радиусов × 3 типа = 135 правил, 5 допработ без правил по base_price, комплекс «Сезонный шиномонтаж»; услуги вне прайса деактивируются), `BookingScheduleSeeder` (Пн–Сб 9:00–19:00), `BookingSettingsSeeder` — вызываются из DatabaseSeeder безусловно. Слоты и демо-записи — волна 1 (сидеры зависят от Actions генератора сетки и расчёта цены).

## See Also

- [Архитектура приложения: слои и путь запроса](architecture-layers.md)
- [Админ-панель на Filament](admin-panel-filament.md)
- [Каталог: ценообразование](catalog-pricing.md)
