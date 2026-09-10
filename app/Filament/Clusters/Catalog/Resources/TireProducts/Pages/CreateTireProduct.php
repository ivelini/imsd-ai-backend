<?php

namespace App\Filament\Clusters\Catalog\Resources\TireProducts\Pages;

use App\Filament\Clusters\Catalog\Resources\TireProducts\TireProductResource;
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
