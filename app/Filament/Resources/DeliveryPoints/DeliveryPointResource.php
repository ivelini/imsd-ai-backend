<?php

namespace App\Filament\Resources\DeliveryPoints;

use App\Filament\Resources\DeliveryPoints\Pages\CreateDeliveryPoint;
use App\Filament\Resources\DeliveryPoints\Pages\EditDeliveryPoint;
use App\Filament\Resources\DeliveryPoints\Pages\ListDeliveryPoints;
use App\Filament\Resources\DeliveryPoints\Schemas\DeliveryPointForm;
use App\Filament\Resources\DeliveryPoints\Tables\DeliveryPointsTable;
use App\Models\Delivery\DeliveryPoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeliveryPointResource extends Resource
{
    protected static ?string $model = DeliveryPoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Точки выдачи';

    public static function form(Schema $schema): Schema
    {
        return DeliveryPointForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryPointsTable::configure($table);
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
            'index' => ListDeliveryPoints::route('/'),
            'create' => CreateDeliveryPoint::route('/create'),
            'edit' => EditDeliveryPoint::route('/{record}/edit'),
        ];
    }
}
