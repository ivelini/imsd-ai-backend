<?php

namespace App\Http\Controllers\Admin\Geo;

use App\Actions\Geo\GetCityList;
use App\Http\Requests\Admin\Geo\CityIndexRequest;
use App\Http\Resources\Admin\Geo\CityResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Список городов.
 *
 * @group Города
 */
final readonly class CityController
{
    public function __construct(
        private GetCityList $getCityList,
    ) {}

    /** Список городов для дропдауна.
     *
     * @queryParam search string Поиск по названию города.
     * @queryParam region_code string Фильтр по коду региона.
     *
     * @authenticated
     */
    public function index(CityIndexRequest $request): AnonymousResourceCollection
    {
        return CityResource::collection(
            $this->getCityList->execute($request->validated())
        );
    }
}
