<?php

namespace App\Filament\Clusters\Catalog\Resources\WheelProducts\Tables;

use App\Enums\Catalog\WheelType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WheelProductsTable
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
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (WheelType $state): string => $state->label()),
                TextColumn::make('width')
                    ->label('Ширина')
                    ->sortable(),
                TextColumn::make('diameter')
                    ->label('Диаметр')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pcd')
                    ->label('PCD')
                    ->searchable(),
                TextColumn::make('et')
                    ->label('ET')
                    ->sortable(),
                TextColumn::make('hub_diameter')
                    ->label('DIA')
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
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(WheelType::class),
                SelectFilter::make('is_published')
                    ->label('Публикация')
                    ->options([
                        1 => 'Опубликован',
                        0 => 'Скрыт',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
