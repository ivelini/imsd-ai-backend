# Product origin (происхождение товара) и перенос description на модель

> Source: План .claude/plans/product-origin-import.md + код (OriginParser, OriginResolver, ProductOrigin, миграции)
> Collected: 2026-08-21
> Published: 2026-08-21

## product_origins

Новая таблица `product_origins` (id, vendor, manufacture_country, manufacture_year): каждая колонка — jsonb `{badge, description}`. Заполняется при импорте из опциональных колонок XLSX `origin_vendor`, `origin_manufacture_country`, `origin_manufacture_year`. Формат значения: `##Badge## <p>описание</p>` → badge = текст между `##`, description = остаток с HTML-тегами (мусор/пусто → null, в БД не попадает).

- Уникальность — UNIQUE-индекс по триплету (vendor, manufacture_country, manufacture_year); колонки nullable, дубли при NULL держит firstOrCreate.
- tire_products.origin_id / wheel_products.origin_id — FK nullable, ON DELETE SET NULL; связи belongsTo `origin()`.
- Чистая функция парсинга — OriginParser (Services/Import, ADR 0001); БД-обвязка — OriginResolver (firstOrCreate, json-сериализация: Eloquent-каст set() не применяется к where-условиям firstOrCreate).
- Каст — OriginInfoCast (по образцу EuroLabelCast), value object — OriginInfo.
- Механика отдельная: колонок нет в файле → флаг origin_present=false → origin_id не трогается при реимпорте; основной импорт не падает.

## Перенос description

Колонки `description` удалены из tire_products и wheel_products. Значение из единой колонки XLSX `description` (text) пишется в `product_models.description` при каждом импорте строки с этой колонкой (обновление модели, последний выигрывает); колонки нет в файле → description модели не трогается (флаг description_present).

- Старые колонки описаний (vendor_description, description_default, description_manufacture_country/year) больше не читаются; DescriptionBuilder удалён; column_map очищен.
- description_euro_label остаётся отдельной механикой (евро-лейбл на товаре).
- Поле description убрано из админ-ресурсов и FormRequest шин/дисков (контракт), у ProductModel остаётся.
- В актуальных файлах: wheels.xlsx имеет колонку description; tires.xlsx пока нет (есть description_default) — шины начнут писать описание при появлении колонки.
