# Волна 1 переноса домена Booking: доменный слой (tireslot → backend)

> Source: код волны 1 переноса (backend, рабочее дерево) + план `.claude/plans/migration-tireslot-to-backend.md`
> Collected: 2026-09-11
> Published: 2026-09-11

## Состав волны 1

- `app/Support/` — чистые функции: `Phone` (канон «7XXXXXXXXXX»), `Money` (формат «1 700 ₽»), `RussianDate`.
- `app/DTOs/Booking/` — `Quote` + `QuoteLine` (результат расчёта цены), `CodeVerification` (статус + строка кода), `ConfirmBookingInput` (код, контакты, слот, параметры, состав, closeSlot).
- `app/Services/Booking/` — `PriceCalculator` (цена по прайс-правилам), `SlotAvailabilityReader` (чтение сетки), `BookingCodeService` (issue/verify/lastIssuedAt), `SmsSender` (контракт) + `LogSmsSender` (dev-драйвер, пишет в лог).
- `app/Actions/Booking/` — `GenerateSlotGrid::execute(): void` (сетка по шаблону недели), `ConfirmBooking::execute(ConfirmBookingInput): Booking` (транзакция + lockForUpdate).
- `app/Preconditions/Booking/` — `EnsureCodeVerifiable` (Invalid → 422, Expired → 409), `EnsureSlotSelectable` (409, быстрая UX-проверка; финальная — в транзакции), `EnsurePricingCombinationExists` (валидация прайсом).
- `app/Jobs/Booking/SendBookingCodeSms` — SMS через очередь (tries 3, backoff 10/60).
- `app/Console/Commands/SlotsGenerate` — `slots:generate`; планировщик `routes/console.php`: каждые 15 минут, withoutOverlapping.
- `config/sms.php` — stub (SMS_STUB/SMS_STUB_CODE), provider (SMS_PROVIDER), кулдаун (SMS_RESEND_COOLDOWN_SECONDS). Биндинг `SmsSender → LogSmsSender` в AppServiceProvider.
- Сидеры: `BookingSlotSeeder` (сетка через GenerateSlotGrid), `DemoBookingSeeder` (неделя вокруг today: клиенты в users, история done/cancelled/no_show, будущие confirmed; расчёт — PriceCalculator).

## Правила, зафиксированные волной 1

- Прайс: точное совпадение (услуга, радиус, тип); нет правил → base_price; комбинации нет → `DomainException` 422.
- Слот: проверка «слот есть, открыт» — дважды: быстро в EnsureSlotSelectable и атомарно (lockForUpdate) в ConfirmBooking — от гонки закрытия.
- Код: одноразовый (used_at в транзакции), TTL `reservation_timeout_min`, свежайшая выдача с тем же хэшем, Invalid → 422 / Expired → 409.
- Бронь с сайта закрывает слот (closeSlot=true) с привязкой booking_id; без closeSlot (админка) — слот открыт.
- Генератор: идемпотентен, прошлое не трогает, открытые пустые строки вне шаблона удаляет, закрытые и с записями — сохраняет.

## Адаптации против tireslot

- `CalculatePriceAction` → **Service `PriceCalculator`** (правило backend: Action не вызывает Action; расчёт нужен и API, и ConfirmBooking — ADR 0001 backend).
- `SlotUnavailableException`/`PricingException` → `DomainException` (409/422).
- Баг tireslot исправлен: гейт стаба был `config('sms.stub')` (массив всегда truthy) → `config('sms.stub.is_active')`; `issue()` теперь возвращает int.
- `VehicleParams`/`BookingSelection`/`CustomerDraft` растворены: параметры — примитивы в сигнатурах, выбор — поля `ConfirmBookingInput`.

## Прогоны

Красный: 29 failed («Class ... not found»), 9 passed (волна 0). Зелёный: 38 passed (111 assertions). Полный сьют: 484 passed, 10 пре-существующих Filament. phpstan/pint — чисто.
