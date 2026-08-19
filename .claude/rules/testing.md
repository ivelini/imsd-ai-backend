# Тестирование

## Unit vs Feature

| Unit | Feature |
|------|---------|
| Чистые классы — без БД, HTTP, файловой системы | HTTP-запросы, БД, полный цикл (Request → Action → Response) |
| Тест = «вход → выход», без `DatabaseMigrations`, без моков БД | `RefreshDatabase` + `actingAs` |
| Никаких фасадов, `Auth::`, `Cache::`, `Storage::` | Взаимодействие с реальной БД, API-ответы |
| Пример: чистая функция, вычисление, DTO-трансформация | Пример: `BrandTest`, `CityTest`, `GetTireDimensionsTest` |

**Если Action ходит в БД — его тест лежит в `tests/Feature/`, не в `tests/Unit/`.**
Даже если тестируется только Action без HTTP — наличие `RefreshDatabase` означает Feature-тест.

## Именование

- Класс: `{Сущность}Test` или `{Action}Test`
- Метод: `test_{method}_{scenario}` — snake_case
- Пример: `test_store_validates_required_fields`, `test_execute_respects_season_filter`

## Структура тестового класса

1. После `RefreshDatabase` — `setUp()` с подготовкой данных (фабрики)
2. Каждый `test_*` — независим, не полагается на состояние из другого теста
3. Использовать `factory()->create()` для данных, не сырые `insert()`
