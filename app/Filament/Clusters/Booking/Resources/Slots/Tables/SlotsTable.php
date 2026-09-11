<?php

namespace App\Filament\Clusters\Booking\Resources\Slots\Tables;

use App\Filament\Support\PanelAction;
use App\Models\Booking\Slot;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Дата')
                    ->date()
                    ->sortable(),
                TextColumn::make('hour')
                    ->label('Час')
                    ->formatStateUsing(fn (int $state): string => sprintf('%02d:00', $state)),
                IconColumn::make('is_closed')
                    ->label('Закрыт')
                    ->boolean(),
                TextColumn::make('close_reason')
                    ->label('Причина закрытия')
                    ->placeholder('—'),
                TextColumn::make('booking.user.name')
                    ->label('Запись клиента')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('is_closed')
                    ->label('Состояние')
                    ->options([
                        true => 'Закрыт',
                        false => 'Открыт',
                    ]),
            ])
            ->recordActions([
                // Переключение закрытия: закрытие без записи, повторное открытие освобождает время
                Action::make('closeSlot')
                    ->label(fn (Slot $record): string => $record->is_closed ? 'Открыть' : 'Закрыть')
                    ->icon(fn (Slot $record): Heroicon => $record->is_closed ? Heroicon::OutlinedLockOpen : Heroicon::OutlinedLockClosed)
                    ->action(function (Slot $record): void {
                        PanelAction::run(
                            $record->is_closed ? 'Слот открыт' : 'Слот закрыт',
                            fn (): bool => $record->update([
                                'is_closed' => ! $record->is_closed,
                                'close_reason' => null,
                            ]),
                        );
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
