<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\GetReferences;
use App\Services\Cache\Catalog\ReferencesCacheService;
use Illuminate\Http\JsonResponse;

/** Все справочники и enum-значения для дропдаунов.
 *
 */
final readonly class GetReferencesController
{
    public function __construct(
        private ReferencesCacheService $referencesCache,
        private GetReferences $getReferences,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => $this->referencesCache->remember(
                fn () => $this->getReferences->execute()
            ),
        ]);
    }
}
