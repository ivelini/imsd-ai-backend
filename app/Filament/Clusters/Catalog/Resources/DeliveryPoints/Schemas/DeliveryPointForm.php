<?php

namespace App\Filament\Clusters\Catalog\Resources\DeliveryPoints\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DeliveryPointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('city_id')
                    ->label('Город')
                    ->relationship('city', 'name')
                    ->required(),
                TextInput::make('address')
                    ->label('Адрес')
                    ->required()
                    ->maxLength(500),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('work_hours')
                    ->label('Часы работы')
                    ->maxLength(500),
                Textarea::make('info')
                    ->label('Дополнительно')
                    ->columnSpanFull()
                    ->maxLength(1000),
                Toggle::make('pickup_from_truck')
                    ->label('Забор из машины'),
            ]);
    }
}
