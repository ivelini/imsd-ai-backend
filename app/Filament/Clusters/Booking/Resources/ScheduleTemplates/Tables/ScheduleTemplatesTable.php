<?php

namespace App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Tables;

use App\Enums\Common\WeekDay;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScheduleTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('weekday')
                    ->label('День недели')
                    ->formatStateUsing(fn (int $state): string => WeekDay::from($state)->label()),
                TextColumn::make('open_time')
                    ->label('Открытие')
                    ->formatStateUsing(fn (?string $state): string => $state === null ? 'Выходной' : substr($state, 0, 5)),
                TextColumn::make('close_time')
                    ->label('Закрытие')
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : substr($state, 0, 5)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
