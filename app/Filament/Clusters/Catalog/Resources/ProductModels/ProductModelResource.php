<?php

namespace App\Filament\Clusters\Catalog\Resources\ProductModels;

use App\Filament\Clusters\Catalog\CatalogCluster;
use App\Filament\Clusters\Catalog\CatalogGroupEnum;
use App\Filament\Clusters\Catalog\Resources\ProductModels\Pages\CreateProductModel;
use App\Filament\Clusters\Catalog\Resources\ProductModels\Pages\EditProductModel;
use App\Filament\Clusters\Catalog\Resources\ProductModels\Pages\ListProductModels;
use App\Filament\Clusters\Catalog\Resources\ProductModels\Schemas\ProductModelForm;
use App\Filament\Clusters\Catalog\Resources\ProductModels\Tables\ProductModelsTable;
use App\Models\Catalog\Model\ProductModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductModelResource extends Resource
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = CatalogGroupEnum::Product->value;

    protected static ?string $model = ProductModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Модели';

    public static function form(Schema $schema): Schema
    {
        return ProductModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductModelsTable::configure($table);
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
            'index' => ListProductModels::route('/'),
            'create' => CreateProductModel::route('/create'),
            'edit' => EditProductModel::route('/{record}/edit'),
        ];
    }
}
