<?php

namespace App\Filament\Clusters\Booking\Resources\PriceRules\Pages;

use App\Filament\Clusters\Booking\Resources\PriceRules\PriceRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPriceRules extends ListRecords
{
    protected static string $resource = PriceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
