<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Catalog\Wheel\GetWheelFilterValues;
use App\DTOs\Catalog\Wheel\WheelFilterValues;
use App\Http\Requests\Catalog\WheelFilterValuesRequest;
use App\Http\Resources\Catalog\WheelFilterValuesResource;
use App\Models\Delivery\City;
use App\Preconditions\Geo\EnsureCityExists;
use App\Services\Cache\Catalog\WheelFilterValuesCacheService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;

/** Фасетные значения фильтра дисков для выбранного города. */
final readonly class GetWheelFilterValuesController
{
    public function __construct(
        private WheelFilterValuesCacheService $wheelFilterCache,
        private GetWheelFilterValues $getWheelFilterValues,
        private EnsureCityExists $ensureCityExists,
        private string $defaultCityName,
    ) {}

    /**
     * Фасеты фильтра дисков для выбранного города (по умолчанию — из config/shop.php).
     *
     * Принимает query-фильтры и сужает фасеты до доступных значений.
     */
    #[Group('Справочник / фильтр', weight: 10)]
    #[Response(type: 'array{data: array{width: list<array{label: string, value: string}>, diameter: list<array{label: int, value: int}>, pcd: list<array{label: string, value: string}>, et: list<array{label: string, value: string}>, hub_diameter: list<array{label: string, value: string}>, type: list<array{label: string, value: string}>, color: list<array{label: string, value: string}>, brand: list<array{label: string, value: string}>, country: list<array{label: string, value: string}>, delivery: list<array{label: string, value: string}>, price: array{min: float, max: float}}}')]
    public function __invoke(WheelFilterValuesRequest $request): WheelFilterValuesResource
    {
        $cityId = $request->validated('city_id');
        $filters = $request->safe()->except(['city_id', 'city']);

        // Precondition — только при промахе кеша, перед Action (hit → Action не вызывается)
        $data = $this->wheelFilterCache->remember(function () use ($cityId, $filters): array {
            $city = $cityId !== null
                ? City::findOrFail($cityId) // страховка от удаления города после exists-валидации
                : $this->ensureCityExists->ensure($this->defaultCityName);

            return $this->getWheelFilterValues->execute($city->id, $filters);
        }, $cityId, $filters);

        return new WheelFilterValuesResource(WheelFilterValues::fromArray($data));
    }
}
