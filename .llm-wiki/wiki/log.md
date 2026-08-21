# Wiki Log

## [2026-08-19] ingest | Архитектура приложения: слои и путь запроса
- Created: Каталог: ценообразование
- Created: Сроки доставки
- Created: Публичный API каталога
- Created: Импорт каталога из XLSX
- Created: Кеширование
- Created: Шина (TireProduct)
- Created: Диск (WheelProduct)
- Created: Запись catalog_prices
- Created: Город и география

## [2026-08-19] lint | 0 issues found, 0 auto-fixed

## [2026-08-19] ingest | Бизнес-модель: цепочка движения товара
- Created: Заказ: жизненный цикл
- Updated: Архитектура приложения: слои и путь запроса

## [2026-08-19] ingest | Эксплуатация: команды, окружение, очереди, runbooks
- Updated: Импорт каталога из XLSX (актуальные имена Job'ов по коду)

## [2026-08-19] ingest | Публичный API каталога: фасетный фильтр шин (city_id + инвалидация)
- Updated: Кеширование (ключ с городом, индекс ключей)

## [2026-08-19] ingest | Публичный API каталога: листинг шин + JSON-roundtrip кеша (ADR 0004)
- Updated: Кеширование (сериализация payload, версия ключа)

## [2026-08-19] ingest | Удаление supplier_id и справочника Supplier
- Updated: Шина (TireProduct) — поле supplier_id удалено
- Updated: Диск (WheelProduct) — поле supplier_id удалено
- Updated: Бизнес-модель — сущность «поставщик» удалена
- Updated: Импорт каталога из XLSX — колонка supplier убрана из маппинга

## [2026-08-19] ingest | Slug у товаров (tire_products, wheel_products)
- Updated: Шина (TireProduct) — поле slug, формула
- Updated: Диск (WheelProduct) — поле slug, формула

## [2026-08-20] ingest | Публичный API каталога: справочник городов /api/reference/city
- Updated: Город и география (geo-city.md) — поле slug, публичный вывод города

## [2026-08-20] ingest | Публичный API каталога: model в элементе листинга /api/catalog/tires
- Updated: Кеширование (версия ключа tire-list v3)

## [2026-08-20] ingest | Публичный API каталога: season-объект, meta.default, slug из характеристик
- Updated: Кеширование (версия ключа tire-list v4)
- Updated: Шина (TireProduct) — формула slug без brand/name
- Updated: Город и география (geo-city.md) — meta.default в справочнике

## [2026-08-20] ingest | Публичный API каталога: region в справочнике городов /api/reference/city
- Updated: Город и география (geo-city.md) — region в элементе справочника

## [2026-08-21] ingest | Листинг шин: meta.seo + delivery-массив
- Updated: Кеширование (версия ключа tire-list v5)

## [2026-08-21] ingest | Евро-лейбл шины (euro_label) в импорте и API
- Updated: Шина (TireProduct) — поле euro_label jsonb + каст EuroLabelCast
- Updated: Импорт каталога из XLSX — RowMapper::map в пайплайне, описания и парсер description_euro_label
- Updated: Публичный API каталога — euro_label в структуре элемента листинга

## [2026-08-21] ingest | Публичный каталог дисков: фасеты + листинг
- Updated: Диск (WheelProduct) — касты decimal:1, byCatalogFilters, публичный каталог
- Updated: Кеширование — wheel-filter/wheel-list, WheelProductObserver, 6 обсерверов + импорт-джобы

## [2026-08-21] ingest | SEO-формулы name/slug при импорте (шины, диски, модели)
- Updated: Шина (TireProduct) — name по TireNameBuilder, slug brand-model-размер-индекс-флаги
- Updated: Диск (WheelProduct) — slug: точки → дефисы
- Updated: Импорт каталога из XLSX — slug модели только из названия, маппинг name/slug

## [2026-08-21] ingest | Происхождение товара (ProductOrigin) и перенос description на модель
- Created: Происхождение товара (ProductOrigin)
- Updated: Шина (TireProduct) — origin_id, description на модели
- Updated: Диск (WheelProduct) — origin_id, description на модели
- Updated: Импорт каталога из XLSX — маппинг origin-колонок и description
