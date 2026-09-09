# Волна 1a миграции на Filament: BrandResource + снос API брендов

> Source: План .claude/plans/filament-migration.md + код (BrandResource, BrandsTable, BrandForm)
> Collected: 2026-09-09
> Published: 2026-09-09

Волна 1 — справочники. 1a — пилотный экран Brand (полный цикл переноса).

## Реализовано

- `App\Filament\Resources\Brands\BrandResource` (страницы List/Create/Edit): форма — name/slug (unique, ignoreRecord), type (enum BrandType), logo (FileUpload: disk public, directory brands, visibility public), description. Таблица — поиск, сортировка, badge type (label enum), фильтр SelectFilter type, ImageColumn logo.
- Удаление — кастомный DeleteAction в таблице: Precondition `EnsureBrandHasNoProducts` (счётчики товаров через withCount) → DomainException → danger-нотификация; bulk-delete убран (обходил бы Precondition). Edit-страница без header DeleteAction — удаление только из таблицы (единая точка).

## Снос API-среза Brand

Удалены: маршрут `apiResource /brands`, BrandController, BrandRequest/BrandIndexRequest, BrandResource (admin), Action GetBrandList, Feature BrandTest.

Оставлены осознанно:
- `BrandBriefResource` — вложенный ресурс бренда в живых admin API Tire/Wheel (умрёт в волне 2 вместе с товарными срезами);
- `EnsureBrandHasNoProducts` — переиспользуется панельным DeleteAction;
- `BrandObserver` + ReferencesCacheService — инвалидация срабатывает на Model::create из формы (тест: create через форму → Cache::has('references') === false).

Модельные тесты isTireBrand/isWheelBrand из удалённого BrandTest не переносились: покрыты `BrandTypeTest` (covers).

## Тесты

BrandResourceTest (7): кеш-инвалидация, unique slug, update, delete без/с товарами (Precondition), логотип, фильтр type. BrandApiRemovalTest (1): /api/admin/catalog/brands → 404. Красный прогон 8 failed → зелёный 8 passed; полный сьют 460 passed.
