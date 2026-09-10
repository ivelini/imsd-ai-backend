<?php

namespace App\Filament\Resources\WheelProducts;

use App\Filament\Resources\WheelProducts\Pages\CreateWheelProduct;
use App\Filament\Resources\WheelProducts\Pages\EditWheelProduct;
use App\Filament\Resources\WheelProducts\Pages\ListWheelProducts;
use App\Filament\Resources\WheelProducts\Schemas\WheelProductForm;
use App\Filament\Resources\WheelProducts\Tables\WheelProductsTable;
use App\Models\Catalog\Wheel\WheelProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WheelProductResource extends Resource
{
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
            //
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
