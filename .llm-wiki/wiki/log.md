# Wiki Log

## [2026-09-10] ingest | Волна 2d (часть 3): акции на панели + применение скидок
- Updated: Админ-панель на Filament (PromotionResource, снос API акций, остаток admin API)
- Updated: Каталог: ценообразование (скидка в price, base_price, PromotionMatcher, promotions:sync)

## [2026-09-10] ingest | Волна 2d (часть 1): страница импорта + снос Import API
- Updated: Админ-панель на Filament (ImportProducts, снос Import API, адрес уведомления)
- Updated: Импорт каталога из XLSX (точка входа — страница панели)

## [2026-09-10] ingest | Волна 2c: изображения товара + снос Image API
- Updated: Админ-панель на Filament (ImagesRelationManager, PanelAction, фикс DeleteImage, снос Image API)

## [2026-09-10] ingest | Волна 2b: WheelProductResource + снос admin API товаров
- Updated: Админ-панель на Filament (WheelDataComposer, снос Tire/Wheel/Products API, состав остатка admin API)
- Updated: Архитектура приложения: слои и путь запроса (аутентификация админов — панель как основной вход)
- Updated: Импорт каталога из XLSX (статус точки входа, путь /api/admin/catalog/import/tires)

## [2026-09-09] ingest | Волна 2a: TireProductResource
- Updated: Админ-панель на Filament (TireDataComposer, волна 2)

## [2026-09-09] ingest | Волна 1c: ProductModel (волна 1 закрыта)
- Updated: Админ-панель на Filament (ProductModelObserver, составной unique slug, итог волны 1)

## [2026-09-09] ingest | Волна 1b: партия справочников на Filament
- Updated: Админ-панель на Filament (7 ресурсов, read-only Cities/Countries, снос 7 API-срезов)

## [2026-09-09] ingest | Волна 1a: BrandResource + снос API брендов
- Updated: Админ-панель на Filament (волна 1, политика delete через Precondition)

## [2026-09-09] ingest | Админ-панель на Filament (апгрейд 4.13.1 → 5.8.1)
- Updated: Админ-панель на Filament (Filament 5.x)

## [2026-09-09] ingest | Админ-панель на Filament
- Created: Админ-панель на Filament
- Updated: Архитектура приложения: слои и путь запроса (аутентификация админов: session-guard + /panel, AdminRoleCode)

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

## [2026-08-21] ingest | Origin в публичных листингах /api/catalog/tires и /api/catalog/wheels
- Updated: Публичный API каталога — origin в структуре элемента
- Updated: Происхождение товара (ProductOrigin) — вывод в API, версии кеша

## [2026-09-10] ingest | Админ-панель на Filament: навигация-кластеры
- Updated: Архитектура приложения — список разделов панели и остатка admin API
- Updated: Импорт каталога из XLSX — URL страницы импорта

## [2026-09-10] ingest | Русификация панели: env_file и пересоздание контейнеров
- Updated: Эксплуатация — раздел «Окружение» + runbook
