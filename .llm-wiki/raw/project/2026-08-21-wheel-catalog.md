# Публичный каталог дисков: фасеты + листинг

> Source: изменение кода (маршруты, actions, ресурсы, кеш) 2026-08-21
> Collected: 2026-08-21
> Published: 2026-08-21

Изменение: публичный API дисков по образцу шин — фасеты и листинг с ценой города.

## Новые эндпоинты

- `GET /api/reference/filter/wheel` — фасеты дисков (аналог filter/tire): width, diameter, pcd, et, hub_diameter, type, color, brand, country, delivery, price. Принимает query-фильтры и сужает фасеты.
- `GET /api/catalog/wheels` — пагинированный листинг дисков (аналог catalog/tires): published + в наличии + цена города, сортировка по цене, meta.seo, город city_id → city-слаг → дефолт.

## Фасеты

Форматы: width/et/hub_diameter — decimal как в БД (строка '6.5'/'38.0' через каст `decimal:1`); diameter — число (без префикса r, в отличие от шин); pcd — строка '5*112'; type — {label: Литые/Стальные/Кованые (WheelType::label), value: alloy/steel/forged} — как season у шин; brand/country — по slug; delivery/price — по stocks+catalog_prices морфа 'wheel'.

## Реализация

- `WheelProductBuilder::byCatalogFilters(cityId, filters, requireCityPrice)` — единый источник фильтров фасетов и листинга (копия TireProductBuilder без profile/season/studded; brand/country по slug; delivery/price по id из групповых запросов; requireCityPrice — листинг не показывает товары без цены города).
- `GetWheelFilterValues` (Action) — базовый запрос (published + whereHas stocks>0 + byCatalogFilters) + клоны на каждый фасет (~11 запросов на промах кеша); `WheelFacetAssembler` — чистые функции (type/dimension/named/delivery/priceRange).
- `GetWheelList` (Action) — копия GetTireList: eagers brand/model/images, enrichCityPrices батч-запросом (join stocks→catalog_prices, groupBy stockable_id), сортировка по цене скалярным подзапросом, id desc.
- Элемент листинга (`WheelListItemResource`): id, name, slug, brand, model, width, diameter, pcd, et, hub_diameter, type {label, value}, color, price, delivery_min, delivery_max, images.
- SEO: `GetTireListSeo` переименован в `GetCatalogListSeo` — один класс для обоих каталогов (дефолт config/shop.php «Шины и диски в {city}»; с брендом — категория по типу бренда через SeoTitleBuilder).

## Касты модели

`WheelProduct`: `width`, `et`, `hub_diameter` → каст `decimal:1` (стабильный строковый формат на чтении/записи; pgsql и так отдаёт '38.0', sqlite в тестах терял дробную часть — 38.0 → 38). Фильтры `byWidths/byEts/byHubDiameters` нормализуют вход (`number_format(1)`), чтобы гдеIn совпадал в обеих средах.

## Кеш и инвалидация

- `WheelFilterValuesCacheService` — ключ `wheel-filter:{city|default}:{md5(filters)}`.
- `WheelListCacheService` — ключ `wheel-list:v1:...` (payload сериализуется JSON-roundtrip).
- Инвалидация: `WheelProductObserver` (новый, saved/deleted) + wheel-сервисы добавлены в 6 существующих обсерверов (Stock, DeliverySchedule, CityDeliveryTime, CityPriceRule, WarehouseMarkupRule, TireProduct) и в ImportMasterJob / PointImportJob.
- TTL: `config/cache_ttl.php` → wheel_filter, wheel_list.

## Тесты

+38: WheelFacetAssemblerTest (unit), WheelProductBuilderTest (byCatalogFilters), GetWheelFilterValuesTest (11 ключей, исключения, сужение фильтрами, кеш), GetWheelListTest (shape, seo, type-reference, агрегация цены/сроков, фильтры, city-слаг, сортировка, кеш). Полный прогон 416 passed.
