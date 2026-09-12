<?php

namespace App\Filament\Clusters\Catalog\Resources\WheelProducts;

use App\Filament\Clusters\Catalog\CatalogCluster;
use App\Filament\Clusters\Catalog\CatalogGroupEnum;
use App\Filament\Clusters\Catalog\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Filament\Clusters\Catalog\Resources\Products\RelationManagers\StocksRelationManager;
use App\Filament\Clusters\Catalog\Resources\WheelProducts\Pages\CreateWheelProduct;
use App\Filament\Clusters\Catalog\Resources\WheelProducts\Pages\EditWheelProduct;
use App\Filament\Clusters\Catalog\Resources\WheelProducts\Pages\ListWheelProducts;
use App\Filament\Clusters\Catalog\Resources\WheelProducts\Schemas\WheelProductForm;
use App\Filament\Clusters\Catalog\Resources\WheelProducts\Tables\WheelProductsTable;
use App\Models\Catalog\Wheel\WheelProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WheelProductResource extends Resource
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = CatalogGroupEnum::Product->value;

    protected static ?string $navigationLabel = 'Диски';

    protected static ?int $navigationSort = 2;

    protected static ?string $model = WheelProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    public static function form(Schema $schema): Schema
    {
        return WheelProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WheelProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
            StocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWheelProducts::route('/'),
            'create' => CreateWheelProduct::route('/create'),
            'edit' => EditWheelProduct::route('/{record}/edit'),
        ];
    }
}
