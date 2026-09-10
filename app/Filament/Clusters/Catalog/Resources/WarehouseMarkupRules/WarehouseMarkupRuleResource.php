<?php

namespace App\Filament\Clusters\Catalog\Resources\WarehouseMarkupRules;

use App\Filament\Clusters\Catalog\CatalogCluster;
use App\Filament\Clusters\Catalog\CatalogGroupEnum;
use App\Filament\Clusters\Catalog\Resources\WarehouseMarkupRules\Pages\CreateWarehouseMarkupRule;
use App\Filament\Clusters\Catalog\Resources\WarehouseMarkupRules\Pages\EditWarehouseMarkupRule;
use App\Filament\Clusters\Catalog\Resources\WarehouseMarkupRules\Pages\ListWarehouseMarkupRules;
use App\Filament\Clusters\Catalog\Resources\WarehouseMarkupRules\Schemas\WarehouseMarkupRuleForm;
use App\Filament\Clusters\Catalog\Resources\WarehouseMarkupRules\Tables\WarehouseMarkupRulesTable;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WarehouseMarkupRuleResource extends Resource
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = CatalogGroupEnum::Warehouse->value;

    protected static ?string $model = WarehouseMarkupRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Наценка от поставщика';

    public static function form(Schema $schema): Schema
    {
        return WarehouseMarkupRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseMarkupRulesTable::configure($table);
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
            'index' => ListWarehouseMarkupRules::route('/'),
            'create' => CreateWarehouseMarkupRule::route('/create'),
            'edit' => EditWarehouseMarkupRule::route('/{record}/edit'),
        ];
    }
}
