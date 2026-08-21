# Knowledge Base Index

## concepts

Архитектурные и бизнес-концепции проекта: слои, цены, доставка, API, импорт, кеш.

| Article | Summary | Updated |
|---------|---------|---------|
| [Архитектура приложения: слои и путь запроса](concepts/architecture-layers.md) | Слои (FormRequest → Controller → Cache → Preconditions → Action → Response), принципы, домены, морф-мапа, аутентификация, enums, корзина | 2026-08-19 |
| [Бизнес-модель: цепочка движения товара](concepts/business-model.md) | Завод → склад → компания → точка выдачи/курьер; сущности и их видимость клиенту | 2026-08-19 |
| [Заказ: жизненный цикл](concepts/order-lifecycle.md) | Статусная машина заказа (pending → paid → … → delivered), отмена и возврат, кто меняет статусы | 2026-08-19 |
| [Каталог: ценообразование](concepts/catalog-pricing.md) | Полная цена города: цепочка наценок, MarkupRuleMatcher, акции, пересчёт catalog_prices, округление | 2026-08-19 |
| [Сроки доставки](concepts/delivery-times.md) | Графики отгрузки, delivery_min/max, расчёт срока на лету, фильтрация по бакетам | 2026-08-19 |
| [Публичный API каталога](concepts/public-catalog-filter-api.md) | Справочник городов (/api/reference/city) + фасеты и листинг шин (/api/reference/filter/tire, /api/catalog/tires) и дисков (/api/reference/filter/wheel, /api/catalog/wheels): контракт фильтров, учёт города, meta.seo, euro_label, агрегация цены, кеш с индексом ключей и JSON-roundtrip (ADR 0004) | 2026-08-21 |
| [Импорт каталога из XLSX](concepts/xlsx-import-pipeline.md) | Пайплайн upload → ImportMasterJob → чанки → ChunkJob, маппинг XLSX → БД (описания, евро-лейбл, SEO-формулы name/slug), инвалидация | 2026-08-21 |
| [Эксплуатация](concepts/operations.md) | Команды Makefile, окружение, очередь и импорты (фактические Job'ы), runbooks | 2026-08-19 |
| [Кеширование](concepts/caching.md) | Cache Service до Action, Observer-инвалидация (шины и диски), TTL, индекс ключей, JSON-roundtrip сериализация (ADR 0004) | 2026-08-21 |

## entities

Сущности БД каталога и географии.

| Article | Summary | Updated |
|---------|---------|---------|
| [Шина (TireProduct)](entities/tire-product.md) | tire_products: поля (включая euro_label), сезоны, индексы, парсинг размеров, связи, SEO-формулы name/slug | 2026-08-21 |
| [Диск (WheelProduct)](entities/wheel-product.md) | wheel_products: тип материала, геометрия (PCD/ET/DIA), касты decimal:1, парсинг при импорте, публичный каталог, slug без точек | 2026-08-21 |
| [Запись catalog_prices](entities/catalog-price.md) | Пара stock × city: полная цена и сроки, UNIQUE, пересчёт и инвалидация | 2026-08-19 |
| [Город и география](entities/geo-city.md) | Регионы, города (с slug и публичным справочником /api/reference/city: region, meta.default), точки выдачи, city_price_rules, city_delivery_times | 2026-08-20 |
