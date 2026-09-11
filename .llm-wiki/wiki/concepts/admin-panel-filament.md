# Админ-панель на Filament

> Sources: Проект, 2026-09-09; снос admin API товаров, изображений, импорта, остатков и акций 2026-09-10; навигация-кластеры 2026-09-10; город в списке шин 2026-09-11; пересборка таблицы при смене города 2026-09-11; фильтры листинга шин и контракт HasLabel 2026-09-11
> Raw: [2026-09-09-filament-admin-panel.md](../../raw/project/2026-09-09-filament-admin-panel.md); [2026-09-09-filament-wave1a-brand.md](../../raw/project/2026-09-09-filament-wave1a-brand.md); [2026-09-09-filament-wave1b-directories.md](../../raw/project/2026-09-09-filament-wave1b-directories.md); [2026-09-09-filament-wave1c-model.md](../../raw/project/2026-09-09-filament-wave1c-model.md); [2026-09-09-filament-wave2a-tire.md](../../raw/project/2026-09-09-filament-wave2a-tire.md); [2026-09-10-filament-wave2b-wheel.md](../../raw/project/2026-09-10-filament-wave2b-wheel.md); [2026-09-10-filament-wave2c-images.md](../../raw/project/2026-09-10-filament-wave2c-images.md); [2026-09-10-filament-wave2d-import.md](../../raw/project/2026-09-10-filament-wave2d-import.md); [2026-09-10-filament-wave2d-stocks.md](../../raw/project/2026-09-10-filament-wave2d-stocks.md); [2026-09-10-filament-wave2d-promotions.md](../../raw/project/2026-09-10-filament-wave2d-promotions.md); [2026-09-10-filament-navigation-clusters.md](../../raw/project/2026-09-10-filament-navigation-clusters.md); [2026-09-11-filament-tire-city-price.md](../../raw/project/2026-09-11-filament-tire-city-price.md); [2026-09-11-filament-tire-city-switch.md](../../raw/project/2026-09-11-filament-tire-city-switch.md); [2026-09-11-filament-tire-filters.md](../../raw/project/2026-09-11-filament-tire-filters.md)

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

## Волна 2d/4 — цена и срок по выбранному городу в списке шин (готово)

