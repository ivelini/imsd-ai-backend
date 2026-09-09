# Волна 1c миграции на Filament: ProductModel (закрытие волны 1)

> Source: План .claude/plans/filament-migration.md + код (ProductModelResource, ProductModelObserver)
> Collected: 2026-09-09
> Published: 2026-09-09

Последний справочник волны 1.

## Реализовано

`App\Filament\Resources\ProductModels\ProductModelResource`:
- форма: brand_id (Select relationship, live), name, slug, type (Select со скалярами tire|wheel — модель хранит строку без enum-каста), image (FileUpload public/models), description.
- **slug unique в рамках brand_id**: `->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('brand_id', $get('brand_id')))` — воспроизводит составной unique из ProductModelRequest.
- таблица: brand.name, name, slug, image, type-badge; фильтры brand (relationship) и type.
- удаление — кастомный DeleteAction с `EnsureModelHasNoProducts` (как Brand): withCount → ensure → danger-нотификация при DomainException.

## ProductModelObserver (новое)

Ручные `referencesCache->forget()` из ProductModelController заменены Observer'ом (saved/deleted) — единый механизм инвалидации со BrandObserver; зарегистрирован в CatalogServiceProvider. Запись из Filament-формы инвалидирует кеш автоматически (тест).

## Снос API

Маршруты `/models`, ProductModelController, ProductModelRequest/IndexRequest, ProductModelResource (admin), GetProductModelList. `ProductModelBriefResource` остаётся до волны 2 (вложен в TireProductResource/WheelProductResource).

## Итог волны 1

9 справочников на Filament; admin API справочников снесён. Остатки админ-API: Tire/Wheel товары, CatalogProduct (products), Image, Promotion, Import (5 потоков), References. Тесты 465 passed.
