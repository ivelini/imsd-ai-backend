<?php

namespace App\Filament\Clusters\Booking\Resources\Settings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Ключ — primary key таблицы settings, менять его на edit нельзя
                TextInput::make('key')
                    ->label('Ключ')
                    ->required()
                    ->maxLength(255)
                    ->disabledOn('edit'),
                TextInput::make('value')
                    ->label('Значение')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
