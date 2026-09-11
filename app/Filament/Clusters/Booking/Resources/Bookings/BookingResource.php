<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings;

use App\Filament\Clusters\Booking\BookingCluster;
use App\Filament\Clusters\Booking\BookingGroupEnum;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\EditBooking;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\ListBookings;
use App\Filament\Clusters\Booking\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Clusters\Booking\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $cluster = BookingCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = BookingGroupEnum::Schedule->value;

    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Записи';

    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }
}
