<?php

namespace App\Filament\Resources\Promotions\Tables;

use App\Actions\Promotion\RecalculatePromotedPrices;
use App\Enums\Promotion\PromotionType;
use App\Models\Catalog\Promotion\Promotion;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PromotionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (PromotionType $state): string => $state->label()),
                TextColumn::make('value')
                    ->label('Значение')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('—'),
                TextColumn::make('promotable_type')
                    ->label('Привязка')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'brand' => 'Бренд',
                        'tire' => 'Шина',
                        'wheel' => 'Диск',
                        default => 'Весь каталог',
                    }),
                TextColumn::make('starts_at')
                    ->label('Начало')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Окончание')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->state(fn (Promotion $record): bool => $record->starts_at->isPast() && $record->ends_at->isFuture())
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(PromotionType::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    // Цены пересчитываются после удаления: акция ещё в БД на момент хука
                    ->after(fn (Promotion $record) => app(RecalculatePromotedPrices::class)->forPromotions($record)),
            ])
            ->defaultSort('starts_at', 'desc');
    }
}
