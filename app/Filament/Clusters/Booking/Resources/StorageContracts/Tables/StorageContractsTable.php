<?php

namespace App\Filament\Clusters\Booking\Resources\StorageContracts\Tables;

use App\Enums\Storage\StorageContractStatus;
use App\Filament\Support\MoneyColumn;
use App\Models\Storage\StorageContract;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StorageContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_on', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Номер'),
                TextColumn::make('user.full_name')
                    ->label('Клиент')
                    // Склейка ФИО — не колонка: поиск идёт по частям имени в карточке клиента
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'user',
                        fn (Builder $user): Builder => $user->where('name', 'like', "%{$search}%")->orWhere('surname', 'like', "%{$search}%"),
                    )),
                TextColumn::make('user.phone')
                    ->label('Телефон'),
                TextColumn::make('starts_on')
                    ->label('Срок с')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('ends_on')
                    ->label('Срок по')
                    ->date('d.m.Y')
                    ->sortable(),
                MoneyColumn::make('price', 'Стоимость'),
                TextColumn::make('items_count')
                    ->label('Позиций')
                    ->counts('items'),
                TextColumn::make('status')
                    ->label('Состояние')
                    ->badge()
                    ->formatStateUsing(fn (StorageContractStatus $state): string => $state->label()),
            ])
            ->recordActions([
                // Выдача колёс закрывает договор: место освободилось, срок больше не идёт
                Action::make('close')
                    ->label('Выдать колёса')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->visible(fn (StorageContract $record): bool => $record->status === StorageContractStatus::Active)
                    ->action(fn (StorageContract $record): bool => $record->update([
                        'status' => StorageContractStatus::Closed,
                        'closed_at' => now(),
                    ])),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
