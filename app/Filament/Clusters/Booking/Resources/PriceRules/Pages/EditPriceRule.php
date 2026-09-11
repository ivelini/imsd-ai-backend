<?php

namespace App\Filament\Clusters\Booking\Resources\PriceRules\Pages;

use App\Filament\Clusters\Booking\Resources\PriceRules\PriceRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPriceRule extends EditRecord
{
    protected static string $resource = PriceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
