<?php

namespace App\Filament\Resources\UserResource\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                // ФИО — склейка колонок: поиск идёт по частям имени
                TextColumn::make('full_name')
                    ->label('Клиент')
                    ->searchable(['surname', 'name', 'patronymic']),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Почта')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Зарегистрирован')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
