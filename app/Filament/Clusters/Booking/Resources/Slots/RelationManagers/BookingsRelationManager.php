<?php

namespace App\Filament\Clusters\Booking\Resources\Slots\RelationManagers;

use App\Enums\Booking\BookingStatus;
use App\Filament\Clusters\Booking\Resources\Bookings\BookingResource;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\ValueObjects\Money;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Записи слота на его странице правки: состав услуг, сумма и переходы в карточку записи.
 *
 * Добавление — переход на страницу создания записи с подставленным слотом: форма создания одна,
 * создаёт её тот же `CreateAdminBooking`, что и раздел «Записи». Удаление — обычное: правил перед
 * ним нет, привязка закрытия снимается FK-правилом (`booking_slots.booking_id` — nullOnDelete),
 * слот остаётся закрытым и открывается кнопкой на странице сетки.
 */
class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Записи';

    public function table(Table $table): Table
    {
        return $table
            // Колонка «Услуги» читает состав каждой записи — грузим заранее, иначе запрос на строку.
            ->modifyQueryUsing(fn (Builder $query) => $query->with('items.service'))
            ->columns([
                TextColumn::make('start_time')
                    ->label('Время')
                    ->state(fn (Booking $record): string => Carbon::parse($record->start_time)->format('H:i'))
                    ->searchable(),
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
                TextColumn::make('services')
                    ->label('Услуги')
                    ->state(fn (Booking $record): array => self::serviceLines($record))
                    ->listWithLineBreaks()
                    ->placeholder('—'),
                TextColumn::make('total_price')
                    ->label('Сумма')
                    ->formatStateUsing(fn (Money $state): string => $state->formatted()),
            ])
            ->headerActions([
                Action::make('createBooking')
                    ->label('Добавить запись')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => BookingResource::getUrl('create', ['slot_id' => $this->getOwnerRecord()->getKey()])),
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
                EditAction::make()
                    ->url(fn (Booking $record): string => BookingResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ]);
    }

    /**
     * Строки состава записи: «Шиномонтаж R16 — 2 шт» (как строка остатка в каталоге).
     *
     * @return array<int, string>
     */
    private static function serviceLines(Booking $record): array
    {
        return $record->items
            ->sortBy('id')
            ->map(fn (BookingItem $item): string => "{$item->service->name} — {$item->quantity} шт")
            ->values()
            ->all();
    }
}
