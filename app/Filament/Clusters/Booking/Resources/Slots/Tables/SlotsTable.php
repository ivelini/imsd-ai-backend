<?php

namespace App\Filament\Clusters\Booking\Resources\Slots\Tables;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\SlotPeriod;
use App\Filament\Support\PanelAction;
use App\Models\Booking\Booking;
use App\Models\Booking\Slot;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Колонка «Клиенты» читает записи слота — грузим заранее, иначе запрос на каждую строку.
            // Прошедшие дни листингу не нужны: сетка хранит слоты истории (закрытые и с записями).
            // Сравнение — с началом дня: в колонке-дате время нулевое, `>= now()` срезал бы и сегодня.
            ->modifyQueryUsing(fn (Builder $query) => $query->with('bookings.user')->where('date', '>=', now()->startOfDay()))
            ->defaultSort(fn (Builder $query): Builder => $query->orderBy('date')->orderBy('hour'))
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
                TextColumn::make('clients')
                    ->label('Клиенты')
                    ->state(fn (Slot $record): array => self::clientLines($record))
                    ->listWithLineBreaks()
                    ->placeholder('—'),
            ])
            // Селекты над таблицей: период с дефолтом «Сегодня» объясняет, почему виден один день.
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                SelectFilter::make('is_closed')
                    ->label('Состояние')
                    ->options([
                        true => 'Закрыт',
                        false => 'Открыт',
                    ]),
                SelectFilter::make('period')
                    ->label('Период')
                    ->options(SlotPeriod::class)
                    // Пустое значение — вся сетка (так ведёт себя сброс фильтров), иначе сужение по датам.
                    ->query(function (Builder $query, array $data): Builder {
                        $period = SlotPeriod::tryFrom((string) ($data['value'] ?? ''));

                        if ($period === null) {
                            return $query;
                        }

                        $range = $period->range(CarbonImmutable::now());

                        return $query->whereBetween('date', [$range['from'], $range['to']]);
                    }),
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

    /**
     * Строки клиентов слота: «Имя — телефон»; отменённые записи пропускаем — время они уже освободили.
     *
     * @return array<int, string>
     */
    private static function clientLines(Slot $record): array
    {
        return $record->bookings
            ->reject(fn (Booking $booking): bool => $booking->status === BookingStatus::Cancelled)
            ->sortBy('id')
            ->map(fn (Booking $booking): string => self::clientLine($booking))
            ->values()
            ->all();
    }

    /** Телефон может быть не заполнен (клиент без записи по SMS) — тогда только имя. */
    private static function clientLine(Booking $booking): string
    {
        $user = $booking->user;
        $startTime = Carbon::parse($booking->start_time)->format('H:i');

        return $user->phone === null
            ? $user->name
            : "{$startTime} : {$user->name} — {$user->phone}";
    }
}
