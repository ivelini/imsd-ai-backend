<?php

namespace App\Filament\Clusters\Settings\Resources\Admins\Pages;

use App\Filament\Clusters\Settings\Resources\Admins\AdminResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;
}
