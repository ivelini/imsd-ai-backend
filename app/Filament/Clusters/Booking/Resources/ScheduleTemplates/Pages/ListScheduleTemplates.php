<?php

namespace App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Pages;

use App\Actions\Booking\GenerateSlotGrid;
use App\Filament\Clusters\Booking\Resources\ScheduleTemplates\ScheduleTemplateResource;
use App\Filament\Support\PanelAction;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScheduleTemplates extends ListRecords
{
    protected static string $resource = ScheduleTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Сетка держится планировщиком; кнопка — ручной запуск того же действия
            Action::make('generateGrid')
                ->label('Сгенерировать сетку')
                ->icon('heroicon-o-bolt')
                ->action(function (GenerateSlotGrid $generateSlotGrid): void {
                    PanelAction::run('Сетка слотов обновлена', function () use ($generateSlotGrid): void {
                        $generateSlotGrid->execute();
                    });
                }),
            CreateAction::make(),
        ];
    }
}
