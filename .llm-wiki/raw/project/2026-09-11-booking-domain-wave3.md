# Волна 3 переноса домена Booking: админка Filament + Money VO (tireslot → backend)

> Source: код волны 3 переноса (backend, рабочее дерево) + план `.claude/plans/migration-tireslot-to-backend.md`
> Collected: 2026-09-11
> Published: 2026-09-11

## 3a — Money VO и каст (ADR 0013)

- `app/ValueObjects/Money` — readonly VO на копейках: `fromKopecks`/`fromRubles` (строка/float/int), `toKopecks`/`toRubles`, `formatted()` («1 700 ₽»), `multiply`/`add`, `jsonSerialize` → копейки, `Wireable` (Livewire-гидратация record), fail fast на отрицательные и множитель < 1.
- `app/Casts/MoneyCast` — на полях `base_price` (услуга), `price` (прайс-правило, строка состава), `total_price` (запись). Принимает Money/int/числовую строку.
- `Support\Money` (статика волны 1) удалён — формат только в VO.
- PriceCalculator/Quote/QuoteLine переведены на Money; границы (API resources, read-actions) — `toKopecks()`, API-контракт в копейках не изменился.

## 3b — кластер Booking + 6 ресурсов

- `BookingCluster` («Шиномонтаж», Heroicon CalendarDays) + `BookingGroupEnum` (Услуги / Записи / Настройки).
- Ресурсы: `BookingServiceResource` (услуги), `PriceRuleResource` (куб с уникальностью комбинации), `ComplexServiceResource` (состав CheckboxList), `ScheduleTemplateResource` (часы недели, WeekDay), `SlotResource` (закр/откр, header-action генерации сетки), `SettingResource` (key/value, ключ disabledOn edit).
- Цены в формах — рубли: `formatStateUsing` (Money → строка рублей) + `dehydrateStateUsing` (строка → Money). `rules(['numeric', 'min:0'])` вместо `numeric()` — NumberStateCast ломается об Money-объект.

## 3c — BookingResource: полный CRUD с созданием оператором

- `Actions/Booking/CreateAdminBooking` — создание записи из панели: транзакция, слот lockForUpdate (нет слота → 422), клиент firstOrCreate по телефону, состав + серверный пересчёт цены (сумма не передаётся), `close_slot` закрывает только свободный слот (закрытие — не барьер, чужая привязка не перезаписывается).
- `BookingResource`: список (статус-фильтр, счётчики), создание (клиент/слот/состав Repeater/чекбокс), правка (статус, причина отмены, снимок параметров), табличные действия arrive/complete/noShow/cancel (cancel — модал с причиной).
- Форма — одна схема с `visibleOn('create'|'edit')`: в этой версии Filament `Schema::operation()` — только сеттер без колбэка.

## Грабли Filament v5 (сверены по вендору)

- `modifyQueryUsing` — третий аргумент `relationship()`, а не отдельный метод.
- `numeric()` на поле с Money-состоянием → NumberStateCast падает; лечится `rules(['numeric'])` + format/dehydrate.
- Livewire не умеет синтезировать VO в record → `Wireable` обязателен.
- `navigationGroup` — тип `string|\UnitEnum|null`.
- arrow fn с `: void` и выражением-вызовом void-метода — «A void method must not return a value» (нужны block-замыкания).

## Прогоны

3a красный: класс не найден; зелёный 61. 3b красный: 10 failed (компонентов нет); зелёный 10. 3c красный: компонентов нет; зелёный 5. Домен целиком: 76 passed (247 assertions). Полный сьют: 522 passed, 10 пре-существующих Filament. phpstan/pint — чисто.
