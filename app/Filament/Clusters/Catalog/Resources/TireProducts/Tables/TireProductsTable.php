<?php

namespace App\Filament\Clusters\Catalog\Resources\TireProducts\Tables;

use App\Enums\Catalog\Season;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Delivery\City;
use App\Services\Delivery\DeliveryInfoService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TireProductsTable
{
    public static function configure(Table $table, ?City $city = null): Table
    {
        return $table
            // Колонки «Склады» и «Цена в городе» читают остатки в каждой строке — грузим заранее,
            // иначе N+1. Только листинг: getEloquentQuery() ресурса тянул бы остатки и на Edit-страницу.
            ->modifyQueryUsing(fn (Builder $query) => $query->with(self::relations($city)))
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.name')
                    ->label('Бренд')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('model.name')
                    ->label('Модель')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ean')
                    ->label('EAN')
                    ->searchable(),
                TextColumn::make('season')
                    ->label('Сезон')
                    ->badge()
                    ->formatStateUsing(fn (Season $state): string => $state->label()),
                TextColumn::make('width')
                    ->label('Ширина')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('profile')
                    ->label('Профиль')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('diameter')
                    ->label('Диаметр')
                    ->searchable(),
                IconColumn::make('is_studded')
                    ->label('Шипы')
                    ->boolean(),
                TextColumn::make('stocks')
                    ->label('Склады')
                    ->state(fn (TireProduct $record): array => self::stockLines($record))
                    ->listWithLineBreaks()
                    ->placeholder('Нет остатков')
                    ->toggleable(),
                ...($city !== null ? [self::cityColumn($city)] : []),
                IconColumn::make('is_published')
                    ->label('Опубл.')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_bestseller')
                    ->label('Хит')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Фильтры-селекты над таблицей: состояние, сброс страницы и сужение запроса — механика Filament.
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                SelectFilter::make('season')
                    ->label('Сезонность')
                    ->options(Season::class),
                SelectFilter::make('width')
                    ->label('Ширина')
                    ->options(fn (): array => self::dimensionOptions('width')),
                SelectFilter::make('profile')
                    ->label('Профиль')
                    ->options(fn (): array => self::dimensionOptions('profile')),
                SelectFilter::make('diameter')
                    ->label('Диаметр')
                    ->options(fn (): array => self::dimensionOptions('diameter')),
                SelectFilter::make('is_studded')
                    ->label('Шипы')
                    ->options([1 => 'Шипованная', 0 => 'Не шипованная']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Значения размера, присутствующие в каталоге, по возрастанию.
     *
     * По всему каталогу, включая неопубликованные: администратор ищет товар, а не витрину.
     *
     * @return array<int, int|string>
     */
    private static function dimensionOptions(string $column): array
    {
        return TireProduct::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->all();
    }

    /**
     * Строки остатков: «Склад — 5 шт — 12 500,00 ₽»; крупные остатки выше.
     *
     * @return array<int, string>
     */
    private static function stockLines(TireProduct $record): array
    {
        return $record->stocks
            ->sortByDesc('quantity')
            ->map(static fn (Stock $stock): string => self::stockLine($stock))
            ->values()
            ->all();
    }

    /** Продажная цена может быть не задана — тогда строка без неё. */
    private static function stockLine(Stock $stock): string
    {
        $line = "{$stock->warehouse->name} — {$stock->quantity} шт";

        if ($stock->price === null) {
            return $line;
        }

        return $line.' — '.number_format((float) $stock->price, 2, ',', ' ').' ₽';
    }

    /**
     * Связи листинга: склады, а для выбранного города — их расписания отгрузки и цены города.
     *
     * @return array<int|string, mixed>
     */
    private static function relations(?City $city): array
    {
        if ($city === null) {
            return ['stocks.warehouse'];
        }

        return [
            'stocks.warehouse.deliverySchedules',
            'stocks.catalogPrices' => function (HasMany $catalogPrices) use ($city): void {
                $catalogPrices->where('city_id', $city->id);
            },
        ];
    }

    /** Цена города: снимок catalog_prices (цена и наценка) + точный срок доставки на текущий момент. */
    private static function cityColumn(City $city): TextColumn
    {
        // Один инстанс сервиса на рендер: внутри кеш дней и правил города, иначе запросы на строку.
        $deliveryInfo = app(DeliveryInfoService::class);

        return TextColumn::make('city_price')
            ->label("Цена в городе ({$city->name})")
            ->state(fn (TireProduct $record): ?string => self::cityLine($record, $city, $deliveryInfo))
            ->placeholder('Нет цены')
            ->toggleable();
    }

    /** «12 800,00 ₽ — 5 дн. (наценка 300,00 ₽)»: части без данных опускаются. */
    private static function cityLine(TireProduct $record, City $city, DeliveryInfoService $deliveryInfo): ?string
    {
        $offer = self::cheapestCityOffer($record);

        if ($offer === null) {
            return null;
        }

        // Точный срок на момент запроса — как на карточке товара (FR-1.3.3).
        $deliveryInfo->enrichProduct($record, $city->id);
        // Ни у одного остатка нет расписания отгрузки — срок посчитать не из чего.
        /** @var array{delivery_days: int, markup: float|null}|null $delivery */
        $delivery = $record->relationLoaded('delivery') ? $record->getRelation('delivery') : null;

        $line = number_format($offer['price'], 2, ',', ' ').' ₽';

        if (($delivery['delivery_days'] ?? null) !== null) {
            $line .= " — {$delivery['delivery_days']} дн.";
        }

        if ($offer['markup'] > 0) {
            $line .= ' (наценка '.number_format($offer['markup'], 2, ',', ' ').' ₽)';
        }

        return $line;
    }

    /**
     * Лучший оффер города: минимальная цена города среди остатков в наличии.
     *
     * @return array{price: float, markup: float}|null
     */
    private static function cheapestCityOffer(TireProduct $record): ?array
    {
        $offers = $record->stocks
            ->filter(fn (Stock $stock): bool => $stock->quantity > 0)
            ->map(fn (Stock $stock): ?array => self::cityOffer($stock))
            ->filter()
            ->values();

        if ($offers->isEmpty()) {
            return null;
        }

        // Tiebreaker по id остатка — детерминизм выбора при равных ценах (как в DeliveryStockPicker).
        /** @var array{price: float, markup: float, stock_id: int} $offer */
        $offer = $offers->sortBy([['price', 'asc'], ['stock_id', 'asc']])->first();

        return $offer;
    }

    /**
     * Цена города и наценка одного остатка — строки catalog_prices по выбранному городу.
     * Наценка — разница базовой цены города и продажной цены остатка (ADR 0002/0009).
     *
     * @return array{price: float, markup: float, stock_id: int}|null
     */
    private static function cityOffer(Stock $stock): ?array
    {
        $catalogPrice = $stock->catalogPrices->first();

        if ($catalogPrice === null || $stock->price === null) {
            return null;
        }

        return [
            'price' => $catalogPrice->price,
            'markup' => ($catalogPrice->base_price ?? 0.0) - (float) $stock->price,
            'stock_id' => (int) $stock->id,
        ];
    }
}
