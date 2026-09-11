<?php

namespace App\Filament\Clusters\Booking\Resources\Slots\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Дата')
                    ->required(),
                Select::make('hour')
                    ->label('Час')
                    ->options(collect(range(0, 23))->mapWithKeys(fn (int $hour): array => [$hour => sprintf('%02d:00', $hour)]))
                    ->required(),
                Toggle::make('is_closed')
                    ->label('Слот закрыт')
                    ->default(false),
                TextInput::make('close_reason')
                    ->label('Причина закрытия')
                    ->maxLength(255),
            ]);
    }
}
