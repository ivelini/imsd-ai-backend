<?php

namespace App\Filament\Clusters\Booking\Resources\BookingServices\Tables;

use App\Enums\Booking\ServiceCategory;
use App\ValueObjects\Money;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Категория')
                    ->badge()
                    ->formatStateUsing(fn (ServiceCategory $state): string => $state->label()),
                TextColumn::make('base_price')
                    ->label('Цена от')
                    ->formatStateUsing(fn (?Money $state): string => $state?->formatted() ?? '—'),
                TextColumn::make('price_rules_count')
                    ->label('Правил')
                    ->counts('priceRules'),
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
