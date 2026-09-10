<?php

namespace App\Filament\Clusters\Catalog\Resources\ProductModels\Pages;

use App\Filament\Clusters\Catalog\Resources\ProductModels\ProductModelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductModel extends CreateRecord
{
    protected static string $resource = ProductModelResource::class;
}
