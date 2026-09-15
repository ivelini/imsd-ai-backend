<?php

namespace App\Filament\Clusters\Booking\Resources\PriceRules\Tables;

use App\Enums\Booking\CarType;
use App\ValueObjects\Money;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PriceRulesTable
{
    /**
     * @param  bool  $withServiceColumn  раздел «Прайс-правила» показывает услугу, карточка услуги — нет
     */
    public static function configure(Table $table, bool $withServiceColumn = true): Table
    {
        $serviceColumn = $withServiceColumn
            ? [TextColumn::make('service.name')->label('Услуга')->searchable()]
            : [];

        return $table
            ->columns([
                ...$serviceColumn,
                TextColumn::make('radius')
                    ->label('Радиус')
                    ->formatStateUsing(fn (int $state): string => 'R'.$state)
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::searchByRadius($query, $search)),
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

    /**
     * Поиск по радиусу так, как он подписан в таблице («R17»): из строки берутся цифры,
     * сравнение точное — радиусы 13…21, частичное совпадение даёт шум. Без цифр условия нет.
     */
    private static function searchByRadius(Builder $query, string $search): Builder
    {
        $digits = (string) preg_replace('/\D+/', '', $search);

        return $digits === '' ? $query : $query->where('radius', (int) $digits);
    }
}
