# Переход админки на Filament: волна 0 (каркас панели)

> Source: План .claude/plans/filament-migration.md + код (AdminPanelProvider, AdminResource, NotificationResource, AdminPolicy)
> Collected: 2026-09-09
> Published: 2026-09-09

Решение (согласовано с владельцем, 2026-09-09): React-админка (отдельный репозиторий ../admin, ~6.6k строк TS, не в проде) замораживается; админка переезжает в Filament-панель внутри backend. Мотив — скорость будущих разделов FR (2/3 — CRUD: заказы, клиенты, статьи, авто, роли). Admin API (`/api/admin/*`) удаляется вместе с переносом разделов.

Волны: 0 каркас → 1 справочники → 2 товары → 3 новые разделы FR → 4 зачистка.

## Волна 0 (реализовано)

- Filament 4.13.1; панель `/panel` (id `admin`), `AdminPanelProvider` в `app/Providers/Filament/`.
- Guard `admin` (session, provider `admins` → `App\Models\Auth\Admin`) добавлен в `config/auth.php`; Sanctum-токены у Admin остаются, пока жив admin API.
- `Admin implements FilamentUser`: `canAccessPanel()` — доступ только активным (`is_active`).
- Роли: enum `App\Enums\Auth\AdminRoleCode` (super-admin, content-manager, order-manager, warehouse-manager); `AdminSeeder` переведён на enum; политика `App\Policies\AdminPolicy` — управление администраторами только у super-admin (удаление себя запрещено). spatie/laravel-permission не вводится (согласовано).
- Уведомления: нативные Filament — `->databaseNotifications()` на панели (в v4 трейта `HasDatabaseNotifications` нет, включение методом панели); `NotificationResource` над таблицей `notifications` с table action `markAsRead` (маршрут `/panel/notifications`).
- `AdminResource` (`/panel/admins`): форма — имя/email/пароль (дегидратация только при заполнении; required на create), роль — Select из `admin_roles`, `is_active` — Toggle.

## Политика слоёв в Filament (контракт на все волны)

- Чтение — Eloquent + скоупы моделей; чтение-Actions API не переносятся.
- Запись доменных сущностей — только через существующие Actions (`handleRecordCreation`/хуки), не напрямую Eloquent; Preconditions — перед мутацией.
- Иначе ломаются инварианты: SEO-slug, пересчёт `catalog_prices`, инвалидация кеша Observer'ами.

## Тесты волны 0

`tests/Feature/Admin/Auth/AuthPanelTest` (гость → /panel/login; активный → 200; неактивный → 403), `AdminRolePolicyTest` (менеджер → 403 на /panel/admins; super-admin → 200), `NotificationResourceTest` (markAsRead меняет read_at). Красный прогон (6 failed: guard/маршруты не существуют) → зелёный (6 passed); полный сьют 461 passed.
