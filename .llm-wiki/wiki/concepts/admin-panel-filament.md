# Админ-панель на Filament

> Sources: Проект, 2026-09-09; снос admin API товаров, изображений, импорта, остатков и акций 2026-09-10; навигация-кластеры 2026-09-10
> Raw: [2026-09-09-filament-admin-panel.md](../../raw/project/2026-09-09-filament-admin-panel.md); [2026-09-09-filament-wave1a-brand.md](../../raw/project/2026-09-09-filament-wave1a-brand.md); [2026-09-09-filament-wave1b-directories.md](../../raw/project/2026-09-09-filament-wave1b-directories.md); [2026-09-09-filament-wave1c-model.md](../../raw/project/2026-09-09-filament-wave1c-model.md); [2026-09-09-filament-wave2a-tire.md](../../raw/project/2026-09-09-filament-wave2a-tire.md); [2026-09-10-filament-wave2b-wheel.md](../../raw/project/2026-09-10-filament-wave2b-wheel.md); [2026-09-10-filament-wave2c-images.md](../../raw/project/2026-09-10-filament-wave2c-images.md); [2026-09-10-filament-wave2d-import.md](../../raw/project/2026-09-10-filament-wave2d-import.md); [2026-09-10-filament-wave2d-stocks.md](../../raw/project/2026-09-10-filament-wave2d-stocks.md); [2026-09-10-filament-wave2d-promotions.md](../../raw/project/2026-09-10-filament-wave2d-promotions.md); [2026-09-10-filament-navigation-clusters.md](../../raw/project/2026-09-10-filament-navigation-clusters.md)

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

## Волна 1 — справочники (закрыта)

Экран переносится: Filament-ресурс (форма/таблица) → приёмка → снос API-среза (маршрут, контроллер, Request/Resource, Action чтения; Precondition переиспользуется панелью; Observer остаётся — инвалидация срабатывает на записи из формы). Bulk-delete не используется там, где удаление под Precondition (массовое удаление обходило бы проверку).

**1a — Brand (готово):** `BrandResource`; delete через `EnsureBrandHasNoProducts` (danger-нотификация); срезаны маршрут/контроллер/Request/Resource/GetBrandList. `BrandBriefResource` прожил до волны 2b — вложен в API Tire/Wheel (снесён вместе с ними). Тесты: BrandResourceTest (7) + BrandApiRemovalTest (404).

