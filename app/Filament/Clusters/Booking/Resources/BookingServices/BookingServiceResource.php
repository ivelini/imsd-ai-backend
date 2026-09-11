<?php

namespace App\Filament\Clusters\Booking\Resources\BookingServices;

use App\Filament\Clusters\Booking\BookingCluster;
use App\Filament\Clusters\Booking\BookingGroupEnum;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\CreateBookingService;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\EditBookingService;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\ListBookingServices;
use App\Filament\Clusters\Booking\Resources\BookingServices\Schemas\BookingServiceForm;
use App\Filament\Clusters\Booking\Resources\BookingServices\Tables\BookingServicesTable;
use App\Models\Booking\BookingService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookingServiceResource extends Resource
{
    protected static ?string $cluster = BookingCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = BookingGroupEnum::Services->value;

    protected static ?string $model = BookingService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Услуги';

    public static function form(Schema $schema): Schema
    {
        return BookingServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingServicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookingServices::route('/'),
            'create' => CreateBookingService::route('/create'),
            'edit' => EditBookingService::route('/{record}/edit'),
        ];
    }
}
