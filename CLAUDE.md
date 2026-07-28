Команды — в `Makefile`. Для artisan: `docker compose exec app php /var/www/artisan ...`

## Архитектура

**Тип:** монолит (Laravel). E-commerce — шины и диски.

**Паттерн:** `Middleware → Controller (thin) → FormRequest → Preconditions → Action::execute(DTO): DTO → Resource` — подробнее в Правилах ниже.

**Домены (директории Models):** `Admin/`, `Catalog/`, `Cart/`, `Order/`, `Geo/`, `Warehouse/`, `Vehicle/`, `Article/`, `Content/`, `Common/`, `System/` — каждый домен = папка в `app/Models/`.

**Морф-мапа (`AppServiceProvider`):** `tire → Tire`, `wheel → Wheel`, `article → Article`.

**Enums:** `ProductType`, `WheelType`, `SpecType`, `PromotionType`, `DiscountType`, `OrderState`, `WeekDay` — все в `app/Enums/`.

**API:** `/api/admin` — `auth:sanctum`, `/api` — публичные + клиентские.

---

## Правила

### Слои

1. **Не клади бизнес-логику в контроллер.** Контроллер только оркестрирует. Для CRUD — `{Сущность}Controller`, для одного действия — `{Действие}{Сущность}Controller::__invoke`.
2. **Action не должен знать об HTTP.** `execute(DTO): DTO`. Action — `final readonly class`.
   - Для тривиального CRUD в админке можно `array<string, mixed>` вместо DTO.
   - Не вызывай другие Action из Action. Исключение: одна транзакция — через DI connection.
   - Разделяй чтение и запись.
   - Чистые функции выноси в отдельный Action под unit-тест.
3. **Проверяй бизнес-правила в Preconditions.** `ensure*() → DomainException`. Если ≥3 проверок — цепочка handler-ов через `setNext()`.
4. **Модель — только Eloquent.** `$table`, `$fillable`, `$casts`, отношения, скоупы, аксессоры.
   - У каждой модели, енума и каста — краткий phpdoc `/** Для чего. */` над классом. Одна строка, без шаблонов.
5. **DTO — `final readonly class`.** `fromRequest` / `toArray`.
6. **Сервис не принимает DTO.** Только примитивы или модели.
7. **Одна миграция — одна таблица** (исключение: pivot). Данные — только в сидерах.

### Принципы кодирования

- **Пиши код под unit-тесты.** Если для проверки нужна БД или HTTP — архитектура сломана.
- **CQS:** команда не возвращает данные, запрос не меняет состояние.
- **Fail Fast:** проверяй контракты на входе.
- **Не возвращай null.** Используй null-object, `Optional` (`?->`), или бросай исключение.
- **Конструктор — только присвоение.** Для инициализации — named constructor.
- **SOLID**, guard → early return. Забудь про `else`.
- **Один уровень абстракции** в методе.
- **≤3 параметра** у метода. Больше — группируй в DTO.
- **Инжекть зависимости явно.** Никаких `Auth::`, `Cache::`, `Redis::`. Исключения: `DB::raw`, `Log`.
- **Называй по намерению:** глагол + существительное.
- **Пиши комментарии «почему», а не «что».**
- **Оставляй код чище, чем нашёл** (правило бойскаута).
- **Обобщай только на третий раз** (Rule of Three).
- **Конфиги — в `config/*.php`.** Без fallback-ов.
- **Не привязывайся к name/title.** Используй `code`, enum или config-маппинг.
- **Валидируй только в `FormRequest`.** Бизнес-ошибки — `DomainException` с HTTP-кодом.

### Сериализация в очередь

- **В Job и batch-коллбэках сериализуй только ID модели, не модель целиком.** Модель восстанавливай внутри `handle()` / `finally()` отдельным запросом. Eloquent-модель тянет весь граф атрибутов и отношений — `laravel/serializable-closure` рекурсивно обходит его, вызывая переполнение памяти и падение без catch.
  ```php
  // ❌ Плохо
  public function __construct(readonly Order $order) {}
  Bus::batch($jobs)->finally(function () use ($product) { ... })->dispatch();

  // ✅ Хорошо
  public function __construct(readonly int $orderId) {}
  $productId = $product->id;
  Bus::batch($jobs)->finally(function () use ($productId) { ... })->dispatch();
  ```
- Исключение: константные скаляры (статусы, флаги, мелкие строки) — можно, если они не растут с данными и гарантированно не превысят лимит.

## Работа с кодом

- Перед коммитом: `make lint-fix` + `make phpstan` + `make test`
- Статический анализ: `make phpstan` (level 6, config: `phpstan.neon`)
- **В сообщении коммита указывай модель AI-агента**, который сгенерировал изменения. Формат: `model: <имя-модели>` в теле коммита. Это позволяет отследить, какой моделью создан код.

## Документация

Вся документация проекта — в `documentations/`:

| Файл | О чём |
|------|-------|
| `architecture.md` | Архитектура проекта (стек, Docker, слои, API, ценообразование, сроки доставки) |
| `db-schema.md` | Полная схема БД, все таблицы и поля, маппинг импорта (XLSX/CSV → БД) |
| `api.md` | Стандарты API: форматы запросов/ответов, ошибки, пагинация |
| `tz/user-functional-spec.md` | ТЗ пользовательской части (бизнес-язык) |
| `tz/admin-functional-spec.md` | ТЗ административной панели (бизнес-язык) |
| `fr/user-functional-spec.md` | Функциональные требования для пользователя (FR-*) |
| `fr/admin-functional-spec.md` | Функциональные требования для админки (ADM-*) |
| `import/` | Примеры исходных файлов для импорта (tires.xlsx, wheels.xlsx, vehicle.csv, points.xlsx) |
