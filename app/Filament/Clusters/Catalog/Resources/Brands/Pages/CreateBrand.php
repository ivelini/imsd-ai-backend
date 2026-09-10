<?php

namespace App\Filament\Clusters\Catalog\Resources\Brands\Pages;

use App\Filament\Clusters\Catalog\Resources\Brands\BrandResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;
}
