<?php

namespace App\Actions\Promotion;

use App\Models\Catalog\Promotion\Promotion;
use App\Services\Admin\PromotionService;

/** Создать акцию. */
final readonly class CreatePromotion
{
    public function __construct(
        private PromotionService $promotionService,
    ) {}

    public function execute(array $data): Promotion
    {
        if (isset($data['promotable_type'])) {
            $data['promotable_type'] = $this->promotionService->resolvePromotableType($data['promotable_type']);
        }

        return Promotion::create($data);
    }
}
