<?php

namespace App\Filament\Clusters\Booking\Resources\PriceRules;

use App\Filament\Clusters\Booking\BookingCluster;
use App\Filament\Clusters\Booking\BookingGroupEnum;
use App\Filament\Clusters\Booking\Resources\PriceRules\Pages\CreatePriceRule;
use App\Filament\Clusters\Booking\Resources\PriceRules\Pages\EditPriceRule;
use App\Filament\Clusters\Booking\Resources\PriceRules\Pages\ListPriceRules;
use App\Filament\Clusters\Booking\Resources\PriceRules\Schemas\PriceRuleForm;
use App\Filament\Clusters\Booking\Resources\PriceRules\Tables\PriceRulesTable;
use App\Models\Booking\PriceRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PriceRuleResource extends Resource
{
    protected static ?string $cluster = BookingCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = BookingGroupEnum::Services->value;

    protected static ?string $model = PriceRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Прайс-правила';

    public static function form(Schema $schema): Schema
    {
        return PriceRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PriceRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPriceRules::route('/'),
            'create' => CreatePriceRule::route('/create'),
            'edit' => EditPriceRule::route('/{record}/edit'),
        ];
    }
}
