<?php

namespace App\Filament\Clusters\Booking\Resources\ComplexServices\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ComplexServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
                CheckboxList::make('services')
                    ->label('Состав')
                    ->relationship('services', 'name', fn ($query) => $query->where('is_active', true))
                    ->required(),
            ]);
    }
}
