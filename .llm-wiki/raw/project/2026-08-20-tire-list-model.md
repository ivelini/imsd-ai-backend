# Модель товара в элементе листинга GET /api/catalog/tires

> Источник: реализация 2026-08-20 (состояние кода на момент изменения).

## Контракт

Элемент списка `GET /api/catalog/tires` дополнен полем `model`: `{id, name, slug}` — модель товара (ProductModel), если у шины задан `model_id`; `null` — если модели нет.

## Реализация

- Action `GetTireList` (`app/Actions/Catalog/Tire/`): в eager-load добавлена реляция `model` (belongsTo, как `brand`).
- Новый ресурс `ProductModelReferenceResource` (`app/Http/Resources/Catalog/`): компактный вывод ровно `{id, name, slug}` — админский `ProductModelBriefResource` не переиспользован (лишнее поле `image`).
- `TireListItemResource`: `'model' => whenLoaded('model', ...)` — поле появляется только при загруженной реляции.
- Кеш: смена формата payload → версия ключа `tire-list:v3` (правило: версионировать ключ при смене payload; старые `v2` протухают по TTL).
- Scramble: `#[Response]`-аннотация контроллера дополнена `model: array{id, name, slug}|null`; `documentations/scramble/public-api.json` перегенерирован через `scramble:export`.
