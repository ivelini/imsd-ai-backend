<?php

namespace App\Filament\Clusters\Catalog\Resources\DeliverySchedules;

use App\Filament\Clusters\Catalog\CatalogCluster;
use App\Filament\Clusters\Catalog\CatalogGroupEnum;
use App\Filament\Clusters\Catalog\Resources\DeliverySchedules\Pages\CreateDeliverySchedule;
use App\Filament\Clusters\Catalog\Resources\DeliverySchedules\Pages\EditDeliverySchedule;
use App\Filament\Clusters\Catalog\Resources\DeliverySchedules\Pages\ListDeliverySchedules;
use App\Filament\Clusters\Catalog\Resources\DeliverySchedules\Schemas\DeliveryScheduleForm;
use App\Filament\Clusters\Catalog\Resources\DeliverySchedules\Tables\DeliverySchedulesTable;
use App\Models\Delivery\DeliverySchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeliveryScheduleResource extends Resource
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = CatalogGroupEnum::Warehouse->value;

    protected static ?int $navigationSort = 20;

    protected static ?string $model = DeliverySchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'График доставки';

    public static function form(Schema $schema): Schema
    {
        return DeliveryScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliverySchedulesTable::configure($table);
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
            'index' => ListDeliverySchedules::route('/'),
            'create' => CreateDeliverySchedule::route('/create'),
            'edit' => EditDeliverySchedule::route('/{record}/edit'),
        ];
    }
}
