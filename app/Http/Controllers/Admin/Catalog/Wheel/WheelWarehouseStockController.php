<?php

namespace App\Http\Controllers\Admin\Catalog\Wheel;

use App\Actions\Warehouse\GetWarehouseStock;
use App\DTOs\Catalog\GetWarehouseStockInput;
use App\Http\Resources\Admin\Catalog\Warehouse\WarehouseStockResource;
use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Http\Request;

/** Остатки диска на складах с ценами и доставкой. */
final readonly class WheelWarehouseStockController
{
    public function __construct(
        private GetWarehouseStock $getWarehouseStock,
    ) {}

    /** Остатки диска на всех складах. */
    public function __invoke(WheelProduct $wheel, Request $request): WarehouseStockResource
    {
        $cityId = (int) $request->query('city_id', 0);
        if ($cityId <= 0) {
            abort(422, 'Параметр city_id обязателен.');
        }

        $result = $this->getWarehouseStock->execute(
            new GetWarehouseStockInput('wheel', $wheel->id, $cityId),
        );

        return new WarehouseStockResource($result);
    }
}
