# Справочник городов: реляция region в элементе

> Источник: реализация 2026-08-20 (состояние кода на момент изменения).

## Контракт

Элемент `GET /api/reference/city` дополнен полем `region`: `{id, name}` — регион города (belongsTo). Остальное без изменений: `{label: name, value: id, slug}`, сортировка по имени, `meta.default` — город по умолчанию из `config/shop.php`.

## Реализация

- `GetCityReference::execute()`: eager-load `region`; в select добавлен `region_id` — без него Eloquent не матчит eager-loaded реляцию (у модели нет FK-атрибута, relation = null — поймано тестом на красном прогоне).
- Новый ресурс `RegionReferenceResource` (`app/Http/Resources/Geo/`): компактный `{id, name}`.
- `CityReferenceResource`: `'region' => whenLoaded('region', ...)` — связанные сущности через relation и вложенный Resource (правило проекта).
- Контракт `#[Response]` обновлён, `public-api.json` переэкспортирован; тест `GetCityReferenceTest` дополнен.
