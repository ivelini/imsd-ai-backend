<?php

namespace App\Filament\Clusters\Booking\Resources\PriceRules\Tables;

use App\Enums\Booking\CarType;
use App\ValueObjects\Money;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PriceRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('service.name')
                    ->label('Услуга')
                    ->searchable(),
                TextColumn::make('radius')
                    ->label('Радиус')
                    ->formatStateUsing(fn (int $state): string => 'R'.$state),
                TextColumn::make('car_type')
                    ->label('Тип авто')
                    ->badge()
                    ->formatStateUsing(fn (CarType $state): string => $state->label()),
                TextColumn::make('price')
                    ->label('Цена за единицу')
                    ->formatStateUsing(fn (?Money $state): string => $state?->formatted() ?? '—'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
