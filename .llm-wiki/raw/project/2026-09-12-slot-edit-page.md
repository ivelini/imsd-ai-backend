# Карточка слота: заголовок, скрытые дата/час, таблица записей + починка страницы правки записи

> Source: код рабочего дерева backend: `app/ValueObjects/Money.php`, `app/Filament/Clusters/Booking/Resources/Slots/Pages/EditSlot.php`, `.../Slots/Schemas/SlotForm.php`, `.../Slots/RelationManagers/BookingsRelationManager.php`, `.../Slots/SlotResource.php`, `.../Bookings/Pages/CreateBooking.php`, `database/factories/Booking/BookingFactory.php`; тесты `BookingsRelationManagerTest`, `SlotResourceTest`, `BookingResourceTest`, `MoneyTest`; план `.claude/plans/booking-slot-edit-page.md`
> Collected: 2026-09-12
> Published: Unknown

## Что изменилось

- `EditSlot::getTitle()` — заголовок «Редактирование слота: 12.09.2026, 10:00» (дата и час слота).
- `SlotForm` — `date` и `hour` получили `visibleOn('create')`: на правке правятся только «Слот закрыт» и «Причина закрытия».
- Новый `BookingsRelationManager` (вкладка «Записи» на странице слота, зарегистрирован в `SlotResource::getRelations()`): колонки «Клиент» (имя и телефон), «Статус» (badge), «Услуги» (состав строками «Услуга — 2 шт», `with('items.service')` против N+1), «Сумма» (`Money::formatted()`).
  - «Добавить запись» — кнопка-ссылка (`Action::url()`) на страницу создания записи с `?slot_id=<слот>`: форма создания одна, создание идёт обычным путём через `CreateAdminBooking` (дублирования схемы нет).
  - «Изменить» — `EditAction` со ссылкой на карточку записи `BookingResource::getUrl('edit')` (не модалка).
  - «Удалить» — обычный `DeleteAction`: правил перед удалением нет, привязка закрытия снимается FK-правилом (`booking_slots.booking_id` — `nullOnDelete`), слот остаётся закрытым и открывается кнопкой «Открыть» на сетке.
- `CreateBooking` — `#[Url] public ?int $slot_id` и подстановка слота в форму при заполнении (переход со страницы слота).

## Починенный баг: страница правки записи падала

Симптом: нажатие «Изменить» у записи давало 500; в логе — `foreach() argument must be of type array|object, int given` в `WireableSynth::dehydrate()`, кадр `dehydrateProperties(EditBooking)` с путём `data.total_price`. Четыре падения за день (10:00–10:56).

Три слоя:

1. **Money отдавал в Livewire число.** `Money implements Wireable`, `toLivewire(): int`, а `WireableSynth` обходит payload через `foreach` и реализует `ArrayShapedSynth` — то есть ждёт массив. Filament заполняет состояние формы `data` всеми атрибутами записи, и денежный атрибут без поля в форме (`total_price`; у услуги `base_price` поле есть, поэтому её страница не падала) уезжает в снапшот как есть. Решение: `toLivewire(): array{kopecks: int}`, `fromLivewire()` принимает и массив, и число (снапшоты открытых страниц переживают деплой). `jsonSerialize()` не менялся — по API деньги остаются числом.
2. **Фабрика генерировала невалидный UTF-8.** После фикса Money страница упала на `Malformed UTF-8 characters, possibly incorrectly encoded` при `json_encode` состояния. Причина — `fake()->regexify('[АВЕКМНОРСТУХ][0-9]{3}…')`: faker режет многобайтный набор по байтам и выдаёт битые последовательности. Госномер теперь собирается по частям из массива букв (`BookingFactory::plate()`).
3. **`Schema::getState()` в Filament 5 валидирует форму** и на пустой бросает `ValidationException` — в `fillForm()` не годится. Подстановка слота идёт через `rawState([...$this->form->getRawState(), 'slot_id' => …])` (штатный `CreateRecord` так же делает для «создать ещё»); вариант `fill(['slot_id' => …])` теряет дефолты полей (`close_slot` = true) — проверено зондом по сырому состоянию формы.

## Тесты

- `MoneyTest`: payload — массив; круг `fromLivewire(toLivewire())` и чтение старого числового вида.
- `BookingResourceTest::test_edit_page_renders` — регрессия падения страницы правки.
- `SlotResourceTest`: заголовок с датой и часом; полей `date`/`hour` в форме правки нет.
- `BookingsRelationManagerTest` (новый, 4 кейса): состав с количеством на своём слоте; «Добавить запись» — ссылка с `slot_id` плюс создание через форму с серверным пересчётом цены; удаление оставляет слот закрытым; «Изменить» ведёт на карточку записи.

Прогон: 547 тестов прошли, `make phpstan` — 0 ошибок, `make lint-fix` — чисто.
