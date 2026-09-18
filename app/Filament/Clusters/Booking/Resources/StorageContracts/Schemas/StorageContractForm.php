<?php

namespace App\Filament\Clusters\Booking\Resources\StorageContracts\Schemas;

use App\Models\User;
use App\ValueObjects\Money;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Одна схема на создание и правку: клиент, срок, стоимость и позиции — что оставлено на хранении.
 * Статуса в форме нет: договор закрывает действие «Выдать колёса» в листинге.
 */
class StorageContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Клиент')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => trim($record->full_name.' '.$record->phone))
                    ->searchable(['name', 'surname', 'phone'])
                    ->required(),
                TextInput::make('personal_document')
                    ->label('Документ, удостоверяющий личность')
                    ->required(),
                DatePicker::make('starts_on')
                    ->label('Срок с')
                    ->required(),
                // «До» раньше «от» — не срок; равенство допустимо (сдал и забрал в один день)
                DatePicker::make('ends_on')
                    ->label('Срок по')
                    ->required()
                    ->afterOrEqual('starts_on'),
                // Рубли в форме, копейки в БД (ADR 0013)
                TextInput::make('price')
                    ->label('Стоимость, ₽')
                    ->required()
                    ->rules(['numeric', 'min:0'])
                    ->formatStateUsing(fn (?Money $state): string => $state === null ? '0' : (string) $state->toRubles())
                    ->dehydrateStateUsing(fn (string $state): Money => Money::fromRubles($state)),
                // Позиции — повторитель со связью: строки синхронизируются по id (правка не пересоздаёт их)
                Repeater::make('items')
                    ->label('Что оставлено на хранении')
                    ->relationship()
                    ->required()
                    ->minItems(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Наименование')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Особенности')
                            ->maxLength(1000),
                    ]),
            ]);
    }
}
