<?php

namespace App\Filament\Clusters\Catalog\Resources\ProductModels\Pages;

use App\Filament\Clusters\Catalog\Resources\ProductModels\ProductModelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductModels extends ListRecords
{
    protected static string $resource = ProductModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
