<?php

namespace App\Filament\Resources\CityPriceRules;

use App\Filament\Resources\CityPriceRules\Pages\CreateCityPriceRule;
use App\Filament\Resources\CityPriceRules\Pages\EditCityPriceRule;
use App\Filament\Resources\CityPriceRules\Pages\ListCityPriceRules;
use App\Filament\Resources\CityPriceRules\Schemas\CityPriceRuleForm;
use App\Filament\Resources\CityPriceRules\Tables\CityPriceRulesTable;
use App\Models\Delivery\CityPriceRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CityPriceRuleResource extends Resource
{
    protected static ?string $model = CityPriceRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CityPriceRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CityPriceRulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCityPriceRules::route('/'),
            'create' => CreateCityPriceRule::route('/create'),
            'edit' => EditCityPriceRule::route('/{record}/edit'),
        ];
    }
}
