<?php

namespace App\Http\Controllers\Admin\Geo;

use App\Actions\Geo\GetCityList;
use App\Http\Requests\Admin\Geo\CityIndexRequest;
use App\Http\Resources\Admin\Geo\CityResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Список городов.
 *
 */
final readonly class CityController
{
    public function __construct(
        private GetCityList $getCityList,
    ) {}

    /** Список городов для дропдауна.
     *
     */
    public function index(CityIndexRequest $request): AnonymousResourceCollection
    {
        return CityResource::collection(
            $this->getCityList->execute($request->validated())
        );
    }
}
