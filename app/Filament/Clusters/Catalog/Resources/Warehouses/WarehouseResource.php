<?php

namespace App\Filament\Clusters\Catalog\Resources\Warehouses;

use App\Filament\Clusters\Catalog\CatalogCluster;
use App\Filament\Clusters\Catalog\CatalogGroupEnum;
use App\Filament\Clusters\Catalog\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Clusters\Catalog\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Clusters\Catalog\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Clusters\Catalog\Resources\Warehouses\Schemas\WarehouseForm;
use App\Filament\Clusters\Catalog\Resources\Warehouses\Tables\WarehousesTable;
use App\Models\Catalog\Warehouse\Warehouse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WarehouseResource extends Resource
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = CatalogGroupEnum::Warehouse->value;

    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Склады поставщиков';

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
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
            'index' => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }
}
