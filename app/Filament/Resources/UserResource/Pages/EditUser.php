<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

/** Удаления в шапке нет: на клиента ссылаются записи и договоры хранения (FK restrict). */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}
