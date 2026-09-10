<?php

namespace App\Filament\Clusters\Catalog\Resources\WarehouseMarkupRules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WarehouseMarkupRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('warehouse_id')
                    ->label('Склад')
                    ->relationship('warehouse', 'name')
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
                TextInput::make('coefficient')
                    ->label('Коэффициент')
                    ->required()
                    ->numeric()
                    ->minValue(1),
            ]);
    }
}
