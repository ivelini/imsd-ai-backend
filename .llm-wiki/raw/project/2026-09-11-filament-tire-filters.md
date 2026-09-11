# Фильтры листинга шин в панели + контракт HasLabel у энумов

> Source: План .claude/plans/tire-list-filters.md + код (TireProductsTable, ListTireProducts, энумы) + вендор Filament 5.8.1 приложения
> Collected: 2026-09-11
> Published: 2026-09-11

## Задача

Администратору нужен отбор шин в `/panel/catalog/tire-products` по характеристикам. Каркас был начат копированием городского селекта: 6 `Select` в `content()`, из них 5 привязаны к одному `cityId`, опции у всех — города.

## Решение: штатный механизм, а не страничные свойства

Город живёт страничным свойством `#[Url] $cityId`, потому что меняет **состав колонок** таблицы (заголовок и наличие колонки «Цена в городе») — единственный из параметров, кому нужна пересборка таблицы (см. отдельный разбор про кеш фазы гидратации).

Сезон, ширина, профиль, диаметр и шипы — чистый `WHERE` по колонкам `tire_products`. Для них взят штатный механизм Filament:

```php
->filtersLayout(FiltersLayout::AboveContent)
->filters([
    SelectFilter::make('season')->label('Сезонность')->options(Season::class),
    SelectFilter::make('width')->label('Ширина')->options(fn (): array => self::dimensionOptions('width')),
    SelectFilter::make('profile')->label('Профиль')->options(fn (): array => self::dimensionOptions('profile')),
    SelectFilter::make('diameter')->label('Диаметр')->options(fn (): array => self::dimensionOptions('diameter')),
    SelectFilter::make('is_studded')->label('Шипы')->options([1 => 'Шипованная', 0 => 'Не шипованная']),
])
```

`FiltersLayout::AboveContent` (`vendor/filament/tables/src/Enums/FiltersLayout.php`, сеттер — `Table/Concerns/HasFilters.php:150`) рендерит фильтры **над таблицей** — тот же вид, что у городского селекта, ради которого каркас и начинался. Дальше всё делает Filament: состояние в `tableFilters` (свойство `#[Url]` у `ListRecords`, то есть в query string), сброс страницы при смене, сужение запроса, кнопка «сбросить фильтры». Своего кода на жизненный цикл — ноль.

Опции размеров — из каталога, а не из справочника размеров: `distinct()` по колонке, `orderBy`, ключ = значение. По всему каталогу, включая неопубликованные (администратор ищет товар, а не витрину). `TireFacetAssembler::dimension()` для панели не годится: он отдаёт значения контракта публичного API (`w205`, `r16`).

Скоупы `TireProductBuilder` (`bySeason`, `studded`, `byWidths`, `byProfiles`, `byDiameters`) при этом не задействованы — `SelectFilter` строит `where` сам. Слой остаётся единым источником для публичного каталога; если панели понадобится фасетное сужение (опции зависят от активных фильтров), это отдельная задача с `byCatalogFilters`.

## Грабля: `->options(Enum::class)` без контракта HasLabel

`vendor/filament/forms/src/Components/Concerns/HasOptions.php:41` — если энум не реализует `Filament\Support\Contracts\HasLabel`, Filament **молча** берёт имя кейса и игнорирует собственный `label()`:

```php
return array_reduce($enum::cases(), function (array $carry, UnitEnum $case): array {
    $carry[$case->value ?? $case->name] = $case->name;   // Winter, Alloy, Percent, Tire
    return $carry;
}, []);
```

Ни один энум проекта контракт не реализовывал, а `->options(X::class)` используется в 7 местах панели: `Season`, `WheelType`, `PromotionType`, `BrandType`. В интерфейсе жили английские имена кейсов вместо русских подписей. Фикс — четырём энумам добавлен `implements HasLabel` и общий трейт `App\Enums\Concerns\HasFilamentLabel` (первый трейт в `app/`: адаптер `getLabel()` → доменный `label()`). Трейт, а не метод в каждом энуме: без него один и тот же трёхстрочный метод копировался бы четырежды (Rule of Three).

## Побочное: метка сезона попадает в имя товара

`TireNameBuilder::build()` (`app/Services/Catalog/Tire/TireNameBuilder.php`) собирает имя как «Шина {mb_strtolower(season->label())} {бренд} …». Правка метки `Season::AllSeason` ('Всесезон' → 'Всесезонная') изменила имена всесезонных товаров: «Шина всесезонная Gislaved Soft Frost 200 …». Уронила `TireNameBuilderTest`; решением принято новое имя, тест обновлён.

Одна метка обслуживает два контекста — подпись в селекте («Всесезонная» согласуется с «Зимняя/Летняя») и слово в имени товара. Пока это одна строка, правка подписи меняет имена. Имена уже созданных товаров остаются старыми до переимпорта.

## Тесты

- `tests/Feature/Admin/Catalog/TireProductsFiltersTest.php` (7) — сужение по каждому фильтру через `filterTable()` + `assertCanSeeTableRecords`/`assertCanNotSeeTableRecords`; шипы проверяются в обе стороны (реализация, зашитая на `true`, падает); опции ширины — точным массивом `[175 => 175, 205 => 205]` (значения 195 нет в каталоге); русские подписи сезона.
- `tests/Unit/Enums/UiEnumLabelsTest.php` (1) — `getLabel()` четырёх энумов.
- Красный прогон 8 failed → зелёный 8 passed. Полная сюита 456 passed; PHPStan и Pint чистые.

Первая версия теста на русские подписи была зелёной **без реализации**: каркас-заглушка уже рендерил подпись «Сезонность», а колонка «Сезон» — «Зимняя» из той же `label()`. Переписан: в данных нет зимней шины, поэтому «Зимняя» может появиться только из опций фильтра.

## Попутная проверка (Postgres)

`SelectFilter` по булевой колонке в Postgres безопасен: `SelectFilter::apply()` подставляет значение связанным параметром, PDO отдаёт его в Postgres, и тот приводит параметр к boolean по контексту. Литерал `where is_studded = 1` в Postgres падает (`operator does not exist: boolean = integer`), связанный параметр (`1`, `true`, `'1'`) — работает. Тесты идут на sqlite, где различия нет, поэтому проверялось отдельно на dev-базе.
