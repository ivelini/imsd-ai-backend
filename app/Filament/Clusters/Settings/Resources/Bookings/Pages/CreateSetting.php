<?php

namespace App\Filament\Clusters\Settings\Resources\Bookings\Pages;

use App\Filament\Clusters\Settings\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
    protected static string $resource = BookingResource::class;
}
