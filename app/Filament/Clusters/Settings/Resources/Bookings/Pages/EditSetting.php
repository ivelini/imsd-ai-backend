<?php

namespace App\Filament\Clusters\Settings\Resources\Bookings\Pages;

use App\Filament\Clusters\Settings\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = BookingResource::class;
}
