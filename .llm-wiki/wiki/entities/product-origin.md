# Происхождение товара (ProductOrigin)

> Sources: Проект, 2026-08-21 (план product-origin-import + код)
> Raw: [2026-08-21-product-origin-description-move.md](../../raw/project/2026-08-21-product-origin-description-move.md)

## Overview

`product_origins` — справочник происхождения товара: производитель, страна производства, год производства. Одна запись — уникальный триплет (vendor, manufacture_country, manufacture_year). Каждая из трёх колонок — jsonb `{badge, description}` (value object `OriginInfo` + каст `OriginInfoCast`, по образцу EuroLabel). Связан с товарами: `tire_products.origin_id` и `wheel_products.origin_id` (FK nullable, ON DELETE SET NULL, belongsTo `origin()`).

## Заполнение при импорте

Из опциональных колонок XLSX `origin_vendor`, `origin_manufacture_country`, `origin_manufacture_year`. Формат значения: `##Badge## <p>описание</p>` → badge = текст между `##`, description = остаток с HTML-тегами; мусор/пусто → null (чистый `OriginParser`, ADR 0001). БД-обвязка — `OriginResolver::resolve(3×?OriginInfo)` → firstOrCreate по триплету; все три null → null, запись не создаётся.

## Механика отдельная

Колонок нет в файле → флаг `origin_present` (ключ отсутствует в данных строки) → `origin_id` не трогается при реимпорте; основной импорт не падает. Файлы с частичным триплетом (например, wheels.xlsx — только `origin_vendor`) создают запись с null-полями.

## See Also

- [Шина (TireProduct)](tire-product.md)
- [Диск (WheelProduct)](wheel-product.md)
- [Импорт каталога из XLSX](../concepts/xlsx-import-pipeline.md)
