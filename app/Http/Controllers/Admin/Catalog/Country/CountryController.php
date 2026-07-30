<?php

namespace App\Http\Controllers\Admin\Catalog\Country;

use App\Http\Resources\Admin\Catalog\Country\CountryResource;
use App\Models\Catalog\Country\Country;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Справочник стран. */
final readonly class CountryController
{
    /**
     * Список стран для дропдауна.
     *
     * @group Страны
     */
    public function index(): AnonymousResourceCollection
    {
        return CountryResource::collection(
            Country::orderBy('name')->get()
        );
    }
}
