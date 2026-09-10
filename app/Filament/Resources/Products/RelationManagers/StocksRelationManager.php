<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Actions\Catalog\PopulateCatalogPrices;
use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Services\Catalog\PriceCalculator;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

/** Остатки товара по складам (общий для шин и дисков): правка количества и цен, пересчёт цен города. */
class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Количество')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label('Закупочная')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('—'),
                TextColumn::make('price')
                    ->label('Продажная')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить')
                    ->schema($this->stockForm())
                    ->mutateDataUsing(fn (array $data): array => $this->composeStockData($data))
                    ->after(fn (Stock $record) => $this->recalculateCatalogPrices($record)),
            ])
            ->recordActions([
                EditAction::make()
                    ->schema($this->stockForm())
                    ->mutateDataUsing(fn (array $data): array => $this->composeStockData($data))
                    ->after(fn (Stock $record) => $this->recalculateCatalogPrices($record)),
                DeleteAction::make()
                    ->before(function (Stock $record): void {
                        // catalog_prices ссылаются на остаток без каскада — снимаем цены города до удаления.
                        $record->catalogPrices()->delete();
                    }),
            ]);
    }

    /** @return array<int, mixed> */
    private function stockForm(): array
    {
        return [
            Select::make('warehouse_id')
                ->label('Склад')
                ->options(fn (): array => Warehouse::orderBy('name')->pluck('name', 'id')->all())
                ->required()
                ->disabledOn('edit')
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule
                        ->where('stockable_type', $this->getOwnerRecord()->getMorphClass())
                        ->where('stockable_id', $this->getOwnerRecord()->getKey()),
                ),
            TextInput::make('quantity')
                ->label('Количество')
                ->numeric()
                ->minValue(0)
                ->required()
                ->default(0),
            TextInput::make('purchase_price')
                ->label('Закупочная цена')
                ->numeric()
                ->minValue(0)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, mixed $state, string $operation): void {
                    // Пересчёт только при вводе закупочной: ручная продажная цена не перетирается.
                    if ($operation === 'edit' && blank($get('purchase_price'))) {
                        return;
                    }

                    $set('price', $this->salePrice($get('warehouse_id'), $state));
                }),
            TextInput::make('price')
                ->label('Продажная цена')
                ->helperText('Пересчитывается по наценке склада; можно задать вручную'),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function composeStockData(array $data): array
    {
        if (($data['price'] ?? null) === null) {
            $data['price'] = $this->salePrice($data['warehouse_id'] ?? null, $data['purchase_price'] ?? null);
        }

        return $data;
    }

    /** Цена продажи по правилу наценки склада; null, если закупочная не задана. */
    private function salePrice(mixed $warehouseId, mixed $purchasePrice): ?float
    {
        if ($warehouseId === null || blank($purchasePrice)) {
            return null;
        }

        return app(PriceCalculator::class)->calculateForWarehouse(
            (float) $purchasePrice,
            (int) $warehouseId,
        );
    }

    private function recalculateCatalogPrices(Model $stock): void
    {
        app(PopulateCatalogPrices::class)->execute(
            new PopulateCatalogPricesInput(stockIds: [$stock->getKey()]),
        );
    }
}
