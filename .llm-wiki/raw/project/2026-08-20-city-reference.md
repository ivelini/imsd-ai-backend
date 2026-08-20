# Справочник городов GET /api/reference/city

> Источник: реализация 2026-08-20 (состояние кода на момент изменения).

## Контракт

`GET /api/reference/city` — публичный справочник всех городов для дропдаунов. Без query-параметров. Ответ: `{data: [{label, value, slug}]}` — label = name, value = id, slug = slug города. Сортировка — по имени города (алфавит).

## Реализация

- Action `GetCityReference` (`app/Actions/Geo/`): `City::orderBy('name')->get(['id', 'name', 'slug'])` — возвращает Collection моделей (не массив) для ресурса.
- Controller `GetCityReferenceController` (`app/Http/Controllers/Catalog/`): single-action, вывод через `CityReferenceResource::collection()` (`app/Http/Resources/Geo/`), Scramble-документация через `#[Response]`.
- Формат `{label, value, slug}` — конвенция «фасетных» элементов справочников публичного API (как brand/country в фильтре), slug нужен для URL карточек.
- Кеш не используется: один select без вычислений (правило «не добавлять кеш молча»).
