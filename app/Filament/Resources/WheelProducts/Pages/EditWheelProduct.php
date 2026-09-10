<?php

namespace App\Filament\Resources\WheelProducts\Pages;

use App\Filament\Resources\WheelProducts\WheelProductResource;
use App\Services\Catalog\WheelDataComposer;
use Filament\Resources\Pages\EditRecord;

class EditWheelProduct extends EditRecord
{
    protected static string $resource = WheelProductResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return app(WheelDataComposer::class)->compose($data, (int) $this->getRecord()->getKey());
    }
}
