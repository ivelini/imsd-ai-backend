<?php

namespace App\Filament\Clusters\Catalog\Resources\WarehouseMarkupRules\Pages;

use App\Filament\Clusters\Catalog\Resources\WarehouseMarkupRules\WarehouseMarkupRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseMarkupRules extends ListRecords
{
    protected static string $resource = WarehouseMarkupRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
