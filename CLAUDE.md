Команды — в `Makefile`. Для artisan: `docker compose exec app php /var/www/artisan ...`

## Архитектура

**Тип:** монолит (Laravel). E-commerce — шины и диски.

### Полный путь запроса

```
Middleware → FormRequest → Controller → Cache Service? → Preconditions → Action → Response
```

| Слой | Что делает | Отвечает за |
|------|-----------|-------------|
| **FormRequest** | Валидация входящих данных (`rules()`) | Формат полей: типы, `exists`, `unique` |
| **Controller** | Оркестрация: валидные данные → кеш? → Preconditions → Action → ответ | Последовательность шагов, HTTP-статус |
| **Cache Service** | `remember(fn → Action)` — прозрачный слой: hit → вернуть, miss → Action → put → вернуть | Кеш до Action. Action не знает о кеше |
| **Preconditions** | Бизнес-правила: `ensure*()` → `DomainException` | «Можно ли продолжать?» — существование, права, лимиты |
| **Action** | Бизнес-логика: `execute(DTO): DTO\|void\|array`. Никаких проверок, никакого HTTP, никакого кеша | Единственная операция: создать, посчитать, выдать токен |
| **Response** | Сериализация в JSON | Формат ответа |

### Ключевые принципы

**Action не проверяет данные.** Если данные попали в Action — они уже прошли FormRequest и Preconditions.

```
❌ Action::execute(DTO) { if (...) throw ...; }  // проверка внутри
✅ Controller { $precondition->ensure(); $action->execute(); }  // проверка до
```

**Action не знает о кеше.** Кеш — инфраструктурная забота контроллера. Action вызывается только при промахе.

```
❌ Action::execute() { if (cached) return ...; $data = ...; cache->put(...); }
✅ Controller { $this->cache->remember(fn () => $this->action->execute()); }
```

### Примеры

**Простой CRUD (нет бизнес-правил):**
```
FormRequest → Controller::index() → Action (query + paginate) → Resource
FormRequest → Controller::store() → Model::create() → JsonResponse 201
```

**С бизнес-правилами (BrandController::destroy):**
```
Controller::destroy()
  → $brand = Brand::withCount(...)->findOrFail($id)
  → $this->ensureBrandHasNoProducts->ensure($brand)   // Precondition
  → $brand->delete()                                    // Action не нужен — тривиально
  → JsonResponse 204
```

**Сложный сценарий (LoginController):**
```
LoginFormRequest
  → Controller::__invoke()
    → $this->ensureAdminExists->ensure($email)         // возвращает Admin
    → $this->ensurePasswordIsValid->ensure(...)        // проверяет пароль
    → $this->ensureAdminIsActive->ensure($admin)        // проверяет активность
    → $this->loginAdmin->execute($admin)                // только createToken
  → LoginResource
```

**С кешем (GetReferencesController):**
```
Controller::__invoke()
  → $this->cache->remember(
      fn () => $this->getReferences->execute()          // Action только при промахе
    )
  → JsonResponse
```

### Response — что выбрать

| Тип | Когда |
|-----|-------|
| `JsonResponse` | Action вернул массив, контроллер обернул в `['data' => ...]`. Нет трансформации |
| `JsonSerializable` DTO | Простой 1:1 mapping полей, без вложенных связей |
| Resource | Сложная трансформация (`code`-путь, вложенные структуры, `whenLoaded`) |

Не создавай DTO/Resource ради `['data' => $x]`.

### Домены

Директории Models: `Admin/`, `Catalog/`, `Cart/`, `Order/`, `Geo/`, `Warehouse/`, `Vehicle/`, `Article/`, `Content/`, `Common/`, `System/`.

Такая же структура — в `Actions/`, `Preconditions/`, `DTOs/`, `Http/Controllers/`, `Http/Requests/`, `Http/Resources/`, `Enums/`, `Services/`.

**Морф-мапа (`AppServiceProvider`):** `tire → Tire`, `wheel → Wheel`, `article → Article`.

**Enums:** `ProductType`, `WheelType`, `SpecType`, `PromotionType`, `DiscountType`, `OrderState`, `WeekDay` — в `app/Enums/`.

**API:** `/api/admin` — `auth:sanctum`, `/api` — публичные + клиентские.

---

## Формат ответов

- Анализируй контекст перед ответом: к какому домену относится вопрос, какой слой затронут
- Если вопрос размытый — уточни перед написанием кода
- Показывай diff/план, а не пересказывай изменения словами

---

## Правила

### Слои

1. **Не клади бизнес-логику в контроллер.** Контроллер только оркестрирует (см. таблицу выше).
   - Для CRUD — `{Сущность}Controller`, для одного действия — `{Действие}{Сущность}Controller::__invoke`.

2. **Action не должен знать об HTTP.** `execute(DTO): DTO`. Action — `final readonly class`. Никаких `Request`, `Response`, `JsonResource`.
   - Для тривиального CRUD в админке можно `array<string, mixed>` вместо DTO. Не злоупотребляй.
   - Не вызывай другие Action из Action. Исключение: одна транзакция — через инжектированный `Connection`, без фасада `DB::`.
   - **Разделяй чтение и запись.** Action либо читает, либо пишет. Исключение: `lockForUpdate` + `increment` в одном Action.

3. **Проверяй бизнес-правила в Preconditions.** Метод `ensure*()`, бросает `DomainException` с HTTP-кодом. Если ≥3 проверок — цепочка handler-ов через `setNext()`.

4. **Модель — только Eloquent.** `$table`, `$fillable`, `$casts`, отношения, скоупы, аксессоры.
   - У каждой модели, енума и каста — краткий phpdoc `/** Для чего. */` над классом. Одна строка, без шаблонов.
   - `$timestamps` не отключай — они включены по умолчанию.
   - **Scopes для повторяющихся фильтров.** Выноси в scope на модели или метод кастомного Builder.
   - **Кастомный Builder** — `@extends Builder<Model>` — сохраняет generic-тип для PHPStan.

