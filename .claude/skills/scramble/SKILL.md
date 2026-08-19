# /scramble — документирование API

## Задача навыка

Аудит и документирование API-эндпоинтов через Scramble (`dedoc/scramble`). Scramble генерирует OpenAPI автоматически статическим анализом кода — без docblock-аннотаций. Аннотации нужны только для описаний, примеров и кастомизации.

Пакет: `dedoc/scramble` ^0.13. Генерация: запуск `/docs/api` (UI) или `/docs/api.json` (OpenAPI JSON) в браузере. Экспорт: `php artisan scramble:export` → `api.json`.

Документация: https://scramble.dedoc.co

## Справочник правил

### Группировка эндпоинтов

По умолчанию — по имени контроллера. Переопределение:

```php
use Dedoc\Scramble\Attributes\Group;

#[Group('Каталог — шины', description: 'Управление товарами шин', weight: 0)]
final readonly class TireProductController
```

- `weight` — порядок (меньше = выше). Равные weight сортируются по имени (`SORT_LOCALE_STRING`).
- `@tags` в PHPDoc контроллера — несколько тегов: `/** @tags Каталог, Шины, API */` (Stoplight Elements показывает только первый).
- **Документация:** https://scramble.dedoc.co/usage/request → «Grouping & Sorting Endpoints»

### Заголовок и описание эндпоинта

```php
/**
 * Список шин.
 *
 * Возвращает пагинированный список с фильтрацией и доставкой.
 */
public function index(TireProductIndexRequest $request): AnonymousResourceCollection
```

- Первая строка докблока = `summary` (title). Остальное (после пустой строки) = `description` (Markdown).
- Без докблока — title пустой, description из кода не извлекается.
- **Документация:** https://scramble.dedoc.co/usage/request → «Title & Description»

### Аутентификация

Автоматически из middleware через `config/scramble.php`:

```php
'security_strategy' => \Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy::class,
```

- `auth:sanctum` → bearer-схема для всех маршрутов с этим middleware. Публичные маршруты — `security: []`.
- Защищённые маршруты получают 401-ответ автоматически.
- `@unauthenticated` в докблоке метода — снимает security для конкретного эндпоинта.
- **Документация:** https://scramble.dedoc.co/usage/authentication

### Параметры запроса

**Автоматически** из FormRequest или `$request->validate()`:
- GET/DELETE/HEAD → query-параметры. Остальные методы → body.
- `@query` в PHPDoc над полем — принудительно в query для не-GET запросов.
- `rules()` разбираются: `required`, `integer`, `string`, `in`/`Rule::in`, `exists`, `min`, `max`, `boolean`, `email`, `uuid`, `file`, `array`, `nullable` и др.
- `exists` — запрашивает БД для типа колонки. `Rule::in` → enum в доке.

**Описания через PHPDoc:**

```php
// В rules() FormRequest:
/**
 * Город для расчёта доставки.
 * @example 1
 */
'city_id' => ['nullable', 'integer', 'exists:cities,id'],

// Или в контроллере:
/**
 * Количество элементов на странице.
 * @default 50
 */
$perPage = $request->integer('per_page', 50);
```

**Ручные атрибуты** (на методе контроллера):

```php
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\CookieParameter;

#[QueryParameter('per_page', description: 'Элементов на странице.', type: 'int', default: 10, example: 20)]
#[BodyParameter('title', description: 'Название.', type: 'string', example: 'Модель A')]
#[PathParameter('id', description: 'ID товара.', type: 'integer', format: 'int64')]
```

- Параметры мержатся с авто-выводом; ручные значения переопределяют авто.
- `infer: false` — полностью отключает авто-вывод для параметра.
- `$required`, `$deprecated`, `$format` (uuid/email/date-time) — опциональны.

**Скрытие параметра:**

```php
/** @ignoreParam */
'debug' => ['boolean'],
// или атрибут:
#[IgnoreParam('debug', 'query')]
```

- **Документация:** https://scramble.dedoc.co/usage/request

### Ответы

**Автоматически** из return-типа контроллера:

