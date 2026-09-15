# Запись на шиномонтаж: домен Booking

> Sources: Проект, 2026-09-11; документация волны 4, 2026-09-12; фильтры листинга слотов 2026-09-12; карточка слота и payload Money 2026-09-12; правка записи оператором (состав, цена по прайсу, итог) 2026-09-15; время внутри часа, проверка совпадения и статус слота 2026-09-15; ФИО клиента по частям 2026-09-15
> Raw: [2026-09-15-create-form-unified-with-edit.md](../../raw/project/2026-09-15-create-form-unified-with-edit.md); [2026-09-15-users-full-name-parts.md](../../raw/project/2026-09-15-users-full-name-parts.md); [2026-09-15-booking-time-and-slot-status.md](../../raw/project/2026-09-15-booking-time-and-slot-status.md); [2026-09-15-booking-edit-items-and-total.md](../../raw/project/2026-09-15-booking-edit-items-and-total.md); [2026-09-12-slot-edit-page.md](../../raw/project/2026-09-12-slot-edit-page.md); [2026-09-12-slots-panel-filters.md](../../raw/project/2026-09-12-slots-panel-filters.md); [2026-09-11-booking-domain-wave0.md](../../raw/project/2026-09-11-booking-domain-wave0.md); [2026-09-11-booking-domain-wave1.md](../../raw/project/2026-09-11-booking-domain-wave1.md); [2026-09-11-booking-domain-wave2.md](../../raw/project/2026-09-11-booking-domain-wave2.md); [2026-09-11-booking-domain-wave3.md](../../raw/project/2026-09-11-booking-domain-wave3.md); [2026-09-12-booking-docs-wave4.md](../../raw/project/2026-09-12-booking-docs-wave4.md)

## Overview

Домен Booking перенесён из сервиса tireslot в монолит (ADR 0012): запись на шиномонтаж — слот-сетка, визард выбора времени/услуг, подтверждение SMS-кодом. Публичный UI — SPA через `/api/booking` (волна 2, готово), админка — кластер Filament «Шиномонтаж» (волна 3, готово). Волна 0 — фундамент: 10 таблиц с префиксом `booking_`, модели `Models/Booking/*`, enum `Enums/Booking/*`, системные настройки (`settings` KV, `Models/System/Setting` + `SettingKeyEnum`). Волна 1 — доменный слой: Services/Actions/Preconditions/DTO, SMS-job, планировщик. Волна 3 — Money VO (ADR 0013). Клиент — единая `users` (телефон-first, без пароля/почты для записи).

## Таблицы

| Таблица | Назначение |
|---|---|
| `booking_services` | Каталог услуг (name, category, is_active, base_price — «от N ₽») |
| `booking_price_rules` | Куб цен (услуга × радиус × тип) с UNIQUE; подбор — точным совпадением, без fallback |
| `booking_complex_services` + `_items` | Комплексы без своей цены (клик отмечает состав ×4); в запись не попадают |
| `booking_schedule_templates` | Неделя: weekday 0 (пн)–6 (вс), open/close; оба null → выходной |
| `booking_slots` | Часовые окна (date+hour, UNIQUE); is_closed + привязка к записи (booking_id): час занимает только запись ровно на его начало, внутри часа записи час не закрывают |
| `booking_codes` | SMS-код: code_hash (не plaintext), used_at — одноразовость, TTL = created_at + reservation_timeout_min |
| `bookings` | Запись: снимок radius/car_type/plate/total_price, user_id, slot_id, booking_code_id, operator_id → admins, статус; `start_time` — точное время внутри часа слота (сайт всегда HH:00, оператор — любая минута) |
| `booking_items` | Состав записи: снимок цены за единицу + quantity 1–4 (услугу деактивируют, не удаляют — история) |
| `settings` | Системный KV монолита (System): ключи и дефолты — `SettingKeyEnum`, чтение — `Setting::get()` |

## Клиент

Единый клиент монолита — `users` (ADR 0012): `phone` nullable unique, `email`/`password` nullable. При записи клиент ищется/создаётся по каноничному телефону (`firstOrCreate`, канон «7XXXXXXXXXX» — `Support\Phone`). Заказы (`orders.user_id`) и записи (`bookings.user_id`) ссылаются на одну сущность.

**ФИО хранится частями** (2026-09-15): `name` — имя, `surname` — фамилия, `patronymic` — отчество. Для показа есть склейка `User::full_name` («Фамилия Имя Отчество», пустые части опускаются) — её читают заголовок записи, карточка слота, листинги панели и ответ API. Фамилию и отчество сайт не спрашивает (там имя одной строкой в `name`) — колонки nullable, обязательность держит форма панели (имя и фамилия). ФИО правится из карточки записи и обновляет **карточку клиента**: один телефон — один клиент, поэтому правка видна и в остальных его записях; на создании карточка следует за вводом оператора, но пустое отчество прежнее значение не затирает.

