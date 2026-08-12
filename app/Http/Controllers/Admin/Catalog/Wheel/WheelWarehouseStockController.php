<?php

namespace App\Http\Controllers\Admin\Catalog\Wheel;

use App\Actions\Warehouse\GetWarehouseStock;
use App\DTOs\Catalog\GetWarehouseStockInput;
use App\Http\Resources\Admin\Catalog\Warehouse\WarehouseStockRowResource;
use App\Models\Catalog\Wheel\WheelProduct;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Остатки диска на складах с ценами и доставкой. */
#[Group('Каталог / диски', weight: 40)]
final readonly class WheelWarehouseStockController
{
    public function __construct(
        private GetWarehouseStock $getWarehouseStock,
    ) {}

    /** Остатки диска на всех складах. */
    public function __invoke(WheelProduct $wheel, Request $request): AnonymousResourceCollection
    {
        $cityId = (int) $request->query('city_id', 0);

        $result = $this->getWarehouseStock->execute(
            new GetWarehouseStockInput('wheel', $wheel->id, $cityId),
        );

        return WarehouseStockRowResource::collection($result->rows);
    }
}
