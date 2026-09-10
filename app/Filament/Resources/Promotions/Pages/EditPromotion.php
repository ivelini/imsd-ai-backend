<?php

namespace App\Filament\Resources\Promotions\Pages;

use App\Actions\Promotion\RecalculatePromotedPrices;
use App\Filament\Resources\Promotions\PromotionResource;
use App\Models\Catalog\Promotion\Promotion;
use Filament\Resources\Pages\EditRecord;

class EditPromotion extends EditRecord
{
    protected static string $resource = PromotionResource::class;

    protected function afterSave(): void
    {
        // Цены затронутых товаров пересчитываются сразу, не дожидаясь плановой задачи
        /** @var Promotion $promotion */
        $promotion = $this->getRecord();

        app(RecalculatePromotedPrices::class)->forPromotions($promotion);
    }
}
