<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Geo\GetCityReference;
use App\Http\Resources\Geo\CityReferenceResource;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Справочник городов для дропдаунов. */
final readonly class GetCityReferenceController
{
    public function __construct(
        private GetCityReference $getCityReference,
    ) {}

    /**
     * Все города: {label: name, value: id, slug}, отсортированы по имени.
     */
    #[Group('Справочник', weight: 10)]
    #[Response(type: 'array{data: list<array{label: string, value: int, slug: string|null}>}')]
    public function __invoke(): AnonymousResourceCollection
    {
        return CityReferenceResource::collection($this->getCityReference->execute());
    }
}
