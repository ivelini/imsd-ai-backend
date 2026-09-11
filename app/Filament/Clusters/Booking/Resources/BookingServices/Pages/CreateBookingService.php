<?php

namespace App\Filament\Clusters\Booking\Resources\BookingServices\Pages;

use App\Filament\Clusters\Booking\Resources\BookingServices\BookingServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBookingService extends CreateRecord
{
    protected static string $resource = BookingServiceResource::class;
}
