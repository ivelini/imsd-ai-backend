# Договор хранения: документ, удостоверяющий личность; помощник печати переехал к сервису

> Source: Правка пользователя в backend (после коммита `e8f567a`), изменение кода
> Collected: 2026-09-18
> Published: Unknown

## Что сделано

1. У договора хранения появилось обязательное поле **«документ, удостоверяющий личность»**
   (`personal_document`): колонка NOT NULL, `TextInput` (required) в форме панели, значение печатается
   в договоре (11-й плейсхолдер шаблона `${personal_document}`).
2. Класс значений шаблона переехал к своему сервису: `app/Support/Storage/ContractDocumentValues.php` →
   `app/Services/StorageContract/ContractDocumentValues.php`; туда же переехал
   `StorageContractDocumentService` (был `app/Services/Storage/`).

## Решения

1. **Документ — обязательное поле договора**, а не свободная заметка: он есть в бумажном договоре
   (печатается рядом с ФИО и телефоном) и в колонке NOT NULL — договор без документа не заводят.
2. **Чистый помощник живёт рядом со своим потребителем**: `ContractDocumentValues` зовёт ровно один
   сервис, поэтому лежит с ним в `app/Services/StorageContract/`, а не в общем `Support/`. В `Support/`
   остаётся то, что нужно разным доменам (`RussianDate`, `Phone`). Решение закреплено правилом
   «Чистый помощник живёт рядом со своим потребителем» в глобальном `~/.claude/rules/coding-style.md`
   (принципы + чек-лист перед коммитом п. 4 + пример ❌/✅); обоснование — ADR 0001 (чистые алгоритмы
   в Services) и «службу не дробить».

## Как устроено

- `storage_contracts.personal_document` — колонка добавлена в create-миграцию `2026_09_17_000001`
  (база пересоздаётся, отдельных alter-миграций в проекте не плодят); в модели — `$fillable` и docblock.
- Форма `StorageContractForm`: `TextInput('personal_document')->label('Документ, удостоверяющий личность')->required()`.
- Шаблон договора: 11 ключей (10 прежних + `personal_document`), значение подставляет
  `ContractDocumentValues::forContract`.
- Тесты переехали зеркально каталогу реализации (правило «тесты — зеркально каталогу реализации»):
  `tests/Unit/Services/StorageContract/ContractDocumentValuesTest.php` (был `tests/Unit/Support/Storage/`)
  и `tests/Feature/Services/StorageContract/StorageContractDocumentTest.php` (был `tests/Feature/Storage/`);
  конвенция в проекте — `tests/Feature/Services/Catalog/PriceCalculatorTest.php`.

## Проверки

- Красный прогон после правки пользователя: `16 failed, 11 passed` — фабрика не заполняла
  `personal_document` (`NOT NULL constraint failed` во всех тестах, создающих договор), тест-лист ждал
  7 ключей вместо 8.
- Зелёный после доделки: `671 passed (2170 assertions)`, PHPStan — 0 ошибок, Pint — чисто.
- Добавлено в тест-лист: `personal_document` в фабрике/фикстуре/`formData()`, 11-й ключ в ожидании
  ключей, проверка документа в готовом .docx, новый кейс «документ обязателен» (Тест 12).

## Документация

- `documentations/db-schema.md` — колонка в таблице домена Storage.
- Wiki — страницы домена Storage и админ-панели.
