<?php

namespace App\Http\Controllers\Admin\Catalog\Warehouse;

use App\Actions\Warehouse\GetWarehouseStock;
use App\DTOs\Catalog\GetWarehouseStockInput;
use App\Http\Resources\Admin\Catalog\Warehouse\WarehouseStockResource;
use Illuminate\Http\Request;

/** Остатки товара на складах с ценами и доставкой.
 *
 * @group Складские остатки
 */
final readonly class WarehouseStockController
{
    public function __construct(
        private GetWarehouseStock $getWarehouseStock,
    ) {}

    /**
     * Остатки товара на всех складах.
     *
     * @queryParam city_id int required ID города.
     *
     * @authenticated
     */
    public function __invoke(Request $request): WarehouseStockResource
    {
        $productId = (int) ($request->route('tireId') ?? $request->route('wheelId'));
        $cityId = (int) $request->query('city_id', 0);
        $productType = $request->route('tireId') !== null ? 'tire' : 'wheel';

        if ($cityId <= 0) {
            abort(422, 'Параметр city_id обязателен.');
        }

        $result = $this->getWarehouseStock->execute(
            new GetWarehouseStockInput($productType, $productId, $cityId)
        );

        return new WarehouseStockResource($result);
    }
}
