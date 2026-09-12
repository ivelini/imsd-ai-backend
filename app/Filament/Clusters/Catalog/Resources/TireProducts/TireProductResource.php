<?php

namespace App\Filament\Clusters\Catalog\Resources\TireProducts;

use App\Filament\Clusters\Catalog\CatalogCluster;
use App\Filament\Clusters\Catalog\CatalogGroupEnum;
use App\Filament\Clusters\Catalog\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Filament\Clusters\Catalog\Resources\Products\RelationManagers\StocksRelationManager;
use App\Filament\Clusters\Catalog\Resources\TireProducts\Pages\CreateTireProduct;
use App\Filament\Clusters\Catalog\Resources\TireProducts\Pages\EditTireProduct;
use App\Filament\Clusters\Catalog\Resources\TireProducts\Pages\ListTireProducts;
use App\Filament\Clusters\Catalog\Resources\TireProducts\Schemas\TireProductForm;
use App\Filament\Clusters\Catalog\Resources\TireProducts\Tables\TireProductsTable;
use App\Models\Catalog\Tire\TireProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TireProductResource extends Resource
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = CatalogGroupEnum::Product->value;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Шины';

    protected static ?string $pluralModelLabel = 'Каталог шин';

    protected static ?string $model = TireProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TireProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TireProductsTable::configure($table);
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
            'index' => ListTireProducts::route('/'),
            'create' => CreateTireProduct::route('/create'),
            'edit' => EditTireProduct::route('/{record}/edit'),
        ];
    }
}
