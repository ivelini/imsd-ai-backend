# Правки панели: остатки складов только для чтения, удаление шин из списка убрано

> Source: код рабочего дерева backend (незакоммиченный WIP): `app/Filament/Clusters/Catalog/Resources/Products/RelationManagers/StocksRelationManager.php`, `app/Filament/Clusters/Catalog/Resources/TireProducts/Tables/TireProductsTable.php`, `app/Filament/Clusters/Catalog/CatalogGroupEnum.php`, `app/Filament/Clusters/Catalog/Pages/ImportProducts.php`, `app/Providers/Filament/AdminPanelProvider.php`, `tests/Feature/Admin/Catalog/StocksRelationManagerTest.php`, `tests/Feature/Admin/Catalog/TireResourceTest.php`
> Collected: 2026-09-12
> Published: Unknown

## Что изменилось

- `StocksRelationManager` (карточка шины и диска, кластер «Каталог») — **только чтение**: удалены действия «Добавить» (CreateAction), «Изменить» (EditAction), «Удалить» (DeleteAction) и приватные методы `stockForm()`, `composeStockData()`, `salePrice()`, `recalculateCatalogPrices()`. Остались колонки: склад, количество, закупочная, продажная.
- Следствие: правка остатка и цен из панели недоступна. Количество и цены пишет импорт — `UpsertStock` применяет наценку склада при записи остатка (ADR 0009), `PopulateCatalogPrices` вызывается джобами импорта, а не панелью. `PriceCalculator::calculateForWarehouse` остался нужен импорту.
- `TireProductsTable` — из действий строки осталось только «Изменить»: удаление шины из списка убрано. У дисков (`WheelProductsTable`) `DeleteAction` сохранён — панель несимметрична.
- Тесты приведены к новому поведению: `StocksRelationManagerTest` — 2 кейса чтения (шины, диски), 9 кейсов действий удалены; из `TireResourceTest` удалён `test_delete_tire_removes_record`.
- Попутно в том же WIP: группа поднавигации «Управление» (`CatalogGroupEnum::Management`) для страницы импорта и `navigationSort` у ресурсов каталога; отключены хлебные крошки панели (`breadcrumbs(false)`); EAN в форме шины — `readonly()`; колонка складов в списке шин рендерится как HTML; в `Makefile` добавлен таргет `route-list`.

## Проверки

- `make phpstan` — 0 ошибок (было 3: мёртвые методы в relation manager).
- `make test` — 523 прошли, 0 падений (было 522 прошли, 10 падений).
- `make lint-fix` — 596 файлов, правок нет.
