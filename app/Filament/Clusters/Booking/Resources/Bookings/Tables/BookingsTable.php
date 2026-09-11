<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Tables;

use App\Enums\Booking\BookingStatus;
use App\Models\Booking\Booking;
use App\ValueObjects\Money;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slot.date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Время')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 5)),
                TextColumn::make('user.name')
                    ->label('Клиент')
                    ->searchable(),
                TextColumn::make('user.phone')
                    ->label('Телефон'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (BookingStatus $state): string => $state->label()),
                TextColumn::make('total_price')
                    ->label('Сумма')
                    ->formatStateUsing(fn (?Money $state): string => $state?->formatted() ?? '—'),
                TextColumn::make('items_count')
                    ->label('Услуг')
                    ->counts('items'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(
                        collect(BookingStatus::cases())
                            ->mapWithKeys(fn (BookingStatus $status): array => [$status->value => $status->label()])
                    ),
            ])
            ->recordActions([
                Action::make('arrive')
                    ->label('Приехал')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn (Booking $record): bool => $record->status === BookingStatus::Confirmed)
                    ->action(fn (Booking $record): bool => $record->update(['status' => BookingStatus::Arrived])),
                Action::make('complete')
                    ->label('Завершить')
                    ->icon(Heroicon::OutlinedFlag)
                    ->visible(fn (Booking $record): bool => in_array($record->status, [BookingStatus::Confirmed, BookingStatus::Arrived], true))
                    ->action(fn (Booking $record): bool => $record->update(['status' => BookingStatus::Done])),
                Action::make('noShow')
                    ->label('Неявка')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->visible(fn (Booking $record): bool => $record->status === BookingStatus::Confirmed)
                    ->action(fn (Booking $record): bool => $record->update(['status' => BookingStatus::NoShow])),
                // Отмена — с обязательной причиной (модальная форма)
                Action::make('cancel')
                    ->label('Отменить')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->visible(fn (Booking $record): bool => in_array($record->status, [BookingStatus::Confirmed, BookingStatus::Arrived], true))
                    ->form([
                        TextInput::make('cancel_reason')
                            ->label('Причина')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(fn (Booking $record, array $data): bool => $record->update([
                        'status' => BookingStatus::Cancelled,
                        'cancel_reason' => $data['cancel_reason'],
                    ])),
                EditAction::make(),
            ]);
    }
}
