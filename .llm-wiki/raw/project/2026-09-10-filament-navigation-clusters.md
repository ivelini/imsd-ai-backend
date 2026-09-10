# Навигация панели на Filament: кластер «Каталог» + группы поднавигации

> Source: План .claude/plans/filament-migration.md + код (CatalogCluster, CatalogGroupEnum, AdminPanelProvider, ресурсы кластера)
> Collected: 2026-09-10
> Published: 2026-09-10

## Проблема

После волн 0–2d панель `/panel` показывала плоский список из десятка разделов в верхнем меню (`topNavigation()`), без группировки. Filament-кластеры в проекте не использовались, `discoverClusters()` в панели отсутствовал.

Грабли: `discoverClusters()` регистрирует только класс кластера и рекурсивно находит компоненты в каталоге. **Принадлежность ресурса к кластеру задаётся свойством `$cluster` на самом классе** (`Filament\Resources\Resource\Concerns\BelongsToCluster::getCluster()` возвращает `static::$cluster`, по умолчанию `null`) — из расположения файла Filament её не выводит. Перенос файлов в `Clusters/Catalog/Resources/` без `$cluster` даёт: `getClusteredComponents()` пуст → `Cluster::canAccessClusteredComponents()` = `false` → `shouldRegisterNavigation()` = `false` — кластер не виден, а его ресурсы остаются плоским списком верхнего уровня.

## Реализовано

- `App\Filament\Clusters\Catalog\CatalogCluster` — кластер «Каталог» (иконка `OutlinedSquares2x2`), в `AdminPanelProvider` добавлен `->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')`.
- В кластер переехали 10 ресурсов: Brands, CityPriceRules, DeliveryPoints, DeliverySchedules, ProductModels, Promotions, TireProducts, WarehouseMarkupRules, Warehouses, WheelProducts (+ RelationManagers `ImagesRelationManager`/`StocksRelationManager` в `Resources/Products/`), и кастомная страница `ImportProducts` (`/panel/import` → внутри кластера). Каждый класс получил `protected static ?string $cluster = CatalogCluster::class;`.
- `CatalogGroupEnum` (в namespace кластера, заменил корневой `NavigationGroupEnum`) — группы поднавигации: `Product` = «Продукция» (Brands, TireProducts, WheelProducts, ProductModels), `Warehouse` = «Склады» (Warehouses, WarehouseMarkupRules, DeliverySchedules), `DeliveryPoint` = «Точки выдачи» (CityPriceRules, DeliveryPoints). Группа применяется внутри поднавигации кластера, а не в верхнем меню.
- Панель: `->topNavigation()` (верхнее меню), `->sidebarCollapsibleOnDesktop()` убран как ненужный при верхней навигации.
- Экраны **Cities и Countries удалены из панели** (вместе с `CityResourceTest`/`CountryResourceTest`): в кластер не переносились, города/страны в панели не нужны.
- URL перенесённых разделов получили префикс кластера (`/panel/catalog/...`, страница импорта — `/panel/catalog/import`).

## Тесты

Полный сьют 439 passed (1307 assertions). Правки: путь импорта в `ImportPageTest::test_page_requires_auth` переведён с хардкода `/panel/import` на `ImportProducts::getUrl()`; удалены `CityResourceTest` и `CountryResourceTest`.
