# Волна 1b миграции на Filament: партия справочников

> Source: План .claude/plans/filament-migration.md + код (7 Filament-ресурсов)
> Collected: 2026-09-09
> Published: 2026-09-09

Перенесены на Filament 7 справочников волны 1.

## Ресурсы

CRUD: Warehouses (name), WarehouseMarkupRules (warehouse_id → Select::relationship, price_from/to/coefficient numeric min), DeliverySchedules (warehouse, day_of_week, cutoff_time, days_before/after), CityPriceRules (city_id, price_from/to, markup), DeliveryPoints (city_id, address, phone, email, work_hours, info, pickup_from_truck).

Read-only (canCreate/canEdit/canDelete = false, только List-страница, без Form): Cities (region.name, name, sort), Countries (name, slug). Наполняются импортом.

Детали реализации:
- день недели DeliverySchedule: `Select` со скалярными значениями `WeekDay` (int-бэкенд enum; передача enum-объекта в Select ломала каст `integer` модели — state должен оставаться int). `WeekDay::label()` (русские названия) добавлен в enum.
- `TimePicker->seconds(false)` — модель хранит 'H:i' (без секунд; API-контракт ожидал regex ^\d{2}:\d{2}$).
- удаление партии — стандартный DeleteAction (Precondition'ов нет; единственное отличие — Brand с EnsureBrandHasNoProducts).

## Снос API-слоя (7 разделов)

Маршруты (routes/admin/catalog.php), 7 контроллеров, 11 Request'ов, 7 admin-Resources, 6 Action чтения (GetWarehouseList, GetCityList, GetCityPriceRuleList, GetDeliveryPointList, GetDeliveryScheduleList, GetMarkupRuleList), 5 Feature-тестов API (WarehouseTest, MarkupRuleTest, CityPriceRuleTest, DeliveryPointTest, CityTest).

Остались до волны 2: `StockResource`/`WarehouseStockRowResource` (admin) и `GetWarehouseStock` — складские остатки в товарных API Tire/Wheel. Наблюдатели (CityPriceRuleObserver, DeliveryScheduleObserver, WarehouseMarkupRuleObserver) остаются — инвалидация на записях из форм.

## Тесты

19 ресурсных (create/update/delete или list по каждому) + DirectoryApiRemovalTest (7 маршрутов → 404). Красный 26 failed → зелёный; полный сьют 457 passed.
