<?php

namespace App\Actions\Catalog;

use App\Models\Catalog\Promotion;
use App\Services\Admin\PromotionService;

/** Обновить акцию. */
final readonly class UpdatePromotion
{
    public function __construct(
        private PromotionService $promotionService,
    ) {}

    public function execute(int $id, array $data): Promotion
    {
        if (isset($data['promotable_type'])) {
            $data['promotable_type'] = $this->promotionService->resolvePromotableType($data['promotable_type']);
        }

        $promotion = Promotion::findOrFail($id);
        $promotion->update($data);

        return $promotion;
    }
}
