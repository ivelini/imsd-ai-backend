<?php

namespace App\Http\Controllers\Admin\Catalog\Tire;

use App\Actions\Warehouse\GetWarehouseStock;
use App\DTOs\Catalog\GetWarehouseStockInput;
use App\Http\Resources\Admin\Catalog\Warehouse\WarehouseStockRowResource;
use App\Models\Catalog\Tire\TireProduct;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Остатки шины на складах с ценами и доставкой. */
#[Group('Каталог / шины', weight: 30)]
final readonly class TireWarehouseStockController
{
    public function __construct(
        private GetWarehouseStock $getWarehouseStock,
    ) {}

    /** Остатки шины на всех складах. */
    public function __invoke(TireProduct $tire, Request $request): AnonymousResourceCollection
    {
        $cityId = (int) $request->query('city_id', 0);

        $result = $this->getWarehouseStock->execute(
            new GetWarehouseStockInput('tire', $tire->id, $cityId),
        );

        return WarehouseStockRowResource::collection($result->rows);
    }
}
