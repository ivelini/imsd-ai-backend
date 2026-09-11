<?php

namespace App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Pages;

use App\Filament\Clusters\Booking\Resources\ScheduleTemplates\ScheduleTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScheduleTemplates extends ListRecords
{
    protected static string $resource = ScheduleTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
