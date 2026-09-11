<?php

namespace App\Filament\Clusters\Booking\Resources\Slots\Pages;

use App\Actions\Booking\GenerateSlotGrid;
use App\Filament\Clusters\Booking\Resources\Slots\SlotResource;
use App\Filament\Support\PanelAction;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSlots extends ListRecords
{
    protected static string $resource = SlotResource::class;

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
