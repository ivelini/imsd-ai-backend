<?php

namespace App\Filament\Clusters\Catalog\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Остатки товара по складам (общий для шин и дисков) — только чтение: количество и цены
 * пишет импорт (`UpsertStock` применяет наценку склада при записи остатка, ADR 0009),
 * панель их не правит.
 */
class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Склады';

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
            ]);
    }
}
