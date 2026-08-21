# Листинг шин: meta.seo + delivery[] массив

> Источник: реализация 2026-08-21 (состояние кода на момент изменения).

## 1. meta.seo в GET /api/catalog/tires

Ответ листинга дополнен `meta.seo: {title, description}` (всегда присутствует):

- **Выбран brand** (`?brand={slug}`): title = «{Категория} {brand.name} в {город}», категория по `BrandType` (tire → «Шины», wheel → «Диски», both → «Шины и диски»); description = `brands.description`.
- **Без brand**: дефолт из `config/shop.php` → `seo: {title, description}`; в title плейсхолдер `{city}` заменяется на выбранный город в предложном падеже.

Город в предложном падеже — эвристика `SeoTitleBuilder::prepositionalCity` (чистая функция, ADR 0001): суффиксы `-ск`/`-бург`/`-град` получают `-е` («в Челябинске», «в Екатеринбурге», «в Волгограде»), прочие — «в городе {name}» («в городе Москва»). Выбранный город — `city_id` из query или город по умолчанию из конфига.

Реализация: `SeoTitleBuilder` (Services/Catalog, static-чистые функции) → `GetTireListSeo` (Actions/Catalog/Tire, БД-обвязка + DI дефолта через `needs('$defaultSeo')->giveConfig('shop.seo')` в CatalogServiceProvider) → контроллер добавляет seo в payload кеша (детерминировано: ключ уже содержит фильтры и город).

## 2. delivery — массив бакетов

Query-параметр `delivery` теперь принимает массив: `delivery[]=today&delivery[]=between1and3days`. Товар попадает в выдачу, если его min_days входит в **любой** из выбранных бакетов.

- Валидация: `delivery` nullable array + `delivery.*` in бакетов (`DeliveryDaysType`); старый вид `delivery=…` → 422.
- `TireProductBuilder::byDeliveryRanges(cityId, list<DeliveryDaysType>)` — whereIn по объединённым productIds бакетов (заменил byDeliveryRange); вызов в `byCatalogFilters` — общий для фасетов и листинга.
- Контракт общий: `TireFilterValuesRequest` (фасеты) наследуется `TireListRequest` (листинг).

## 3. Кеш

Смена payload (meta.seo) → версия ключа `tire-list:v5` (правило версионирования ADR 0004).
