# Евро-лейбл шины (euro_label) в импорте и API

> Source: изменение кода (миграция, импорт, ресурсы) 2026-08-21
> Collected: 2026-08-21
> Published: 2026-08-21

Изменение: импорт шин и API получают евро-лейбл шины.

## Поле euro_label в TireProduct

- Новая колонка `tire_products.euro_label` (jsonb, nullable).
- Кастомный каст `EuroLabelCast` + value object `EuroLabel` (`app/DTOs/Catalog/Tire/EuroLabel.php`): поля `rollingResistance` (A–G), `wetGrip` (A–G), `noiseEmission` (dB). Каст отдаёт `EuroLabel` из валидного JSON; мусор в БД → null, чтение не падает.
- Формат в API — объект `{rollingResistance, wetGrip, noiseEmission}` (JsonSerializable).

## Импорт: description_euro_label

- XLSX-колонка `description_euro_label` уже была в `column_map` (`config/tire_import.php`); значение — строка «D/C/71» (rolling/wet/noise, разделитель `/`).
- Новый чистый метод `RowMapper::parseEuroLabel(?string): ?EuroLabel`: 3 сегмента через `/`, буквы A–G (нормализуются в верхний регистр), шум — 2–3 цифры; невалидно → null (мусор из XLSX не попадает в БД, строка импорта не падает).
- В DTO `ImportTireRow` поле `euroLabel: ?EuroLabel`; из JSON-`description` ключ `euro_label` убран — одно место истины.

## Починка импорта описаний (попутный баг)

- `TireRowProcessor` переведён с `ImportTireRow::fromArray()` (не собирал `descriptions` из плоских ключей — в `description` уходило `"[]"`, описания молча терялись) на `RowMapper::map()`. Описания vendor/default/manufacture_country/manufacture_year теперь импортируются.
- Пустые описания → `description` = null, а не `"[]"` (JSON-массив не соответствовал формату объекта).
- Удалён мёртвый `ImportTireRow::fromArray` (единственное использование — TireRowProcessor).

## API

- Admin-ресурс `TireProductResource` и public-листинг `TireListItemResource` отдают `euro_label` объектом (null при отсутствии).

## Кеш листинга

Формат payload листинга изменился (новое поле) — ключ кеша `tire-list:v5` НЕ инкрементирован до v6: инвалидация Observer-ами (saved/deleted) сбрасывает варианты по индексу ключей; TTL короткий. При плановой смене payload — версия ключа.
