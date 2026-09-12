<?php

namespace App\Filament\Clusters\Booking\Resources\Slots\Pages;

use App\Actions\Booking\GenerateSlotGrid;
use App\Enums\Booking\SlotPeriod;
use App\Filament\Clusters\Booking\Resources\Slots\SlotResource;
use App\Filament\Support\PanelAction;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSlots extends ListRecords
{
    protected static string $resource = SlotResource::class;

    /**
     * Стартовый вид — слоты на сегодня.
     *
     * Дефолт живёт здесь, а не в `->default()` фильтра периода: при сбросе фильтров Filament
     * перезаполняет форму дефолтами, и фильтр возвращал бы «Сегодня» вместо всей сетки.
     */
    public function mount(): void
    {
        parent::mount();

        $this->tableFilters['period']['value'] ??= SlotPeriod::Today->value;
    }

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
