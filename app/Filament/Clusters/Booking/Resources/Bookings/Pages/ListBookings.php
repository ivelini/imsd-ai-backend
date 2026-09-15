<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Pages;

use App\Filament\Clusters\Booking\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;
}
