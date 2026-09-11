# Волна 2 переноса домена Booking: публичный API /api/booking (tireslot → backend)

> Source: код волны 2 переноса (backend, рабочее дерево) + план `.claude/plans/migration-tireslot-to-backend.md`
> Collected: 2026-09-11
> Published: 2026-09-11

## Состав волны 2

- Маршруты (`routes/api.php`, публичные, Scramble Group «Запись на шиномонтаж»): `GET /booking/slots` (days-карта), `GET /booking/slots/{date}` (сетка дня, where-регэксп формата), `GET /booking/catalog`, `GET /booking/price`, `POST /booking/code`, `POST /booking/confirm`.
- FormRequests: `GetSlotDaysRequest` (date_format:Y-m-d), `GetUnitPricesRequest` (radius — WheelRadius::cases, car_type — bookable), `IssueCodeRequest`, `ConfirmBookingRequest` (code size:4, date_format, hour 0–23, quantities — границы из PriceCalculator::MIN/MAX_QUANTITY).
- Read-Actions: `GetSlotDays` (обёртка SlotAvailabilityReader), `GetDaySlots`, `GetBookingCatalog` (услуги + has_rules через withCount, комплексы с service_ids), `GetUnitPrices` (PriceCalculator по каталогу с количеством 1).
- Resources: `BookingResource` (снимок, user, items через whenLoaded), `BookingItemResource`, `BookingServiceResource` (компакт), `BookingClientResource` (компакт).
- `EnsureCodeCooldownElapsed` — серверный кулдаун SMS 429 (в tireslot кулдаун был только в UI).
- Контроллеры: `GetSlotDaysController`, `GetDaySlotsController` (422 на несуществующую дату), `GetBookingCatalogController`, `GetUnitPricesController`, `IssueBookingCodeController` (200 {retry_after}), `ConfirmBookingController`.

## Контракт подтверждения (ConfirmBookingController)

Цепочка: EnsureCodeVerifiable (invalid → 422, expired → 409) → **Used: повторный submit возвращает существующую запись 200** (НФ-1 tireslot) → EnsureSlotSelectable (409) → EnsurePricingCombinationExists (422) → ConfirmBooking (транзакция) → 201 BookingResource. `closeSlot` из API не выставляется — с сайта всегда true (ФТ-16 tireslot); чекбокс появится в админке (волна 3).

## Исправления по итогам прогонов

- Глобальный handler DomainException хардкодил 409 → `$e->getCode() ?: 409` (bootstrap/app.php): 422/429 заработали, существующие броски с 409 не изменились.
- `BookingCodeService::lastIssuedAt()` возвращал mutable Carbon → TypeError → `CarbonImmutable::instance()`.
- Правило «количество 1–4» централизовано: константы `PriceCalculator::MIN/MAX/DEFAULT_QUANTITY` (третья копия — FormRequest и сидер ссылаются на них).

## Прогоны

Красный: 16 failed (404). Зелёный: 52 passed (156 assertions). Полный сьют: 500 passed, 10 пре-существующих Filament. phpstan/pint — чисто. Scramble: 6 эндпоинтов в public-api.json.
