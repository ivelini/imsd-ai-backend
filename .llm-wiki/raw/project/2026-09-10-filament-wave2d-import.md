# Волна 2d (часть 1) миграции на Filament: страница импорта + снос Import API

> Source: План .claude/plans/filament-migration.md + код (ImportProducts, ImportCompletedNotification, routes/admin/catalog.php)
> Collected: 2026-09-10
> Published: 2026-09-10

## Реализовано

`App\Filament\Pages\ImportProducts` — страница панели `/panel/import` (группа «Каталог», slug `import`), реализует `HasTable`, использует `InteractsWithTable`:
- форма «Тип импорта (Select) + Файл (FileUpload)»; MIME-типы зависят от типа: XLSX для tire/wheel/model/point, CSV для vehicle (`acceptedMimeTypes()`); лимит 50 МБ; `storeFiles(false)` → `TemporaryUploadedFile`;
- кнопка «Загрузить» → `EnsureNoActiveImport::ensure($type)` → `StartProductImport::execute(StartImportInput)`. Пайплайн (Jobs, чанки, парсеры, Upsert*) не тронут — сменилась только точка входа;
- таблица истории: id, тип, файл (поиск), статус-бейдж с цветом, processed/total, ошибки, сообщение, запущен/завершён; фильтр по типу; `poll('5s')`; модалка со списком ошибок (`errors` jsonb).

**Смена адреса уведомления:** `ImportCompletedNotification::action_url` вёл на `/admin/imports/{id}` (React-админка, не в проде) → теперь `ImportProducts::getUrl()` (`/panel/import`).

**Снос Import API:** маршруты `/import/*` (7: 5 загрузок + сводный статус + статус по id), 7 контроллеров `Import/*`, `UploadFileRequest`, `UploadCsvFileRequest`, `ProductImportResource`. Тесты: удалены `ImportStatusTest` (3) и `TireImportTest` (5), срезаны HTTP-кейсы из `WheelImportTest`/`PointImportTest` (по 2) и `VehicleImportTest` (4 — переведены на прямой `StartProductImport` через хелпер `importVehicle()`, пайплайн-кейсы остались).

## Тесты

`ImportPageTest` (10): загрузка шин создаёт `ProductImport` + ставит `ImportMasterJob`; PDF для шин отклоняется; CSV для vehicle принимается и ставит `VehicleImportMasterJob`; XLSX для vehicle отклоняется; активный импорт того же типа блокирует и Job не ставится; активный импорт другого типа не блокирует; completed не блокирует; таблица показывает историю; фильтр по типу; страница требует авторизацию. `ImportApiRemovalTest` (2): 404 на 7 маршрутов + страж на promotions. `ImportCompletedNotificationTest` (1): action_url = ImportProducts::getUrl(). Красный 12 failed → зелёный; полный сьют 419 passed.

Грабли: страница с таблицей обязана реализовать `HasTable` (иначе `Table::make()` не принимает livewire); `ImportProducts::getUrl()` требует bootstrap приложения — тест уведомления Feature, не Unit; `$form` аннотируется `@property Schema $form` для PHPStan.