`ListTireProducts` учился выбирать город: над таблицей — селект (`content()` + `EmbeddedTable`; штатные табы и RenderHook'и сохранены), состояние — Livewire-свойство `#[Url] $cityId`, то есть город живёт в URL (`?cityId=6`) и переживает перезагрузку/шаринг ссылки; дефолт в `mount()` — `config('shop.default_city')` («Челябинск»), несуществующий id трактуется как отсутствие города. Атрибут `#[Url]` Livewire применяет раньше `mount()`, поэтому дефолт ставится через `??=`.

Колонка «Цена в городе (Уфа)» в `TireProductsTable` — `12 800,00 ₽ — 5 дн. (наценка 300,00 ₽)`, три части независимы: **цена** — минимум `catalog_prices.price` по остаткам в наличии (как в листинге каталога), **наценка** — разница `base_price` и `stocks.price` той же строки снимка (у Челябинска правил нет — не выводится), **срок** — точный расчёт `DeliveryInfoService::enrichProduct()` на момент запроса. Без города колонки нет, без цены города — «Нет цены». Границы «снимок vs расчёт на лету» — ADR 0011.

Запрос листинга грузит `stocks.warehouse.deliverySchedules` и цены города одним `with()` (иначе N+1), сервис срока — один инстанс на рендер (кеш дней и правил города внутри). Порог склада `shop.delivery_min_quantity` здесь не участвует: срок берётся по остаткам товара, как на карточке.

### Смена города: конфигурацию таблицы приходится пересобирать

**Состояние страницы, прочитанное в `table()`/schema-классе, отстаёт на один рендер.** Livewire (`HandleComponents::update()`) сначала гидратирует компонент — и в этой фазе исполняется `bootedInteractsWithTable()`, который собирает и **кеширует** таблицу (`$this->table = $this->table($this->makeTable())`, `getTable()` — просто отдача кеша), — и только потом применяет обновлённые свойства. К моменту `render()` менять таблицу уже некому: `configure($table, $this->currentCity())` был вызван со старым `cityId` из снапшота.

Общая асимметрия Filament: **структура** таблицы (колонки, фильтры, заголовки) собирается на гидратации, а **данные** разрешаются лениво при рендере. Штатные `tableFilters`/`tableSearch`/`tableSort` работают именно потому, что читаются в момент построения запроса; город же зашит в структуру — в заголовок колонки и в замыкание `state()` — и попадает в кеш.

Лечится пересборкой в `updatedCityId()`: `$this->table = $this->table($this->makeTable())` + `flushCachedTableRecords()` + `resetPage()`.

- `resetTable()` (штатный API «сбросить таблицу») **не подходит**: он дополнительно вызывает `resetTableFiltersForm()`, который перезаписывает `$this->tableFilters` пустой формой, — сбросил бы активные фильтры, если они есть (у таблицы шин фильтры сняты).
- Ленивые замыкания (`fn () => $this->currentCity()` внутри `configure()`) — рабочий путь (колонки принимают `Closure` для `label()`, запрос строится при рендере), но **наличие** колонки решается жадно, и следующий, кто напишет `->label("...{$city->name}")` без замыкания, вернёт баг молча. Пересборка чинит класс целиком.
- `bootedInteractsWithTable()` вызывать руками нельзя — это lifecycle-хук: повторный прогон трогает session-состояние поиска/сортировки.

Тот же кеш даёт попутный краш: `TireProductsTable` вызывал `getRelation('delivery')` безусловно, а `DeliveryInfoService::enrichProduct()` выставляет связь только когда срок вычислим. Товар с остатками, но без расписаний у складов, ронял листинг с `Undefined array key "delivery"`; закрыто проверкой `relationLoaded()`.

Тесты — `TireProductsTableTest` (+6 кейсов, включая остаток без расписания) и `TireProductsCitySwitchTest` (переключение города; панель задаётся явно через `Filament::setCurrentPanel()`, HTTP-запроса нет). Опции селекта Filament отдаёт Alpine-атрибутом (`options: JSON.parse(...)`), не текстом: тест проверяет `assertSee('Уфа')` и связку `entangle('cityId'` — иначе браузерный выбор города молча перестал бы что-либо менять.

## Фильтры листинга шин — характеристики

Сезон, ширина, профиль, диаметр и шипы — штатные `SelectFilter` с `->filtersLayout(FiltersLayout::AboveContent)`: селекты рендерятся **над таблицей**, тем же видом, что городской селект.

**Правило выбора:** страничное свойство (как `cityId`) оправдано только там, где параметр меняет **состав колонок** — иначе таблицу приходится пересобирать в `updatedXxx`. Параметр, который лишь сужает выборку, отдаётся штатному фильтру: Filament сам ведёт состояние в `tableFilters` (свойство `#[Url]` у `ListRecords` → query string), сбрасывает страницу, применяет `where`, рисует кнопку сброса. Своего кода на жизненный цикл — ноль.

Опции размеров — из каталога (`distinct()` + `orderBy`, ключ = значение), включая неопубликованные товары: администратор ищет товар, а не витрину. `TireFacetAssembler::dimension()` не переиспользуется — он отдаёт значения контракта публичного API (`w205`, `r16`). Сезон — `->options(Season::class)`, все три кейса независимо от данных.

## Энумы в панели: контракт HasLabel

`->options(X::class)` для энума **без** `Filament\Support\Contracts\HasLabel` молча подставляет имя кейса, игнорируя собственный `label()` энума (`HasOptions::getOptions()`, `vendor/filament/forms/src/Components/Concerns/HasOptions.php:41`). Ни один энум проекта контракт не реализовывал — в панели жили `Winter`, `Alloy`, `Percent`, `Tire` вместо «Зимняя», «Литые», «Процент», «Шинные» (7 мест).

Фикс — `Season`, `WheelType`, `PromotionType`, `BrandType` получили `implements HasLabel` и трейт `App\Enums\Concerns\HasFilamentLabel` (адаптер `getLabel()` → доменный `label()`; трейт — потому что без него один и тот же метод копировался бы в каждый энум). Новый энум панели — `implements HasLabel` + `use HasFilamentLabel`; без контракта Filament откатится на имя кейса без единой ошибки.

## Навигация (кластеры + группы)

Разделы группируются **кластером** — `app/Filament/Clusters/<Раздел>/`: кластер даёт один пункт верхнего уровня и поднавигацию внутри. Кластер «Каталог» (`CatalogCluster`) собрал 10 ресурсов и страницу импорта; внутри — группы поднавигации из `CatalogGroupEnum`: «Продукция» (Brands, TireProducts, WheelProducts, ProductModels), «Склады» (Warehouses, WarehouseMarkupRules, DeliverySchedules), «Точки выдачи» (CityPriceRules, DeliveryPoints). Панель — с верхним меню (`topNavigation()`).

**Принадлежность к кластеру задаётся явно свойством `$cluster` на каждом ресурсе/странице.** Filament не выводит её из расположения файла — каталог `Clusters/…` задаёт только размещение кода. Перенос файлов без `$cluster` даёт обратный эффект: кластер пуст и не виден (`canAccessClusteredComponents()` = false), а ресурсы остаются плоским списком. Кластеризация меняет URL разделов (префикс `/panel/catalog/...`) — ссылки строить только через `getUrl()`.

Экраны Cities и Countries из панели удалены (в кластер не переносились). ADR 0008 (обновлён).

Кластер **«Шиномонтаж»** (`BookingCluster`, ADR 0012) — 7 ресурсов записи на шиномонтаж; группы `BookingGroupEnum`: «Услуги» (BookingServices, PriceRules, ComplexServices), «Записи» (Slots, Bookings), «Настройки» (ScheduleTemplates, Settings). Деньги в формах — рубли через `formatStateUsing`/`dehydrateStateUsing` (`numeric()` не совместим с Money-состоянием — NumberStateCast); record с Money-кастом гидратируется Livewire через `Wireable`. См. [Запись на шиномонтаж: домен Booking](booking-domain.md).

## See Also

- [Архитектура приложения: слои и путь запроса](architecture-layers.md)
- [Импорт каталога из XLSX](xlsx-import-pipeline.md)
- [Кеширование](caching.md)