| Возвращаемый тип | Как документируется |
|---|---|
| `TireProductResource` | Анализ `toArray()` — все поля, вложенные связи, `whenLoaded` |
| `AnonymousResourceCollection` | Коллекция: `data[]` + структура Resource |
| `LengthAwarePaginator` (через Resource) | `data`, `links`, `meta` |
| `JsonResponse` (201, 204) | Статус из кода |
| `Model` | Касты + колонки БД (миграции должны быть применены) |
| `array` / `response()->json(...)` | Типы из структуры массива |

**Модель для Resource:** Scramble ищет в `App\Models` по singular-имени ресурса. Если не находит — все поля `string`. Явно:

```php
/** @property TireProduct $resource */
class TireProductResource extends JsonResource
```

**PHPDoc над полями `toArray()`:**

```php
/**
 * Название склада.
 * @example «Основной склад»
 * @format string
 * @default null
 */
'warehouse' => $this->warehouse?->name,
```

**Ошибки — автоматически:**

| Триггер | Статус |
|---|---|
| `FormRequest` / `$request->validate()` | 422 |
| `$this->authorize()` / Gate | 403 |
| Model binding (`findOrFail`) | 404 |
| `abort()`, `abort_if()`, `abort_unless()` | per code |
| `@throws` в докблоке | varies |
| `AuthenticationException` | 401 |
| `AuthorizationException` | 403 |

**Ручные ответы:**

```php
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Dedoc\Scramble\Attributes\Header;

#[ScrambleResponse(201, 'Товар создан.', type: 'array{id: int}')]
#[Header('X-Rate-Limit', 'Лимит запросов.', type: 'int', status: 200)]
public function store(TireProductRequest $request): JsonResponse
```

Можно кастомизировать inferred-ответ, не добавляя новый:
```php
#[ScrambleResponse(description: 'Список с фильтрами', status: 200)]
```

**Исключение ответа:** `#[IgnoreResponse(404)]` или `#[IgnoreResponse('30*')]` — wildcard.

- **Документация:** https://scramble.dedoc.co/usage/response

### ID операции

Автоматически из route name → controller+method. Переопределить: `@operationId getTireList` в докблоке метода.

### Скрытие эндпоинта

```php
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;

#[ExcludeRouteFromDocs]
public function internalDebug(): JsonResponse
```

Или на весь контроллер: `#[ExcludeAllRoutesFromDocs]`.

### Медиа-тип запроса

По умолчанию `application/json`. `file` в rules → `multipart/form-data`. Переопределить: `@requestMediaType multipart/form-data` в докблоке метода.

### Enum-ы

Backed Enum автоматически: `{"type": "string", "enum": [...]}`. Описания case'ов — из PHPDoc enum-класса. Стратегия: `config/scramble.php` → `enum_cases_description_strategy` (`'description'` / `'extension'` / `false`).

### SchemaName — коллизии имён

```php
use Dedoc\Scramble\Attributes\SchemaName;

#[SchemaName('AdminUser')]
class UserResource extends JsonResource
```

## Стиль проекта

- PHP-атрибуты Scramble, **русский язык** описаний
- `#[Group]` на контроллере — краткое название домена («Каталог — шины», «Гео», «Импорт»)
- Простой эндпоинт: однострочный докблок `/** Список шин. */` + атрибуты для параметров
- Сложный: многострочный докблок (title + описание) + `#[QueryParameter]`/`#[BodyParameter]` для ключевых параметров
- Админские эндпоинты (`/api/admin`, `auth:sanctum`) — автоопределение через `MiddlewareAuthSecurityStrategy`
- `#[ScrambleResponse]` — только для нестандартных статусов (201, 204) или описаний
- Описания параметров — PHPDoc над правилом в `rules()`, а не `#[QueryParameter]`, кроме случаев:
  - Нужен `infer: false` (параметр не из валидации)
  - Параметр document-based (не из FormRequest)
  - Нужен пример, который нельзя записать в PHPDoc

## Алгоритм при запуске

