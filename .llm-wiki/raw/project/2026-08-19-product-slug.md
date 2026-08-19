# Slug у товаров: tire_products и wheel_products

> Источник: реализация 2026-08-19 (план `.claude/plans/product-slug.md`), состояние кода на момент изменения.

## Формула

- **Шина:** `{brand-slug}-{name-slug}-{width}-{profile}-{diameter}[-studded][-runflat]` — studded/runflat только при `true`; null-размеры опускаются.
- **Диск:** `{brand-slug}-{name-slug}-{width}-{diameter}-{et}-{pcd}-{hub_diameter}` — `pcd` «4*98» → «4x98», точка в hub_diameter («58.6») сохраняется.

## Механика

- `Services/Catalog/ProductSlugBuilder` — чистая static-функция (паттерн TireFacetAssembler), unit-тесты без БД.
- `Services/Catalog/ProductSlugService` — БД-обвязка (ADR 0001): slug бренда из brands, уникальность по таблице с суффиксом `-2`, `-3` при коллизии (одинаковые характеристики, разные товары), `ignoreId` при обновлении.
- Вызовы: админские store/update (TireProductController, WheelProductController) — пересчёт при изменении параметров; импорт (UpsertTireProduct, UpsertWheelProduct) — пересчёт при реимпорте по EAN.
- Миграции: `slug` nullable + unique в обеих таблицах. Старые записи без slug — заполняются при следующем сохранении (backfill не делался).
- Slug отдаётся в админских API-ответах (TireProductResource, WheelProductResource).

## Назначение

URL-часть карточки товара. Поле уникально — готово к использованию в маршрутах по slug.
