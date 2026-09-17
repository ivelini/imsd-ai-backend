<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Tables;

use App\Enums\Booking\BookingStatus;
use App\Filament\Support\MoneyColumn;
use App\Filament\Support\PeriodFilter;
use App\Models\Booking\Booking;
use App\Models\Booking\Slot;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Порядок — дата слота, затем время начала: одно время суток перемешало бы дни недели
            // (запись на завтра в 09:00 встала бы выше сегодняшней на 15:00).
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy(Slot::select('date')->whereColumn('booking_slots.id', 'bookings.slot_id'))
                ->orderBy('start_time'))
            ->columns([
                TextColumn::make('slot.date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Время')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 5)),
                TextColumn::make('user.full_name')
                    ->label('Клиент')
                    // Склейка ФИО — не колонка: поиск идёт по частям имени в карточке клиента
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'user',
                        fn (Builder $user): Builder => $user->where('name', 'like', "%{$search}%")->orWhere('surname', 'like', "%{$search}%"),
                    )),
                TextColumn::make('user.phone')
                    ->label('Телефон'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (BookingStatus $state): string => $state->label()),
                MoneyColumn::make('total_price', 'Сумма'),
                TextColumn::make('items_count')
                    ->label('Услуг')
                    ->counts('items'),
            ])
            // Фильтры над таблицей — как в листинге слотов; период с дефолтом «текущая неделя».
            ->filtersLayout(FiltersLayout::AboveContent)
            // Применяются сразу: кнопка быстрого выбора должна сужать таблицу одним кликом, без «Применить».
            ->deferFilters(false)
            ->defaultPaginationPageOption(50)
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(
                        collect(BookingStatus::cases())
                            ->mapWithKeys(fn (BookingStatus $status): array => [$status->value => $status->label()])
                    ),
                // Период — по дате слота записи.
                PeriodFilter::make(
                    fn (Builder $query, CarbonImmutable $from, CarbonImmutable $to): Builder => $query->whereHas(
                        'slot',
                        fn (Builder $slot): Builder => $slot->whereBetween('date', [$from, $to]),
                    ),
                )->columnSpan(4),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('Завершить')
                    ->icon(Heroicon::OutlinedFlag)
                    ->visible(fn (Booking $record): bool => in_array($record->status, [BookingStatus::Confirmed], true))
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
                    ->visible(fn (Booking $record): bool => in_array($record->status, [BookingStatus::Confirmed], true))
                    ->schema([
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
