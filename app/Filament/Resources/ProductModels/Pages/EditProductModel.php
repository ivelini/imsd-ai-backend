<?php

namespace App\Filament\Resources\ProductModels\Pages;

use App\Filament\Resources\ProductModels\ProductModelResource;
use Filament\Resources\Pages\EditRecord;

class EditProductModel extends EditRecord
{
    protected static string $resource = ProductModelResource::class;
}
