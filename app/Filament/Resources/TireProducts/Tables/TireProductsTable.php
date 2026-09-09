<?php

namespace App\Filament\Resources\TireProducts\Tables;

use App\Enums\Catalog\Season;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TireProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                TextColumn::make('year')
                    ->label('Год')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Опубл.')
                    ->boolean(),
                IconColumn::make('is_bestseller')
                    ->label('Хит')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('brand')
                    ->label('Бренд')
                    ->relationship('brand', 'name')
                    ->searchable(),
                SelectFilter::make('season')
                    ->label('Сезон')
                    ->options(Season::class),
                SelectFilter::make('is_published')
                    ->label('Публикация')
                    ->options([
                        1 => 'Опубликована',
                        0 => 'Скрыта',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
