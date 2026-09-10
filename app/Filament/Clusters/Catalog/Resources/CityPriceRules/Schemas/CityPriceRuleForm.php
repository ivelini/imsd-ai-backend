<?php

namespace App\Filament\Clusters\Catalog\Resources\CityPriceRules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CityPriceRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('city_id')
                    ->label('Город')
                    ->relationship('city', 'name')
                    ->required(),
                TextInput::make('price_from')
                    ->label('Цена от')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                TextInput::make('price_to')
                    ->label('Цена до')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                TextInput::make('markup')
                    ->label('Наценка')
                    ->required()
                    ->numeric()
                    ->minValue(0),
            ]);
    }
}
