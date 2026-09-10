<?php

namespace App\Filament\Clusters\Catalog\Resources\TireProducts\Pages;

use App\Filament\Clusters\Catalog\Resources\TireProducts\TireProductResource;
use App\Services\Catalog\TireDataComposer;
use Filament\Resources\Pages\EditRecord;

class EditTireProduct extends EditRecord
{
    protected static string $resource = TireProductResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return app(TireDataComposer::class)->compose($data, (int) $this->getRecord()->getKey());
    }
}
