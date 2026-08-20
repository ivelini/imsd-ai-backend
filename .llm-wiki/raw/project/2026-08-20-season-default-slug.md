# Публичный API каталога: season-объект, meta.default, slug из характеристик

> Источник: реализация 2026-08-20 (состояние кода на момент изменения).

## 1. season в листинге — объект {label, value}

Элемент `GET /api/catalog/tires`: `season` из строки превращён в объект `{label, value}` — label = русское название (аксессор `season_label`), value = значение из БД (winter/summer/all-season). Формат совпадает с фасетом season в `/api/reference/filter/tire` (TireFacetAssembler::season). Колонка `season` в БД NOT NULL.

Кеш листинга: смена формата payload → версия ключа `tire-list:v4`.

## 2. /api/reference/city — default в meta

Ответ дополнен `meta.default`: `{label, value}` — город по умолчанию из `config/shop.php` (`default_city` = Челябинск), `null` — города нет в БД. Реализация: `GetCityReference::defaultCity(name)` (Action), DI `$defaultCityName` через `CatalogServiceProvider` (как у GetTireListController).

## 3. Slug шины — только характеристики

Формула `ProductSlugBuilder::tire`: `{width}-{profile}-{diameter}[-studded][-runflat]` — brand и name убраны (было `{brand-slug}-{name}-...`). Причина: slug стабилен при смене названий бренда/модели, URL карточки не ломается при реимпорте. Параметры brandId/name убраны из `ProductSlugService::tire` и обоих вызовов (`UpsertTireProduct`, `TireProductController::slugFrom`). Формула диска не менялась. Старые slug в БД остаются до пересохранения/реимпорта.
