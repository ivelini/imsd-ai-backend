# Учёт города в фасетном фильтре шин + починка инвалидации кеша

> Источник: реализация 2026-08-19 (план `.claude/plans/tire-filter-city.md`), состояние кода на момент изменения.

## Изменение контракта

`GET /api/reference/filter/tire` теперь принимает `city_id` (`nullable`, `integer`, `exists:cities,id`). Без параметра — город по умолчанию из `config/shop.php` (Челябинск), обратная совместимость сохранена.

- Контроллер: явный `city_id` → `City::findOrFail` (страховка от удаления города после exists-валидации), иначе `EnsureCityExists::ensure(defaultCityName)`.
- `city_id` исключается из массива фильтров перед передачей в Action (`$request->safe()->except('city_id')`).
- Фасеты delivery и price считаются по `catalog_prices` выбранного города (Action уже принимал `cityId`).

## Кеш

`TireFilterValuesCacheService`:
- Ключ: `tire-filter:{cityId|null→'default'}:{md5(filters)}` — город и фильтры в ключе.
- Инвалидация починена: `remember()` регистрирует каждый ключ в индексе `tire-filter:index`; `forget()` удаляет все ключи из индекса + сам индекс. Драйвер кеша — database, теги недоступны.
- Известная гонка: параллельные `remember()` могут потерять ключ в индексе — такой вариант устареет по TTL, приемлемо.