**1b — партия справочников (готово):** Warehouses, WarehouseMarkupRules, DeliverySchedules, CityPriceRules, DeliveryPoints (CRUD) + Cities, Countries (read-only: `canCreate/canEdit/canDelete = false`, только List). Детали: день недели — Select со скалярными значениями `WeekDay` (модель кастует integer), `TimePicker->seconds(false)` ('H:i' в БД); `WeekDay::label()` добавлен. Удаление партии — стандартный DeleteAction (Precondition'ов нет). Снесены маршруты/контроллеры/Request'ы/Resources/Actions чтения 7 разделов. Тесты: 19 ресурсных + DirectoryApiRemovalTest (7 × 404).

**1c — ProductModel (готово, волна 1 закрыта):** slug unique в рамках brand_id (`modifyRuleUsing → where('brand_id', …)`); type — скалярные опции (модель без enum-каста); image FileUpload; delete с `EnsureModelHasNoProducts`. Инвалидация references вынесена из контроллера в **ProductModelObserver** (saved/deleted) — единый механизм с BrandObserver. Снесён API-срез /models. `ProductModelBriefResource` прожил до волны 2b.

**Итог волны 1:** 9 справочников на Filament; admin API справочников снесён полностью.

## Волна 2 — товары

**2a — Tire (готово):** TireProductResource (форма по TireProductRequest; model_id — только type=tire; Section/Grid из Filament\Schemas\Components); подготовка данных вынесена в **TireDataComposer** (name из модели + SEO-slug) — единая реализация для хуков страниц и контроллера. slug/euro_label/origin_id не редактируются.

**2b — Wheel + снос API товаров (готово):** WheelProductResource — зеркало Tire (model_id только type=wheel, `type` — Select WheelType со скалярными опциями через string-каст, геометрия width/diameter/pcd/et/hub_diameter с шагом 0.1 под `decimal:1`), подготовка данных — **WheelDataComposer** (name из модели + `ProductSlugService::wheel`). admin API товаров снесён целиком одной волной: маршруты `/tires*`, `/wheels*`, `/products`; 7 контроллеров; 9 Request'ов + концерны `ValidatesTireFilters`/`ValidatesWheelFilters`; 5 Resources — включая `BrandBriefResource`/`ProductModelBriefResource` (их единственными потребителями были Tire/Wheel API) и `CatalogProductResource`; Action'ы `GetTireDimensions`/`GetWheelDimensions`/`GetTireProductList`/`GetWheelProductList`/`GetCatalogProducts` с DTO. Панель — два раздельных ресурса вместо агрегированного `/products`. `GetWarehouseStock` (+Input/Result/`WarehouseStockRowResource`) сохранён без HTTP-потребителя: нужен волне 2d (RelationManager остатков), тест переведён на прямой вызов Action. Тесты: WheelResourceTest (9), TireWheelApiRemovalTest (6 × 404 + страж), кейс коллизии slug перенесён в TireResourceTest.

**2c — изображения (готово):** один `ImagesRelationManager` на оба товара (связь `images` морфная и идентична; зарегистрирован в `getRelations()` Tire и Wheel). FileUpload с `storeFiles(false)` — файл приходит `TemporaryUploadedFile` и сохраняется **доменным Action** `UploadImage`, не Filament'ом; порядок перетаскиванием — `ReorderImages` через хук `afterReordering` (встроенная SQL-запись Filament обошла бы слой). Прочие actions: `SetMainImage`, `DeleteImage`. Снесён Image API: 5 маршрутов, контроллер, 3 Request'а, admin-`ImageResource`, `ListImages`; удалён мёртвый `ImageService::getNextMainImageId`.

**PanelAction** (`app/Filament/Support/PanelAction.php`) — общий хелпер панельных действий: `PanelAction::run('Что сделано', fn () => ...)` — success-нотификация при успехе, danger с текстом `DomainException` при провале (Precondition). Введён по правилу «обобщай на третий раз» (Brands, ProductModels, изображения); те используются через него.

Фикс дефекта: `DeleteImage` удалял только запись в `images` — файл оставался на public-диске (сироты); теперь снимается и файл.

**2d (часть 1) — импорт (готово):** страница панели `/panel/import` (`ImportProducts`, реализует `HasTable`) — форма «тип + файл» (MIME-типы зависят от типа: XLSX для шин/дисков/моделей/точек, CSV для автомобилей) → `EnsureNoActiveImport` → `StartProductImport`; ниже таблица истории импортов с фильтром по типу и `poll('5s')`. **Пайплайн (Jobs, чанки, парсеры) не менялся** — сменилась только точка входа. Снесён Import API: 7 маршрутов, 7 контроллеров, 2 Request'а, `ProductImportResource`. Адрес уведомления о завершении переведён с `/admin/imports/{id}` (React-админка) на `/panel/import`.

**2d (часть 2) — остатки складов (готово):** `StocksRelationManager` (общий для Tire/Wheel) — таблица остатков товара по складам и правка количества/закупочной/продажной цены (FR ADM-4.1.2/4.1.3; в старом API был только GET). Ввод закупочной пересчитывает продажную по правилу наценки склада, её можно перебить вручную; после сохранения — `PopulateCatalogPrices` для затронутого остатка; удаление снимает зависимые `catalog_prices`. Попутно починен `PopulateCatalogPrices`: он считал наценку склада заново из `purchase_price` и затирал ручную продажную — теперь берёт готовую `stocks.price` (FR ADM-10.2.2/10.2.3). Снесены осиротевшие `GetWarehouseStock` (+DTO/Resource), `PriceCalculator::calculateFinalPrice`, `DeliveryTimeCalculator::calculate/calculateAll`.

**2d (часть 3) — акции (готово):** `PromotionResource` (`/panel/promotions`) — CRUD акций с привязкой «шина / диск / бренд / весь каталог». Попутно реализовано **применение скидок** (до волны акции создавались, но нигде не применялись): `PromotionDiscount` (percent/fixed/special меняют цену, gift — нет) и `PromotionMatcher` (приоритет товар → бренд → каталог, внутри — большая скидка) — обе чистые функции; скидка входит в `catalog_prices.price`, рядом хранится `base_price`. Границы действия акций закрывает плановая задача `promotions:sync` (каждые 5 минут; первая задача проекта). Листинги отдают `old_price` и `promotion`. Снесён API акций (5 маршрутов). ADR 0010.

**Осталось в admin API:** references, auth/notifications.

## Навигация (кластеры + группы)

Разделы группируются **кластером** — `app/Filament/Clusters/<Раздел>/`: кластер даёт один пункт верхнего уровня и поднавигацию внутри. Кластер «Каталог» (`CatalogCluster`) собрал 10 ресурсов и страницу импорта; внутри — группы поднавигации из `CatalogGroupEnum`: «Продукция» (Brands, TireProducts, WheelProducts, ProductModels), «Склады» (Warehouses, WarehouseMarkupRules, DeliverySchedules), «Точки выдачи» (CityPriceRules, DeliveryPoints). Панель — с верхним меню (`topNavigation()`).

**Принадлежность к кластеру задаётся явно свойством `$cluster` на каждом ресурсе/странице.** Filament не выводит её из расположения файла — каталог `Clusters/…` задаёт только размещение кода. Перенос файлов без `$cluster` даёт обратный эффект: кластер пуст и не виден (`canAccessClusteredComponents()` = false), а ресурсы остаются плоским списком. Кластеризация меняет URL разделов (префикс `/panel/catalog/...`) — ссылки строить только через `getUrl()`.

Экраны Cities и Countries из панели удалены (в кластер не переносились). ADR 0008 (обновлён).

## See Also

- [Архитектура приложения: слои и путь запроса](architecture-layers.md)
- [Импорт каталога из XLSX](xlsx-import-pipeline.md)
- [Кеширование](caching.md)