1. **Скоуп.** Аргумент: имя контроллера (`/scramble TireProductController`), домен, группа. Без аргумента — все эндпоинты.
2. **Инвентаризация.** Прочитать `routes/admin.php` и `routes/api.php`: метод, URI, контроллер+метод, middleware.
3. **Аудит** каждого эндпоинта по чек-листу (ниже). Сверять:
   - Параметры — FormRequest (`rules()`) или `validate()` в контроллере
   - Ответы — return-тип, Resource (`toArray()`), модель
   - Ошибки — `abort`, `@throws`, `authorize`
4. **План.** Показать таблицу: эндпоинт → что добавить/изменить. При >10 эндпоинтов или новых контроллерах — подтверждение.
5. **Применение.** Добавить только недостающие атрибуты и докблоки. Не менять код поведения.
6. **Проверка.** `make lint-fix` → `make phpstan` → `make test`. Документация перегенерируется при открытии `/docs/api` — предложить открыть в браузере.

## Чек-лист полноты эндпоинта

| Критерий | Когда требуется | Как сделать |
|---|---|---|
| Докблок с title | Всегда | `/** Список шин. */` на методе |
| Описание | Нетривиальная логика | Многострочный докблок |
| `#[Group]` | Группа ≠ имя контроллера | Атрибут на контроллере |
| Описания параметров | Имя параметра неочевидно | PHPDoc над правилом или `#[QueryParameter]`/`#[BodyParameter]` |
| Примеры параметров | Значение важно для понимания | `@example` в PHPDoc или `example:` в атрибуте |
| `#[ScrambleResponse]` | Нестандартный статус (201, 204) или описание ответа | Атрибут на методе |
| `@unauthenticated` | Публичный эндпоинт внутри защищённого контроллера | Докблок метода |
| Ответы ошибок | Scramble не видит (abort с переменной, кастомные исключения) | `#[ScrambleResponse]` или `@throws` |
| `#[ExcludeRouteFromDocs]` | Служебный/внутренний маршрут | Атрибут на методе |
| `@requestMediaType` | Не JSON и не multipart/form-data | Докблок метода |
| Модель Resource | Resource без привязки к модели | `@property` в докблоке Resource |
| `$infer: false` на параметре | Scramble извлёк неправильный тип из `rules()` | В `#[QueryParameter]` |

## Анти-правила

- **Не пиши `@queryParam`, `@bodyParam`, `@responseField`** — это Scribe-аннотации, Scramble их не понимает.
- **Не добавляй параметры вручную, если Scramble уже извлёк их из `rules()`.** Ручные описания — только через PHPDoc над правилом.
- **Не дублируй ответы ошибок, которые Scramble видит сам:** 422 из `FormRequest`, 403 из `authorize()`, 404 из model binding.
- **Не меняй поведение кода** — только докблоки и атрибуты.
- **Не добавляй `#[ScrambleResponse]` без крайней нужды** — авто-вывод покрывает 200/201/204 из return-типа.
- **Не пиши описания в `#[QueryParameter]` для параметров, уже описанных в PHPDoc `rules()`** — достаточно одного места.

## Отличия от Scribe

| Scribe | Scramble |
|---|---|
| `@group Каталог — шины` | `#[Group('Каталог — шины')]` |
| `@authenticated` | Авто из middleware `auth:sanctum` |
| `@queryParam`, `@bodyParam` | `#[QueryParameter]`, `#[BodyParameter]` (только если нужно описание/пример) |
| `@apiResource`, `@apiResourceModel` | Авто из return-типа, модель из `@property` |
| `@response 200 {...}` | Авто из Resource/return-типа |
| `@responseField id int` | PHPDoc `@var int` над полем `toArray()` |
| `bodyParameters()`/`queryParameters()` | PHPDoc над правилами в `rules()` |
| `@hideFromAPIDocumentation` | `#[ExcludeRouteFromDocs]` |
| Response call (HTTP-запрос) | Статический анализ (нет запросов к БД) |
| Ошибки — только ручные `@response 422` | Автоматически: 422, 403, 404, `abort()` |
