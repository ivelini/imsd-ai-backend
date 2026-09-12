# Запись на шиномонтаж: домен Booking

> Sources: Проект, 2026-09-11; документация волны 4, 2026-09-12
> Raw: [2026-09-11-booking-domain-wave0.md](../../raw/project/2026-09-11-booking-domain-wave0.md); [2026-09-11-booking-domain-wave1.md](../../raw/project/2026-09-11-booking-domain-wave1.md); [2026-09-11-booking-domain-wave2.md](../../raw/project/2026-09-11-booking-domain-wave2.md); [2026-09-11-booking-domain-wave3.md](../../raw/project/2026-09-11-booking-domain-wave3.md); [2026-09-12-booking-docs-wave4.md](../../raw/project/2026-09-12-booking-docs-wave4.md)

## Overview

Домен Booking перенесён из сервиса tireslot в монолит (ADR 0012): запись на шиномонтаж — слот-сетка, визард выбора времени/услуг, подтверждение SMS-кодом. Публичный UI — SPA через `/api/booking` (волна 2, готово), админка — кластер Filament «Шиномонтаж» (волна 3, готово). Волна 0 — фундамент: 10 таблиц с префиксом `booking_`, модели `Models/Booking/*`, enum `Enums/Booking/*`, системные настройки (`settings` KV, `Models/System/Setting` + `SettingKeyEnum`). Волна 1 — доменный слой: Services/Actions/Preconditions/DTO, SMS-job, планировщик. Волна 3 — Money VO (ADR 0013). Клиент — единая `users` (телефон-first, без пароля/почты для записи).

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

## Публичный API (волна 2, `/api/booking`, Scramble Group «Запись на шиномонтаж»)

| Метод | Путь | Ответ |
|---|---|---|
| GET | `/slots?date_from&date_to` | `{days: {"Y-m-d": bool}}` — карта доступности календаря |
| GET | `/slots/{date}` | `{slots: [{hour, is_closed}]}` — сетка дня (прошлое/за горизонтом — пусто; несуществующая дата — 422) |
| GET | `/catalog` | `{services: [{id, name, base_price, has_rules}], complexes: [{id, name, service_ids}]}` |
| GET | `/price?radius&car_type` | `{unit_prices: {id: price}}` по каталогу; потерянная комбинация — 422 |
| POST | `/code` {phone} | 200 `{retry_after}`; кулдаун (60 с) — 429 серверно; невалидный телефон — 422 |
| POST | `/confirm` {phone, code, name, plate?, date, hour, radius, car_type, service_ids[], quantities{}} | 201 BookingResource; used-код (повторный submit) — 200 существующая; 422/409 |

`BookingResource`: снимок (date, start_time, status, source, radius, car_type, plate, total_price) + `user {id,name,phone}` + `items [{id, service{id,name}, price, quantity}]` (вложенные компактные Resource через whenLoaded). `closeSlot` вне API — с сайта всегда true (слот закрывается с привязкой к записи). Количество 1–4 — константы `PriceCalculator::MIN/MAX/DEFAULT_QUANTITY` (единственный источник, FormRequest и сидеры ссылаются). Ошибки: DomainException → `$e->getCode() ?: 409`.

## Деньги (ADR 0013)

Все денежные поля (`base_price`, `price`, `total_price`, `price` строк) — VO `Money` (копейки внутри) через каст `MoneyCast`. Арифметика — только методы Money (`multiply`/`add`, fail fast на отрицательных); формат — `formatted()` («1 700 ₽»); в БД — копейки, в формах панели — рубли (`formatStateUsing`/`dehydrateStateUsing`), на границах API — `toKopecks()` (контракт не менялся). Livewire-гидратация record — интерфейс `Wireable`. Количество 1–4 — константы `PriceCalculator`.

## Админка (волна 3, кластер «Шиномонтаж»)

Кластер Booking с группами «Услуги» / «Записи» / «Настройки»: ресурсы услуг, прайс-правил (уникальность комбинации), комплексов (состав CheckboxList), расписания недели (WeekDay, TimePicker без секунд), слотов (закр/откр, header-action генерации сетки через GenerateSlotGrid), настроек (key/value). Записи — полный CRUD: создание оператором через `CreateAdminBooking` (транзакция, слот lockForUpdate, клиент firstOrCreate по телефону, серверный пересчёт цены, чекбокс закрывает только свободный слот — закрытие не барьер), правка статуса/причины/снимка, табличные действия arrive/complete/noShow/cancel (причина в модале). Форма — одна схема с `visibleOn('create'|'edit')` (в этой версии Filament `operation()` — только сеттер).

## Сидеры

`BookingCatalogSeeder` (реальный прайс-лист: 5 работ × 9 радиусов × 3 типа = 135 правил, 5 допработ без правил по base_price, комплекс «Сезонный шиномонтаж»; услуги вне прайса деактивируются), `BookingScheduleSeeder` (Пн–Сб 9:00–19:00), `BookingSettingsSeeder` — вызываются из DatabaseSeeder безусловно. `BookingSlotSeeder` (сетка через GenerateSlotGrid) и `DemoBookingSeeder` (неделя вокруг today: клиенты, история done/cancelled/no_show, будущие confirmed) — local-only.

## Осталось (не реализовано, из ФТ-карты)

Крон чистки просроченных кодов (ФТ-9); «Моя запись» — отмена клиентом (ФТ-14, верификатор `booking_code_id` готов); календарь дня (ФТ-17); корректировка цены записи оператором (ФТ-19); перенос записи (ФТ-20); снятие привязки закрытия слота при отмене и массовое закрытие дня (ФТ-15/16); любое время начала и подстановка параметров из последней записи (ФТ-18); карточка клиента (ФТ-22). UI-слой SPA — отдельный план в репо frontend. Полная карта — `documentations/tz/booking-functional-requirements.md`.

## See Also

- [Архитектура приложения: слои и путь запроса](architecture-layers.md)
- [Админ-панель на Filament](admin-panel-filament.md)
- [Каталог: ценообразование](catalog-pricing.md)
