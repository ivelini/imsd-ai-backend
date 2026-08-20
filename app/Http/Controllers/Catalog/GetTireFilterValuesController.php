<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Catalog\Tire\GetTireFilterValues;
use App\DTOs\Catalog\Tire\TireFilterValues;
use App\Http\Requests\Catalog\TireFilterValuesRequest;
use App\Http\Resources\Catalog\TireFilterValuesResource;
use App\Models\Delivery\City;
use App\Preconditions\Geo\EnsureCityExists;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;

/** Фасетные значения фильтра каталога шин. */
final readonly class GetTireFilterValuesController
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
        private GetTireFilterValues $getTireFilterValues,
        private EnsureCityExists $ensureCityExists,
        private string $defaultCityName,
    ) {}

    /**
     * Все значения фильтра шин для выбранного города
     * (без city_id — для города по умолчанию из config/shop.php).
     */
    #[Group('Справочник / фильтр', weight: 10)]
    #[Response(type: 'array{data: array{width: list<array{label: int, value: int}>, profile: list<array{label: int, value: int}>, diameter: list<array{label: string, value: string}>, season: list<array{label: string, value: string}>, studded: list<array{label: string, value: string}>, brand: list<array{label: string, value: string}>, country: list<array{label: string, value: string}>, delivery: list<array{label: string, value: string}>, price: array{min: float, max: float}}}')]
    public function __invoke(TireFilterValuesRequest $request): TireFilterValuesResource
    {
        $cityId = $request->validated('city_id');
        $filters = $request->safe()->except('city_id');

        // Precondition — только при промахе кеша, перед Action (hit → Action не вызывается)
        $data = $this->tireFilterCache->remember(function () use ($cityId, $filters): array {
            $city = $cityId !== null
                ? City::findOrFail($cityId) // страховка от удаления города после exists-валидации
                : $this->ensureCityExists->ensure($this->defaultCityName);

            return $this->getTireFilterValues->execute($city->id, $filters);
        }, $cityId, $filters);

        return new TireFilterValuesResource(TireFilterValues::fromArray($data));
    }
}
