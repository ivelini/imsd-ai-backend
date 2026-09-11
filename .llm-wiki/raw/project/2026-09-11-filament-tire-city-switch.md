# Смена города в списке шин: таблица отставала на рендер + краш колонки «Цена в городе»

> Source: Код (ListTireProducts, TireProductsTable) + вендор Livewire/Filament 5.8.1 + тесты TireProductsCitySwitchTest / TireProductsTableTest
> Collected: 2026-09-11
> Published: 2026-09-11

## Симптом

На `/panel/catalog/tire-products` при выборе города в селекте в таблицу приходил **предыдущий** город: заголовок «Цена в городе (Челябинск)» и его цены оставались после выбора Уфы. Второй запрос (любое следующее действие) показывал уже правильный город — отставание ровно на один рендер.

## Причина: конфигурация таблицы кешируется до применения обновлений

Порядок внутри `Livewire\Mechanisms\HandleComponents\HandleComponents::update()` (вендор, строка ~219):

```
trigger('hydrate', $component, ...)          ← здесь bootedInteractsWithTable()
$this->updateProperties($component, $updates, ...)
$this->callMethods($component, $calls, ...)
$this->render($component)
```

`Filament\Tables\Concerns\InteractsWithTable::bootedInteractsWithTable()` — публичный хук жизненного цикла Livewire (`booted` + имя трейта) — выполняется в фазе гидратации и **присваивает кешируемое поле**:

```php
public function bootedInteractsWithTable(): void
{
    $this->table = $this->table($this->makeTable());
    ...
}
```

`getTable()` — просто `return $this->table;` (пересборки нет). Значит `table()` и вызванный из него `TireProductsTable::configure($table, $this->currentCity())` исполняются **один раз за запрос и до** применения обновлённых свойств: на момент сборки `cityId` в компоненте ещё старый (из снапшота). К моменту `render()` менять `$this->table` уже некому.

Общая асимметрия Filament: **структура** таблицы (колонки, фильтры) собирается на гидратации, **данные** — разрешаются лениво при рендере. Штатные `tableFilters` / `tableSearch` / `tableSort` работают именно потому, что читаются в момент построения запроса. `cityColumn($city)` зашивает город в структуру (в заголовок колонки и в замыкание `state()`) — и попадает в кеш.

## Фикс

`ListTireProducts::updatedCityId()` пересобирает конфигурацию после применения свойства:

```php
public function updatedCityId(): void
{
    $this->table = $this->table($this->makeTable());
    $this->flushCachedTableRecords();
    $this->resetPage();
}
```

`$this->table` и `makeTable()` — protected-члены трейта, доступные наследнику; строка повторяет шаг `bootedInteractsWithTable()`.

### Отвергнутые альтернативы

- **`resetTable()`** (штатный публичный API «сбросить таблицу») — делает то же плюс `resetTableFiltersForm()`, который через `handleTableFilterUpdates()` перезаписывает `$this->tableFilters` пустой формой. Проверено тестом: активный фильтр «Бренд» стирался. Индексные вызовы `resetTable()` в вендоре отсутствуют — это API для разработчика на случай смены всего контекста.
- **Ленивые замыкания** (`fn () => $this->currentCity()` внутри `configure()`) — колонки принимают `Closure` для `label()`, запрос строится при рендере, так что это рабочий путь. Отвергнут: **наличие** колонки решается в `configure()` жадно (`...($city !== null ? [self::cityColumn($city)] : [])`), а главное — половинчатость ловушки: следующий, кто напишет `->label("...{$city->name}")` без замыкания, вернёт баг молча. Пересборка чинит класс целиком.
- **`bootedInteractsWithTable()` напрямую** — публичный, но это lifecycle-хук: повторный вызов прогоняет логику session-фильтров/поиска/сортировки и может затереть состояние.

## Попутный краш: колонка «Цена в городе» без расписания отгрузки

`TireProductsTable::cityLine()` вызывал `$record->getRelation('delivery')` безусловно, а `DeliveryInfoService::enrichProduct()` выставляет связь `delivery` только когда срок вычислим (`computeDelivery()` возвращает `null`, если ни у одного остатка нет расписания — `$days === null`). Итог: товар с остатками, но без расписаний у складов, ронял листинг с `Undefined array key "delivery"` (ViewException в `tables/resources/views/index.blade.php`) при выбранном городе.

Фикс — защита по факту наличия связи:

```php
$delivery = $record->relationLoaded('delivery') ? $record->getRelation('delivery') : null;
```

Существующие тесты кейс не покрывали: `createStockWithSchedule()` всегда создаёт расписание, а листинг без города колонку не строит.

## Тесты

- Новый `tests/Feature/Admin/Catalog/TireProductsCitySwitchTest.php` — `Livewire::test(ListTireProducts::class)`, панель задаётся явно (`Filament::setCurrentPanel(...)`, HTTP-запроса нет). Проверяет заголовок колонки и цену до/после переключения города.
- `TireProductsTableTest` (+1) — HTTP-тест: остаток без расписания отгрузки не роняет листинг (красный прогон: `Undefined array key "delivery"`).
- Прогон админской сюиты: 146 passed; PHPStan и Pint чистые.

## Попутное

- `Schemas/TireListForm.php` — побайтовая копия `TireProductForm` (различалось только имя класса) — удалена как дубль.
- Из `ListTireProducts` убран неиспользуемый импорт `Illuminate\Support\Facades\Log` (наследство отладки).
- Фильтры таблицы шин (`SelectFilter` по бренду, сезону, публикации) при этом сняты — состояние `tableFilters` больше не пишется, поэтому тест «фильтры переживают смену города» убран за ненадобностью. Если фильтры вернутся — вернуть и тест: тогда `resetTable()` окончательно непригоден.
