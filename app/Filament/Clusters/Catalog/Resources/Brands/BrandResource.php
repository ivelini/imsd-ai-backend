<?php

namespace App\Filament\Clusters\Catalog\Resources\Brands;

use App\Filament\Clusters\Catalog\CatalogCluster;
use App\Filament\Clusters\Catalog\CatalogGroupEnum;
use App\Filament\Clusters\Catalog\Resources\Brands\Pages\CreateBrand;
use App\Filament\Clusters\Catalog\Resources\Brands\Pages\EditBrand;
use App\Filament\Clusters\Catalog\Resources\Brands\Pages\ListBrands;
use App\Filament\Clusters\Catalog\Resources\Brands\Schemas\BrandForm;
use App\Filament\Clusters\Catalog\Resources\Brands\Tables\BrandsTable;
use App\Models\Catalog\Brand\Brand;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = CatalogGroupEnum::Product->value;

    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Бренды';

    public static function form(Schema $schema): Schema
    {
        return BrandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
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
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}
