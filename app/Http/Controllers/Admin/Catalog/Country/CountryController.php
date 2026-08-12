<?php

namespace App\Http\Controllers\Admin\Catalog\Country;

use App\Http\Resources\Admin\Catalog\Country\CountryResource;
use App\Models\Catalog\Country\Country;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Справочник стран. */
#[Group('Справочники', weight: 5)]
final readonly class CountryController
{
    /** Список стран для дропдауна. */
    public function index(): AnonymousResourceCollection
    {
        return CountryResource::collection(
            Country::orderBy('name')->get()
        );
    }
}
