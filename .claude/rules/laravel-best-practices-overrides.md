# Overrides: laravel-best-practices

Навык `laravel-best-practices` (плагин `laravel@laravel`) даёт generic-дефолты Laravel. **При конфликте побеждают конвенции проекта** (CLAUDE.md + правила этого каталога). Файлы навыка не редактировать — плагин перезапишет их при обновлении.

## 1. Фасады и глобальные хелперы

Навык строит примеры на фасадах (caching.md, http-client.md, security.md, mail.md, events-notifications.md, architecture.md, config.md) — в проекте запрещены, кроме трёх исключений.

```php
// ❌ Навык
Http::timeout(5)->get(...);
Cache::remember('stats', 60, fn () => ...);
Gate::authorize('update', $post);
Mail::to($user)->send(...);
Notification::route('mail', ...)->notify(...);
Context::add('tenant_id', $request->header('X-Tenant-ID'));
Concurrency::run([...]);
App::environment('production');
app(SomeClass::class);   // и App::make()

// ✅ Проект: явная DI в конструкторе
// Исключения: DB::raw / DB::table (выразительный SQL), Storage (URL), Log
```

Не применять и глобальные хелперы `once()` (caching.md), `app()` (style.md).

## 2. Контракт Action

```php
// ❌ Навык (architecture.md, routing.md): handle(array), возврат Eloquent-модели
public function handle(array $data): Order { return Order::create($data); }
$post = $create->execute($request->validated());

// ✅ Проект: final readonly, execute(DTO): DTO|void|array, CQS
final readonly class CreateOrder
{
    public function execute(CreateOrderInput $input): CreateOrderResult {}
}
```

## 3. Кеш

```php
// ❌ Навык (caching.md): кеш на месте использования, теги, модели в кеше
Cache::remember('stats', 60, fn () => $this->computeStats());
Cache::tags(['user-1'])->flush();
Cache::flexible('users', [300, 600], fn () => User::all());

// ✅ Проект: Cache Service до Action, Observer-инвалидация, только массивы/скаляры
// Controller: $this->cache->remember(fn () => $this->action->execute());
// Observer: saved()/deleted() → $cache->forget()
// TTL — config/cache_ttl.php, без инлайн-литералов
```

## 4. Модели в очередях

```php
// ❌ Навык (queue-jobs.md): модель в Job и batch-замыканиях
public function uniqueId(): string { return $this->order->id; }
public function failed(?Throwable $e): void { $this->podcast->update([...]); }
Bus::batch([...])->then(fn (Batch $batch) => Notification::send($user, ...));

// ✅ Проект: только ID модели
public function __construct(readonly int $orderId) {}
$userId = $user->id;
Bus::batch([...])->finally(function () use ($userId) { ... });
```

## 5. Остальные overrides

| Файл навыка | Не применять | Конвенция проекта |
|---|---|---|
| validation.md | Бизнес-проверки (сток, лимиты) в `after()` FormRequest | FormRequest — только формат; бизнес — Preconditions `ensure*` → DomainException |
| migrations.md | Данные в миграции (`DB::table(...)->insert`) | Данные — только в сидерах |
| testing.md | `LazilyRefreshDatabase` | `RefreshDatabase` + actingAs |
| http-client.md | `return null` при graceful degradation | Fail fast: `->throw()` / DomainException |
| mail.md | `$this->afterCommit()` в конструкторе | Конструктор — только присвоение |
| style.md | «Комментарии не нужны, код сам себя читает» | Комментарии «почему» — пишем; «что» — не пишем |
| blade-views.md | View Composer для выборки данных | Данные — через Controller → Action, не в вью-слой |
| error-handling.md | `render()` + view на классе исключения | DomainException с HTTP-кодом из Preconditions |
| eloquent.md | `DB::table` помечен Incorrect | Разрешён для выразительного SQL |
| db-performance.md | Примеры с выборкой прямо в контроллере | Выборка — в Action |

## 6. Без конфликтов — применять как есть

`collections.md`, `advanced-queries.md`, `scheduling.md` совместимы с конвенциями проекта.