5. **DTO — `final readonly class`.** Никакой логики, только `fromRequest` / `toArray` / `JsonSerializable`.

6. **Сервис не принимает DTO.** Только примитивы или модели. Исключение: если метод сервиса — чистая функция над скалярами.

7. **Одна миграция — одна таблица** (исключение: pivot). Данные — только в сидерах.

8. **Resource — только маппинг полей.** Никаких вычислений, запросов к БД, вызовов сервисов.
   ```
   // ❌ Resource сам считает
   'delivery' => $this->computeDelivery($tire, $cityId)

   // ✅ Controller подготовил, Resource выводит
   'delivery' => $this->whenLoaded('delivery')
   ```
   Если нужно вычислить — делай в Controller/Service до передачи в Resource. Используй `setRelation()` или прямые свойства модели.

9. **Enum — только Backed Enum.** Никаких строковых констант. Каждый enum — отдельный файл в `app/Enums/`.

10. **Валидируй только в `FormRequest`.** Бизнес-ошибки — `DomainException` с HTTP-кодом.

11. **Конфиги — в `config/*.php`.** Один параметр — одно место. Без fallback-ов (`config('key', default)`).

### Кеширование

Если Action выполняет несколько запросов к БД — кеш размещается **до Action**, в контроллере. Action не знает о кеше.

```
Controller
  ├── $this->cache->remember(fn () => $this->action->execute())  ← кеш до Action
  │     ├── hit  → вернуть из кеша (Action не вызывается)
  │     └── miss → Action::execute() → put → вернуть
  └── response
```

Компоненты:

1. **Cache Service** (`Services/Cache/{Domain}/XxxCacheService`) — тонкая обёртка над `Repository`: `remember(callable): array` + `forget(): void`.
2. **Controller** — оркестратор: вызывает `remember(fn () => $this->action->execute())`.
3. **Observer** (`app/Observers/`) — инвалидация: `saved()`/`deleted()` → `$cache->forget()`. Регистрируется в `AppServiceProvider::boot()` через `Model::observe()`.
4. **TTL** — в `config/cache_ttl.php`, один ключ — один параметр.
5. **В кеш — только массивы/скаляры**, не Eloquent-модели. Перед `put()` данные сериализованы через Resource или `toArray()`.

Не добавляй кеш молча — сначала предложи.

### Именование

| Сущность | Пример |
|---|---|
| Action (чтение) | `GetWarehouseStock::execute(DTO): DTO` |
| Action (запись) | `PopulateCatalogPrices::execute(DTO): void` |
| Preconditions | `EnsureBrandHasNoProducts::ensure(Brand): void` |
| DTO | `GetWarehouseStockInput`, `WarehouseStockRow` (в namespace домена) |
| Enum | `ProductType`, `WeekDay` (Backed Enum) |
| Controller (single-action) | `WarehouseStockController::__invoke` |
| Controller (CRUD) | `BrandController` (единственное число) |

### Сериализация в очередь

- **В Job и batch-коллбэках сериализуй только ID модели, не модель целиком.** Eloquent-модель тянет весь граф атрибутов и отношений — `laravel/serializable-closure` рекурсивно обходит его, вызывая переполнение памяти.
  ```php
  // ❌ Плохо
  public function __construct(readonly Order $order) {}
  Bus::batch($jobs)->finally(function () use ($product) { ... })->dispatch();

  // ✅ Хорошо
  public function __construct(readonly int $orderId) {}
  $productId = $product->id;
  Bus::batch($jobs)->finally(function () use ($productId) { ... })->dispatch();
  ```
- Исключение: константные скаляры (статусы, флаги, мелкие строки) — можно, если не растут с данными.

---

## Работа с кодом

- Перед планированием или редактированием файла — читай `.ai/rules/index.md` и правила под глоб файла
- Перед изменениями кода проверяй актуальную документацию через `search-docs` (версии пакетов проекта)
- Перед коммитом: `make lint-fix` + `make phpstan` + `make test`
- Статический анализ: `make phpstan` (level 6, config: `phpstan.neon`)

---

## Решения (ADR)

Правила ведения — `documentations/adr/README.md` (создаётся при первом ADR).

| № | Решение | Статус | Дата |
|---|---|---|---|
| 0001 | Чистые алгоритмы в Services — БД-обвязка снаружи | Accepted | 2026-08-07 |
| 0002 | catalog_prices — полная цена города и единый матчинг наценок | Accepted | 2026-08-13 |

## Документация

| Слой | Где | Что отвечает |
|---|---|---|
| Эксплуатация | `documentations/operations.md` | env, очереди/импорты, ручные сценарии |
| Схема БД | `documentations/db-schema.md` | таблицы и связи |
| Архитектура | `documentations/architecture.md` | устройство системы |
| API | Scramble → `/docs/api` (фронт, UI) + `/docs/admin` (админка, UI) + `documentations/scramble/public-api.json` + `admin-api.json` | контракты потребителей |
| ТЗ | `documentations/tz/`, `documentations/fr/` | функциональные требования |

<!-- setup-llm-wiki:start -->
## LLM Wiki
- Индекс знаний проекта: .llm-wiki/wiki/index.md
- Wiki обновляется только через скил .claude/skills/llm-wiki/SKILL.md (Ingest workflow)
- Правила синхронизации: .claude/rules/wiki-sync.md, базовые правила: .claude/rules/base.md
- Команды: /ship-wiki — коммит с обязательным обновлением wiki; /ship — без wiki
<!-- setup-llm-wiki:end -->