## Enum и правила

- `BookingStatus`: confirmed → done/no_show/cancelled (статусная машина записи); статус «Клиент приехал» (`arrived`) убран 2026-09-15.
- `BookingSource`: site | admin (канал создания).
- `CarType::bookable()` — passenger/crossover/suv; truck вне прайса (запись по звонку).
- `WheelRadius` R13–R21 (R22 в прайсе нет — не предлагается).
- `CodeStatus`: valid/used/expired/invalid (результат проверки кода).
- `SettingKeyEnum`: дефолты 15 мин (код), 1 ч (минимальное время до начала), 30 дней (горизонт); в settings также `cancel_free_before_h`, `shop_address`, `shop_phone`.

## Доменный слой (волна 1)

- **Прайс** — `Services/Booking/PriceCalculator::calculate(services, radius, carType, quantities): Quote` (строки + итог, копейки). Точное совпадение (услуга × радиус × тип); нет правил → `base_price`; комбинации нет → `DomainException` 422 (потерянное правило — баг данных, не fallback). Количество 1–4 вне границ — `InvalidArgumentException`.
- **Доступность** — `SlotAvailabilityReader`: карта дат с доступными слотами, слоты дня от первого часа (`now + min_lead_time_h`, округление вверх), горизонт `booking_horizon_days`, закрытые помечаются busy.
- **Коды** — `BookingCodeService`: `issue()` (plain-код для SMS, в БД — sha256 с `app.key`), `verify()` → `CodeVerification` (valid/used/expired/invalid; свежайшая выдача с тем же хэшем), TTL = `reservation_timeout_min`. Доставка — `Jobs/Booking/SendBookingCodeSms` (очередь, tries 3) через контракт `SmsSender` → `LogSmsSender` (dev-драйвер, биндинг в AppServiceProvider; стаб-режим — `config('sms.stub.is_active')`).
- **Создание записи** — `Actions/Booking/ConfirmBooking::execute(ConfirmBookingInput): Booking`: транзакция (`Connection`), слот `lockForUpdate` (финальная антигонка, `DomainException` 409 — предпроверка `EnsureSlotSelectable` быстрее, но TOCTOU-незащищена), проверка времени `EnsureSlotTimeIsFree` (час мог быть переоткрыт вручную), клиент `firstOrCreate` по phone, снимок параметров и цены (пересчёт `PriceCalculator`), `closeSlot` → слот закрыт и привязан к записи, код помечен `used_at` атомарно.
- **Preconditions** — `EnsureCodeVerifiable` (invalid → 422, expired → 409; used пропускается — контроллер вернёт существующую запись), `EnsureSlotSelectable` (409), `EnsurePricingCombinationExists` (валидация прайсом), `EnsureSlotTimeIsFree` (409 — время занято другой записью; отменённые не держат, правимая запись себе не мешает).
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
| POST | `/confirm` {phone, code, name, plate?, date, hour, radius, car_type, service_ids[], quantities{}} | 201 BookingResource; used-код (повторный submit) — 200 существующая; 422/409 (в т.ч. 409 — время занято записью оператора) |

`BookingResource`: снимок (date, start_time, status, source, radius, car_type, plate, total_price) + `user {id,name,phone}` (name — склейка ФИО, контракт не менялся) + `items [{id, service{id,name}, price, quantity}]` (вложенные компактные Resource через whenLoaded). `closeSlot` вне API — с сайта всегда true (слот закрывается с привязкой к записи). Количество 1–4 — константы `PriceCalculator::MIN/MAX/DEFAULT_QUANTITY` (единственный источник, FormRequest и сидеры ссылаются). Ошибки: DomainException → `$e->getCode() ?: 409`.

## Деньги (ADR 0013)

Все денежные поля (`base_price`, `price`, `total_price`, `price` строк) — VO `Money` (копейки внутри) через каст `MoneyCast`. Арифметика — только методы Money (`multiply`/`add`, fail fast на отрицательных); формат — `formatted()` («1 700 ₽»); в БД — копейки, в формах панели — рубли (`formatStateUsing`/`dehydrateStateUsing`), на границах API — `toKopecks()` (контракт не менялся). Livewire-гидратация record — интерфейс `Wireable`, по проводу **массив** `['kopecks' => N]`: `WireableSynth` обходит payload через `foreach`, и числовой вид ронял страницы правки записей (починено 2026-09-12 — `fromLivewire()` принимает и число из старых снапшотов). Количество 1–4 — константы `PriceCalculator`.

## Админка (волна 3, кластер «Шиномонтаж»)

