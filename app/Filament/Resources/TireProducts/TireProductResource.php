<?php

namespace App\Filament\Resources\TireProducts;

use App\Filament\Resources\TireProducts\Pages\CreateTireProduct;
use App\Filament\Resources\TireProducts\Pages\EditTireProduct;
use App\Filament\Resources\TireProducts\Pages\ListTireProducts;
use App\Filament\Resources\TireProducts\Schemas\TireProductForm;
use App\Filament\Resources\TireProducts\Tables\TireProductsTable;
use App\Models\Catalog\Tire\TireProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TireProductResource extends Resource
{
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
            //
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
