<?php

namespace App\Filament\Resources\DeliverySchedules;

use App\Filament\Resources\DeliverySchedules\Pages\CreateDeliverySchedule;
use App\Filament\Resources\DeliverySchedules\Pages\EditDeliverySchedule;
use App\Filament\Resources\DeliverySchedules\Pages\ListDeliverySchedules;
use App\Filament\Resources\DeliverySchedules\Schemas\DeliveryScheduleForm;
use App\Filament\Resources\DeliverySchedules\Tables\DeliverySchedulesTable;
use App\Models\Delivery\DeliverySchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeliveryScheduleResource extends Resource
{
    protected static ?string $model = DeliverySchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
