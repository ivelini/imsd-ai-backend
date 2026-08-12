<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\GetReferences;
use App\Services\Cache\Catalog\ReferencesCacheService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Справочники', weight: 5)]
final readonly class GetReferencesController
{
    public function __construct(
        private ReferencesCacheService $referencesCache,
        private GetReferences $getReferences,
    ) {}

    /**
     *Все справочники и enum-значения для дропдаунов.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => $this->referencesCache->remember(
                fn () => $this->getReferences->execute()
            ),
        ]);
    }
}
