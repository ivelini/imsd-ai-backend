# Админ-панель на Filament

> Sources: Проект, 2026-09-09
> Raw: [2026-09-09-filament-admin-panel.md](../../raw/project/2026-09-09-filament-admin-panel.md); [2026-09-09-filament-wave1a-brand.md](../../raw/project/2026-09-09-filament-wave1a-brand.md); [2026-09-09-filament-wave1b-directories.md](../../raw/project/2026-09-09-filament-wave1b-directories.md); [2026-09-09-filament-wave1c-model.md](../../raw/project/2026-09-09-filament-wave1c-model.md); [2026-09-09-filament-wave2a-tire.md](../../raw/project/2026-09-09-filament-wave2a-tire.md)

## Решение

Админка переезжает с React SPA (отдельный репозиторий, не в проде) на Filament-панель внутри backend: мотив — скорость будущих CRUD-разделов FR (заказы, клиенты, статьи, авто, роли). Admin API (`/api/admin/*`) удаляется вместе с переносом разделов, React замораживается. Волны: 0 каркас → 1 справочники → 2 товары → 3 новые разделы → 4 зачистка (план: `.claude/plans/filament-migration.md`).

## Каркас (волна 0)

- Панель `/panel` (id `admin`), Filament 5.x, `AdminPanelProvider` в `app/Providers/Filament/`.
- Auth: session-guard `admin` (provider `admins` → `App\Models\Auth\Admin`); `Admin implements FilamentUser` — доступ только активным (`is_active`). Sanctum-токены остаются, пока жив admin API.
- Роли: enum `AdminRoleCode` (super-admin, content-manager, order-manager, warehouse-manager), сиды ролей — из enum. Доступ к ресурсам — через policies (`App\Policies\AdminPolicy`: администраторы — только super-admin). spatie не используется.
- Уведомления: нативные Filament (`->databaseNotifications()`; в v4 трейта нет — включение методом панели); `NotificationResource` над таблицей `notifications` с действием `markAsRead`.

## Политика слоёв (контракт для всех ресурсов)

- Чтение — Eloquent + скоупы моделей; Actions чтения API не переносятся.
- Запись доменных сущностей — только через существующие Actions, Preconditions — перед мутацией.
- Прямой Eloquent-записи ресурсами нет: иначе ломаются SEO-slug, пересчёт `catalog_prices`, инвалидация кеша Observer'ами.

## Волна 1 — справочники (в работе)

Экран переносится: Filament-ресурс (форма/таблица) → приёмка → снос API-среза (маршрут, контроллер, Request/Resource, Action чтения; Precondition переиспользуется панелью; Observer остаётся — инвалидация срабатывает на записи из формы). Bulk-delete не используется там, где удаление под Precondition (массовое удаление обходило бы проверку).

**1a — Brand (готово):** `BrandResource`; delete через `EnsureBrandHasNoProducts` (danger-нотификация); срезаны маршрут/контроллер/Request/Resource/GetBrandList. `BrandBriefResource` живёт до волны 2 (вложен в API Tire/Wheel). Тесты: BrandResourceTest (7) + BrandApiRemovalTest (404).

**1b — партия справочников (готово):** Warehouses, WarehouseMarkupRules, DeliverySchedules, CityPriceRules, DeliveryPoints (CRUD) + Cities, Countries (read-only: `canCreate/canEdit/canDelete = false`, только List). Детали: день недели — Select со скалярными значениями `WeekDay` (модель кастует integer), `TimePicker->seconds(false)` ('H:i' в БД); `WeekDay::label()` добавлен. Удаление партии — стандартный DeleteAction (Precondition'ов нет). Снесены маршруты/контроллеры/Request'ы/Resources/Actions чтения 7 разделов; `StockResource`/`GetWarehouseStock` живут до волны 2. Тесты: 19 ресурсных + DirectoryApiRemovalTest (7 × 404).

**1c — ProductModel (готово, волна 1 закрыта):** slug unique в рамках brand_id (`modifyRuleUsing → where('brand_id', …)`); type — скалярные опции (модель без enum-каста); image FileUpload; delete с `EnsureModelHasNoProducts`. Инвалидация references вынесена из контроллера в **ProductModelObserver** (saved/deleted) — единый механизм с BrandObserver. Снесён API-срез /models. `ProductModelBriefResource` живёт до волны 2.

**Итог волны 1:** 9 справочников на Filament; admin API справочников снесён полностью. В админ-API остались товары (Tire/Wheel), products, изображения, промоакции, импорт, references.

## Волна 2 — товары (в работе)

**2a — Tire (готово):** TireProductResource (форма по TireProductRequest; model_id — только type=tire; Section/Grid из Filament\Schemas\Components); подготовка данных вынесена в **TireDataComposer** (name из модели + SEO-slug) — единая реализация для хуков страниц и контроллера. slug/euro_label/origin_id не редактируются. Снос Tire API — после переноса Wheel и Image (ImageController общий на оба товара).

## See Also

- [Архитектура приложения: слои и путь запроса](architecture-layers.md)
- [Импорт каталога из XLSX](xlsx-import-pipeline.md)
- [Кеширование](caching.md)
