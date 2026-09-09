<?php

namespace App\Filament\Resources\Cities\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('region.name')
                    ->label('Регион')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort')
                    ->label('Порядок')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort');
    }
}
