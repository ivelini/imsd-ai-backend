# Удаление supplier_id и справочника Supplier

> Источник: реализация 2026-08-19, состояние кода на момент изменения.

## Решение

Поле `supplier_id` не участвовало в бизнес-логике (цены — по складам и городам, доступность — stocks; фильтры каталога и листинг его не используют) — хранилось только для показа в админке. Удалено полностью:

- Миграции: drop `supplier_id` из `tire_products` и `wheel_products` (dropForeign по колонке — sqlite не умеет по имени), drop таблицы `suppliers`.
- Модели, админские Requests/Resources, импорт (XLSX-колонка `supplier` убрана из column_map, `RowMapper`, `WheelRowProcessor`, `ReferenceResolver::resolveSupplier` удалён).
- Справочник: модель Supplier, CRUD `/catalog/suppliers`, `GetSupplierList`, `EnsureSupplierHasNoProducts`, `SupplierObserver`, блок `suppliers` в `GetReferences` — удалены.

## Контекст решения

Поставщик (завод-изготовитель) ≠ склад (у кого купили). Если потребуется фильтр «по производителю» — вернуть отдельной фичей со своей схемой.
