# Волна 2c миграции на Filament: изображения товара + снос Image API

> Source: План .claude/plans/filament-migration.md + код (ImagesRelationManager, DeleteImage, PanelAction)
> Collected: 2026-09-10
> Published: 2026-09-10

## Реализовано

`App\Filament\Resources\Products\RelationManagers\ImagesRelationManager` — **один RelationManager на оба товара** (связь `images` морфная и идентична у Tire/Wheel; зарегистрирован в `getRelations()` обоих ресурсов):
- таблица: ImageColumn (disk public), sort, is_main; `reorderable('sort')`;
- CreateAction «Загрузить» с FileUpload: `image()` + `acceptedFileTypes(jpeg/png/webp)` + `maxSize(10240)` + **`storeFiles(false)`** — файл приходит как `TemporaryUploadedFile` и сохраняется через Action, а не Filament'ом;
- recordActions: `setMain` (виден только для не-главных) и `delete`.

Запись идёт через доменные Actions (`UploadImage`, `DeleteImage`, `SetMainImage`, `ReorderImages`) — политика слоёв соблюдена. Порядок перетаскиванием пишет `ReorderImages` через хук `afterReordering` (встроенная SQL-запись Filament обошла бы слой).

**PanelAction** (`app/Filament/Support/PanelAction.php`) — новый общий хелпер: `PanelAction::run('Что сделано', fn () => ...)`. Инкапсулирует идиому «try/catch DomainException → danger-нотификация + success-нотификация». Появился потому, что паттерн встретился третий раз (Brands, ProductModels, теперь изображения) — правило «обобщай на третий раз»; оба существующих места переведены на хелпер.

**Фикс дефекта:** `DeleteImage` удалял только запись в `images`, файл оставался на public-диске (файлы-сироты копились). Добавлено `Storage::disk('public')->delete($path)`.

**Снос Image API:** маршруты `/images*` (5), `ImageController`, Request'ы `ImageIndexRequest`/`UploadImageRequest`/`ReorderImagesRequest`, admin-`ImageResource`, Action `ListImages` (замена — query связи), мёртвый `ImageService::getNextMainImageId` (дублировал логику `DeleteImage` инлайном, не вызывался нигде) + 3 его юнит-теста.

## Тесты

`ImagesRelationManagerTest` (8): загрузка создаёт запись с morph-типом и главным флагом, лимит 10 (11-я отклоняется), удаление главного переназначает следующее, удаление не-главного не трогает флаг, удаление снимает файл с диска, setMain переключает главное, перетаскивание меняет sort, загрузка работает для диска. `ImageApiRemovalTest` (2): 404 на 5 маршрутов + страж 200 на import/status. Красный 9 failed (8 × ComponentNotFoundException) → зелёный; полный сьют 422 passed.

Грабли: header-action таблицы в тестах вызывается `callTableAction('create')`, не `callAction`; владелец в CreateAction берётся из `$livewire->getOwnerRecord()` — у header-экшена `$record` пуст.
