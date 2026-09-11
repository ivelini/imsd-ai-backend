<?php

namespace App\Filament\Clusters\Booking\Resources\Slots;

use App\Filament\Clusters\Booking\BookingCluster;
use App\Filament\Clusters\Booking\BookingGroupEnum;
use App\Filament\Clusters\Booking\Resources\Slots\Pages\CreateSlot;
use App\Filament\Clusters\Booking\Resources\Slots\Pages\EditSlot;
use App\Filament\Clusters\Booking\Resources\Slots\Pages\ListSlots;
use App\Filament\Clusters\Booking\Resources\Slots\Schemas\SlotForm;
use App\Filament\Clusters\Booking\Resources\Slots\Tables\SlotsTable;
use App\Models\Booking\Slot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SlotResource extends Resource
{
    protected static ?string $cluster = BookingCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = BookingGroupEnum::Schedule->value;

    protected static ?string $model = Slot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $navigationLabel = 'Слоты';

    public static function form(Schema $schema): Schema
    {
        return SlotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SlotsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSlots::route('/'),
            'create' => CreateSlot::route('/create'),
            'edit' => EditSlot::route('/{record}/edit'),
        ];
    }
}
