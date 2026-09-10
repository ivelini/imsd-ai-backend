<?php

namespace App\Filament\Clusters\Catalog\Resources\ProductModels\Pages;

use App\Filament\Clusters\Catalog\Resources\ProductModels\ProductModelResource;
use Filament\Resources\Pages\EditRecord;

class EditProductModel extends EditRecord
{
    protected static string $resource = ProductModelResource::class;
}
