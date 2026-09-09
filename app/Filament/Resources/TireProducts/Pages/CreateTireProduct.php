<?php

namespace App\Filament\Resources\TireProducts\Pages;

use App\Filament\Resources\TireProducts\TireProductResource;
use App\Services\Catalog\TireDataComposer;
use Filament\Resources\Pages\CreateRecord;

class CreateTireProduct extends CreateRecord
{
    protected static string $resource = TireProductResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(TireDataComposer::class)->compose($data);
    }
}
