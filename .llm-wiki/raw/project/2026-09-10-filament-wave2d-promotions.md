# Волна 2d (часть 3): акции — CRUD на панели + применение скидок

> Source: План .claude/plans/filament-migration.md + код (PromotionResource, PromotionMatcher, PromotionDiscount, PopulateCatalogPrices)
> Collected: 2026-09-10
> Published: 2026-09-10

## Контекст

Акции существовали с начала проекта (CRUD, типы, полиморфная привязка), но **нигде не применялись**: `catalog_prices` скидок не учитывала, публичный каталог выводил базовую цену. Волна закрывает и CRUD-перенос, и механику применения (FR ADM-7.1.5/7.1.6, FR-7.3), решение — ADR 0010.

## Реализовано

**`PromotionResource`** (`/panel/promotions`): форма — название, описание, тип (Select `PromotionType`), значение (скрыто для gift), период (DateTimePicker, `afterOrEqual`), привязка (Select: шина / диск / бренд / пусто = весь каталог) + зависимый Select объекта. Таблица — тип-бейдж, привязка, даты, признак «активна», фильтр по типу. Пересчёт цен затронутых товаров — в `afterCreate`/`afterSave` страницы и в `after` DeleteAction.

**Применение скидки.** `PromotionDiscount::apply()` (чистая функция): percent — процент от цены, fixed — вычитание суммы, special — цена = значению, gift — цена не меняется (подарок); итог не отрицательный. `PromotionMatcher::match()` (чистая функция): приоритет по конкретике привязки — товар → бренд → каталог, внутри уровня побеждает большая скидка в рублях.

`PopulateCatalogPrices` грузит акции один раз на пересчёт и для каждой пары stock × city считает: `base_price` = stocks.price + наценка города, `price` = base_price − скидка выбранной акции. Привязка к бренду — через карту `brand_id` по ключу «morph-тип:id» (акция на бренд может задевать и шины, и диски).

**Границы действия.** Плановая задача `promotions:sync` (`routes/console.php`, `everyFiveMinutes()->withoutOverlapping()`) — первая в проекте (контейнер `imsd-backend-scheduler` работал, задач не было). `RecalculatePromotedPrices` считает затронутые остатки: по конкретной акции (панель) или по всем, пока акции есть (задача).

**API листингов**: `price` (со скидкой), `old_price` (без, только при акции), `promotion` (признак) — в `TireListItemResource`/`WheelListItemResource`; обогащение в `GetTireList`/`GetWheelList` (MIN(price) + MAX(base_price) одним запросом).

**Схема**: миграция `catalog_prices.base_price` (decimal 10,2, nullable) с backfill `base_price = price`.

**Удалено**: 5 маршрутов `/promotions*`, `PromotionController`, `PromotionRequest`/`PromotionIndexRequest`, admin-`PromotionResource`, `GetPromotionList`, `PromotionService` (+тест), `CreatePromotion`/`UpdatePromotion` (писали через морф-мапу), пустой `DiscountType`. Morph-карта проекта получила `brand => Brand::class`.

## Тесты

`PromotionDiscountTest` (5): формулы, границы, отсутствие значения. `PromotionMatcherTest` (7): приоритет товар/бренд/каталог, максимальная скидка внутри уровня, истёкшая, чужой товар, gift остаётся выбранной, чужой морф-тип. `PromotionPriceTest` (5): сквозной пересчёт, base_price, без акций, бренд, команда. `PromotionResourceTest` (6): CRUD панели, валидация дат, уровни привязки, фильтр, удаление. `PromotionApiRemovalTest` (2). Красный 23 failed → зелёный; полный сьют 441 passed.
