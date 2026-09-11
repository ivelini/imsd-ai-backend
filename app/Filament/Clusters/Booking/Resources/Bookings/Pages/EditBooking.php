<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Pages;

use App\Filament\Clusters\Booking\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;
}
