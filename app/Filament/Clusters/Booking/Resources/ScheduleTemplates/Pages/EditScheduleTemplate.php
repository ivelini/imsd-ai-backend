<?php

namespace App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Pages;

use App\Filament\Clusters\Booking\Resources\ScheduleTemplates\ScheduleTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScheduleTemplate extends EditRecord
{
    protected static string $resource = ScheduleTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
