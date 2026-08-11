<?php

namespace App\Http\Controllers\Admin\Catalog\Tire;

use App\Actions\Warehouse\GetWarehouseStock;
use App\DTOs\Catalog\GetWarehouseStockInput;
use App\Http\Resources\Admin\Catalog\Warehouse\WarehouseStockResource;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Http\Request;

/** Остатки шины на складах с ценами и доставкой. */
final readonly class TireWarehouseStockController
{
    public function __construct(
        private GetWarehouseStock $getWarehouseStock,
    ) {}

    /** Остатки шины на всех складах. */
    public function __invoke(TireProduct $tire, Request $request): WarehouseStockResource
    {
        $cityId = (int) $request->query('city_id', 0);
        if ($cityId <= 0) {
            abort(422, 'Параметр city_id обязателен.');
        }

        $result = $this->getWarehouseStock->execute(
            new GetWarehouseStockInput('tire', $tire->id, $cityId),
        );

        return new WarehouseStockResource($result);
    }
}