Кластер Booking с группами «Услуги» / «Записи» / «Настройки»: ресурсы услуг, прайс-правил (уникальность комбинации), комплексов (состав CheckboxList), расписания недели (WeekDay, TimePicker без секунд), слотов (закр/откр, header-action генерации сетки через GenerateSlotGrid; листинг слотов — фильтр периода «Сегодня/Завтра/Текущая неделя/Следующая неделя» из `SlotPeriod` и состояние, стартовый вид — сегодня, сброс фильтров — вся сетка хронологией (прошедшие дни срезаны), колонка «Клиенты» — записавшиеся на слот с временем начала; карточка слота — заголовок с датой и часом, состав записей с услугами и количеством, добавление записи переходом на её создание с подставленным слотом; см. [Админ-панель на Filament](admin-panel-filament.md)), настроек (key/value). Записи — полный CRUD: создание оператором через `CreateAdminBooking` (транзакция, слот lockForUpdate, клиент `updateOrCreate` по телефону, состав и цены строк — из формы, итог — по сохранённым строкам, запись ровно на начало часа занимает час), правка статуса/причины/снимка, табличные действия complete/noShow/cancel (причина в модале). Форма — одна схема с `visibleOn('create'|'edit')` (в этой версии Filament `operation()` — только сеттер); состав — **один и тот же повторитель строк с ценой и итогом на создании и правке** (2026-09-15: прежний «Состав» с серверным пересчётом убран — оператор правит цену и при создании, серверный пересчёт прайсом остался только у брони с сайта).

**Время внутри часа и закрытие часа (2026-09-15).** У записи есть точное время начала: сайт бронирует час целиком (`HH:00`), оператор в панели выбирает любую минуту внутри часа слота (14:00–14:59, выбор слота подставляет начало). Час занимает **только запись ровно на его начало** — она закрывает слот и привязывает его к себе; уехала внутрь часа — час освобождается (чужую привязку и ручную причину правка не трогает). Две записи на одно время в одном слоте невозможны: `EnsureSlotTimeIsFree` под `lockForUpdate` слота — во всех каналах (создание и правка в панели, подтверждение на сайте), отменённые время не держат, правимая запись себе не мешает. Чекбокса «Закрыть слот с привязкой к записи» в форме больше нет — закрытие определяет время.

**Правка записи оператором (ФТ-19, 2026-09-15).** Состав правится строками прямо в форме (повторитель `items` в табличном виде), а не отдельной таблицей: relation manager — отдельный Livewire-компонент, он не видит ещё не сохранённый радиус, а пересчёт после сохранения молча затирал бы ручную правку цены. Правила цены: строки открываются **снимком** (`mutateFormDataBeforeFill` — правка прайса задним числом запись не меняет); смена радиуса или типа авто пересчитывает цену услуг **с правилами** по кубу (услуга × радиус × тип), услуги без правил сохраняют снимок; комбинации в прайсе нет — уведомление, цена остаётся (молчаливая подстановка `base_price` запрещена); цена строки правится руками, но смена радиуса/типа её перезаписывает у услуг с правилами. Итоговая стоимость — `TextEntry` со `state()` в схеме (Σ цена × количество) и `bookings.total_price`, пересчитываемый сервером при сохранении из сохранённых строк. Сохранение — Action `UpdateAdminBooking` (транзакция: снимок полей, синхронизация строк по `service_id` без пересоздания, итог), страница не пишет модель напрямую. Цена из формы сохраняется как есть — ручная правка оператора прайсом не пересчитывается.

## Сидеры

`BookingCatalogSeeder` (реальный прайс-лист: 5 работ × 9 радиусов × 3 типа = 135 правил, 5 допработ без правил по base_price, комплекс «Сезонный шиномонтаж»; услуги вне прайса деактивируются), `BookingScheduleSeeder` (Пн–Сб 9:00–19:00), `BookingSettingsSeeder` — вызываются из DatabaseSeeder безусловно. `BookingSlotSeeder` (сетка через GenerateSlotGrid) и `DemoBookingSeeder` (неделя вокруг today: клиенты, история done/cancelled/no_show, будущие confirmed) — local-only; демо держит правило домена: записи на начало часа закрывают свой слот, двух записей на одно время в слоте не создаётся.

## Осталось (не реализовано, из ФТ-карты)

Крон чистки просроченных кодов (ФТ-9); «Моя запись» — отмена клиентом (ФТ-14, верификатор `booking_code_id` готов); календарь дня (ФТ-17); перенос записи (ФТ-20); снятие привязки закрытия слота при отмене и массовое закрытие дня (ФТ-15/16); подстановка параметров из последней записи (ФТ-18); карточка клиента (ФТ-22). UI-слой SPA — отдельный план в репо frontend. Полная карта — `documentations/tz/booking-functional-requirements.md`.

## See Also

- [Архитектура приложения: слои и путь запроса](architecture-layers.md)
- [Админ-панель на Filament](admin-panel-filament.md)
- [Каталог: ценообразование](catalog-pricing.md)
