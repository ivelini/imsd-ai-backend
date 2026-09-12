# Слоты панели: решения после ревью (сортировка, прошедшие дни, время в клиентах)

> Source: код рабочего дерева backend после ревью: `app/Filament/Clusters/Booking/Resources/Slots/Tables/SlotsTable.php`, `tests/Feature/Admin/Booking/SlotFiltersTest.php`
> Collected: 2026-09-12
> Published: Unknown

Дополняет [2026-09-12-slots-panel-filters.md](2026-09-12-slots-panel-filters.md): часть решений того ingest'а к моменту коммита изменена.

## Решения

- **Сортировка — всегда хронология** (`date asc, hour asc`), от периода не зависит. Исходный вариант «вся сетка — от дальней даты» (`date desc` при пустом периоде) отменён: код упрощён до одного `defaultSort`-замыкания, зависящее от фильтра упорядочивание и его приватный метод удалены.
- **Прошедшие дни в листинг не попадают** — `->where('date', '>=', now()->startOfDay())` в `modifyQueryUsing`. Первая версия строки сравнивала с `now()` — в колонке-дате время нулевое, поэтому срезался и весь сегодняшний день; сравнение переведено на начало дня, причина записана комментарием.
- **Строка клиента — со временем начала записи**: «10:00 : Имя — телефон» (`Carbon::parse($start_time)->format('H:i')`).
- Колонка «Причина закрытия» из листинга убрана (осталась в форме правки слота).

## Тесты после ревью

- `test_all_slots_sorted_by_date_descending` → `test_all_slots_sorted_by_date_ascending` (порядок прямой);
- `test_current_week_filter_covers_whole_week` → `test_current_week_filter_covers_week_until_sunday`: понедельник текущей недели срезан базовым запросом, проверка на нём зависела бы от дня запуска теста;
- новый `test_past_slots_are_hidden` — страховка на срез прошедших дней;
- ожидание в `test_clients_column_lists_slot_bookings` — строки с временем «10:00 : …».

Прогон после правок: 538 тестов прошли, `make phpstan` — 0 ошибок, `make lint-fix` — чисто.
