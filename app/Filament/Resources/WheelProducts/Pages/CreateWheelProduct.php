<?php

namespace App\Filament\Resources\WheelProducts\Pages;

use App\Filament\Resources\WheelProducts\WheelProductResource;
use App\Services\Catalog\WheelDataComposer;
use Filament\Resources\Pages\CreateRecord;

class CreateWheelProduct extends CreateRecord
{
    protected static string $resource = WheelProductResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(WheelDataComposer::class)->compose($data);
    }
}
