<?php

namespace App\Filament\Clusters\Booking\Resources\ComplexServices\Pages;

use App\Filament\Clusters\Booking\Resources\ComplexServices\ComplexServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditComplexService extends EditRecord
{
    protected static string $resource = ComplexServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
