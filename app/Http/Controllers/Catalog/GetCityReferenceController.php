<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Geo\GetCityReference;
use App\Http\Resources\Geo\CityReferenceResource;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

/** Справочник городов для дропдаунов. */
final readonly class GetCityReferenceController
{
    public function __construct(
        private GetCityReference $getCityReference,
        private string $defaultCityName,
    ) {}

    /**
     * Все города: {label: name, value: id, slug, region: {id, name}}, отсортированы по имени.
     * meta.default — город по умолчанию из config/shop.php ({label, value}), null — города нет в БД.
     */
    #[Group('Справочник', weight: 10)]
    #[Response(type: 'array{data: list<array{label: string, value: int, slug: string|null, region: array{id: int, name: string}}>, meta: array{default: array{label: string, value: int}|null}}')]
    public function __invoke(): JsonResponse
    {
        $defaultCity = $this->getCityReference->defaultCity($this->defaultCityName);

        return response()->json([
            'data' => CityReferenceResource::collection($this->getCityReference->execute()),
            'meta' => [
                'default' => $defaultCity !== null
                    ? ['label' => $defaultCity->name, 'value' => $defaultCity->id]
                    : null,
            ],
        ]);
    }
}
