# Волна 2b миграции на Filament: WheelProductResource + снос admin API товаров

> Source: План .claude/plans/filament-migration.md + код (WheelProductResource, WheelDataComposer, routes/admin/catalog.php)
> Collected: 2026-09-10
> Published: 2026-09-10

## Реализовано

`App\Filament\Resources\WheelProducts\WheelProductResource`:
- форма: brand/model (Select relationship; model_id — только type=wheel через `Rule::exists('product_models','id')->where('type','wheel')` + modifyQueryUsing), type (Select WheelType — string-каст модели даёт скалярные опции), name (опц., helper «пусто — из модели»), ean (unique ignoreRecord), country, color, размеры (width, diameter, pcd, et, hub_diameter — шаг 0.1 для decimal:1), тумблеры is_published/is_bestseller/is_new.
- таблица: name, brand/model (скрываемые), ean (поиск), type-badge, геометрия (width/diameter/pcd/et/hub_diameter), статусы; фильтры brand/type/is_published; DeleteAction.
- **WheelDataComposer** (Services/Catalog) — зеркало TireDataComposer: name из модели (DisplayNameResolver) + SEO-slug диска (ProductSlugService::wheel, ADR 0006). Вызывается хуками страниц Create/Edit.

Снос admin API товаров целиком (Tire + Wheel сразу, как согласовано):
- Маршруты: `/tires*` (CRUD + dimensions + warehouse-stock), `/wheels*` (то же), `/products` (агрегированный список).
- Контроллеры: TireProductController, GetTireDimensionsController, TireWarehouseStockController, WheelProductController, GetWheelDimensionsController, WheelWarehouseStockController, CatalogProductController.
- Request'ы: 4 Tire + 4 Wheel + CatalogProductIndexRequest + концерны ValidatesTireFilters/ValidatesWheelFilters (иных потребителей не было).
- Resources: TireProductResource (admin), WheelProductResource (admin), CatalogProductResource, **BrandBriefResource, ProductModelBriefResource** (единственными потребителями были Tire/Wheel API).
- Actions/DTO: GetTireDimensions, GetWheelDimensions, GetTireProductList, GetWheelProductList, GetCatalogProducts (+Input/Result).

Импорт (5 потоков), изображения, промоакции, references, auth/notifications — в admin API остались (следующие волны).

## Тесты

WheelResourceTest (9): генерация name+slug (nokian-xx-7-16-45-4x98-58-6 — PCD `*`→`x`, hub 58.6→58-6), отклонение tire-модели, ean unique, валидация type, коллизия slug → суффикс, пересчёт slug при смене ET, сохранение при неизменных размерах, delete, поиск по EAN. TireResourceTest дополнен кейсом коллизии slug (перенесён из ProductSlugTest — тот оставлен только с импортными кейсами). TireWheelApiRemovalTest (6): 404 на CRUD обоих товаров, dimensions, warehouse-stock, products + страж 200 на promotions. Красный 14 failed → зелёный 27 passed; полный сьют 418 passed.

Грабли: `GetWarehouseStockTest` ходил через снесённый маршрут `/tires/{id}/warehouse-stock` — переведён на прямой вызов Action + WarehouseStockRowResource. У `GetWarehouseStock` потребителей в API не осталось; Action/DTO/Resource сохранены для волны 2d (RelationManager остатков).
