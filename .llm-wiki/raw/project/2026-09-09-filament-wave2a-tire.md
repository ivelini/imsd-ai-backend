# Волна 2a миграции на Filament: TireProductResource

> Source: План .claude/plans/filament-migration.md + код (TireProductResource, TireDataComposer)
> Collected: 2026-09-09
> Published: 2026-09-09

## Реализовано

`App\Filament\Resources\TireProducts\TireProductResource`:
- форма по контракту TireProductRequest: brand/model (Select relationship; model_id — только type=tire через `Rule::exists('product_models','id')->where('type','tire')` + modifyQueryUsing), season (Select Season), name (опц., helper «пусто — из модели»), ean (unique ignoreRecord), country, размеры (width 100–400, profile 20–100, diameter, load_index, speed_index, year 2000–2030), тумблеры (is_studded/is_runflat/is_xl/is_published/is_bestseller/is_new — без required). Компоновка — Section/Grid из `Filament\Schemas\Components` (v5).
- таблица: name, brand/model (скрываемые), ean (поиск), season-badge, размеры, год, статусы; фильтры brand/season/is_published; DeleteAction в recordActions.
- **TireDataComposer** (Services/Catalog) — подготовка данных к сохранению: name из модели (если пуст, DisplayNameResolver) + SEO-slug (ProductSlugService, ADR 0006). Единая реализация для хуков страниц Create/Edit (`mutateFormDataBeforeSave`) и ещё живого TireProductController (приватный slugFrom удалён). В Filament-хуках сервис берётся контейнером (UI-слой).
- slug/euro_label/origin_id в форме не редактируются: slug генерируется, euro_label/origin — из импорта (чтение).

## Тесты

TireResourceTest (8): create генерирует name+slug (nokian-hakka-215-60-r16), отклонение wheel-модели, ean unique, season, пересчёт slug при смене размеров, сохранение при неизменных, delete, поиск по EAN. Красный 8 failed → зелёный; полный сьют 473 passed.

Граница: снос Tire API — после переноса Wheel и Image (ImageController обслуживает оба товара).
