# Админ-панель на Filament

> Sources: Проект, 2026-09-09
> Raw: [2026-09-09-filament-admin-panel.md](../../raw/project/2026-09-09-filament-admin-panel.md)

## Решение

Админка переезжает с React SPA (отдельный репозиторий, не в проде) на Filament-панель внутри backend: мотив — скорость будущих CRUD-разделов FR (заказы, клиенты, статьи, авто, роли). Admin API (`/api/admin/*`) удаляется вместе с переносом разделов, React замораживается. Волны: 0 каркас → 1 справочники → 2 товары → 3 новые разделы → 4 зачистка (план: `.claude/plans/filament-migration.md`).

## Каркас (волна 0)

- Панель `/panel` (id `admin`), Filament 5.x, `AdminPanelProvider` в `app/Providers/Filament/`.
- Auth: session-guard `admin` (provider `admins` → `App\Models\Auth\Admin`); `Admin implements FilamentUser` — доступ только активным (`is_active`). Sanctum-токены остаются, пока жив admin API.
- Роли: enum `AdminRoleCode` (super-admin, content-manager, order-manager, warehouse-manager), сиды ролей — из enum. Доступ к ресурсам — через policies (`App\Policies\AdminPolicy`: администраторы — только super-admin). spatie не используется.
- Уведомления: нативные Filament (`->databaseNotifications()`; в v4 трейта нет — включение методом панели); `NotificationResource` над таблицей `notifications` с действием `markAsRead`.

## Политика слоёв (контракт для всех ресурсов)

- Чтение — Eloquent + скоупы моделей; Actions чтения API не переносятся.
- Запись доменных сущностей — только через существующие Actions, Preconditions — перед мутацией.
- Прямой Eloquent-записи ресурсами нет: иначе ломаются SEO-slug, пересчёт `catalog_prices`, инвалидация кеша Observer'ами.

## See Also

- [Архитектура приложения: слои и путь запроса](architecture-layers.md)
- [Импорт каталога из XLSX](xlsx-import-pipeline.md)
- [Кеширование](caching.md)
