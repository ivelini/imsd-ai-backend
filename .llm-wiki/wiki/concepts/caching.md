# Кеширование: Cache Service до Action, Observer-инвалидация

> Sources: Проект (CLAUDE.md), 2026-08-19
> Raw: [architecture.md](../../raw/project/architecture.md)

## Overview

Кеш размещается **до Action**, в контроллере: `Cache Service → remember(fn () → Action::execute())`. Action не знает о кеше — это инфраструктурная забота контроллера. В кеш кладутся только массивы/скаляры, никогда Eloquent-модели (после десериализации из Redis связи не загружены — `null` или N+1).

## Компоненты

1. **Cache Service** (`Services/Cache/{Domain}/XxxCacheService`) — тонкая обёртка над Repository: `remember(callable): array` + `forget(): void`.
2. **Controller** — оркестратор: `$this->cache->remember(fn () => $this->action->execute())`.
3. **Observer** (`app/Observers/`) — инвалидация: `saved()` / `deleted()` → `$cache->forget()`. Регистрация в `AppServiceProvider::boot()`.
4. **TTL** — в `config/cache_ttl.php`, один ключ — один параметр, без инлайн-литералов.

## Правила

- Если Action делает несколько запросов к БД — кеш до Action в контроллере.
- Перед `put()` данные сериализованы (Resource / `toArray()`).
- Не добавлять кеш молча — сначала предложить.
- Особый случай: таблицы, пишущиеся `upsert()` без Eloquent-событий (`catalog_prices`), — инвалидация только явным `forget()` из импорт-джобов, Observer не сработает.

## Ключ

Кеш фильтра `TireFilterValuesCacheService` включает hash query-фильтров — с разными фильтрами разные записи.

## See Also

- [Публичный API каталога](public-catalog-filter-api.md)
- [Архитектура приложения](architecture-layers.md)
