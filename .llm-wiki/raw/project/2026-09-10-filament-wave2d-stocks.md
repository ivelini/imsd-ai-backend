# Волна 2d (часть 2) миграции на Filament: остатки складов + фикс пересчёта цен

> Source: План .claude/plans/filament-migration.md + код (StocksRelationManager, PopulateCatalogPrices, PriceCalculator)
> Collected: 2026-09-10
> Published: 2026-09-10

## Реализовано

`App\Filament\Resources\Products\RelationManagers\StocksRelationManager` — общий для Tire/Wheel (связь `stocks` морфная):
- таблица: склад (поиск/сортировка), количество, закупочная, продажная;
- Create/Edit: Select склада (на edit заблокирован), количество, закупочная цена (live onBlur) и продажная;
- ввод закупочной пересчитывает продажную по правилу наценки склада (ADM-4.2.3) и подставляет в поле — админ может перебить вручную (ADM-4.1.3);
- уникальность склада в рамках товара — правило `unique` со `stockable_type`/`stockable_id` владельца (страхует unique-индекс `stocks`);
- после создания/правки — `PopulateCatalogPrices(stockIds: [id])` (пересчёт только затронутого остатка, ADR 0002);
- удаление снимает зависимые `catalog_prices` (FK без каскада).

Закрывает FR ADM-4.1.2/4.1.3: в старом admin API был только GET, редактирования остатков не существовало вовсе.

## Фикс расхождения с FR в расчёте цен

`PopulateCatalogPrices` считала наценку склада заново из `stocks.purchase_price`, игнорируя `stocks.price`. Следствие: ручная продажная цена (FR ADM-4.1.3) затиралась при любом пересчёте — импортом, сменой правила склада, правкой города. FR ADM-10.2.2/10.2.3 и ADR 0002 требуют другого: правило города матчится по `stocks.price`, финал = `stocks.price + markup`.

Теперь `PopulateCatalogPrices` берёт готовую `stocks.price` (наценка склада применена при записи), пропускает остатки без цены и матчит правило города по ней. `RecalcContext` лишился поля `warehouseRules`; `PriceCalculator::calculateFinalPrice()` удалён.

`PriceCalculator::calculateForWarehouse()` — новый единый путь «поиск правила склада + применение» для импорта (`UpsertStock`) и панели (RelationManager): раньше композиция `findRule` + `applyRule` жила только в импорте.

## Удалён мёртвый код

`GetWarehouseStock` + `GetWarehouseStockInput/Result` + `WarehouseStockRow` + `WarehouseStockRowResource` + `StockResource` + `GetWarehouseStockTest`; `PriceCalculator::calculateFinalPrice`; `DeliveryTimeCalculator::calculate`/`calculateAll` (класс стал чистым `deliveryRange`, ADR 0001; тест переехал Feature → Unit без `RefreshDatabase`); `phpstan-baseline.neon` (единственная запись — для удалённого файла).

## Тесты

`StocksRelationManagerTest` (10): показ остатков, цена по правилу склада, без правила = закупочная, дубль склада отклоняется, пересчёт `catalog_prices` при создании, смена только количества не трогает продажную, смена закупочной пересчитывает обе цены, ручная продажная выживает, удаление уносит цены города, работа для диска (morph wheel). `PopulateCatalogPricesTest`: 3 новых кейса на новый источник цены + 8 переведены на явную `price`. Красный 10 failed → зелёный; полный сьют 423 passed.
