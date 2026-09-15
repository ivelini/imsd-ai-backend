<?php

namespace App\Filament\Clusters\Booking\Resources\Slots\Pages;

use App\Filament\Clusters\Booking\Resources\Slots\SlotResource;
use App\Filament\Concerns\SavesAndCloses;
use App\Models\Booking\Slot;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSlot extends EditRecord
{
    use SavesAndCloses;

    protected static string $resource = SlotResource::class;

    /** Заголовок несёт дату и час: сами поля на правке скрыты. */
    public function getTitle(): string
    {
        /** @var Slot $slot */
        $slot = $this->getRecord();

        return sprintf('Редактирование слота: %s, %02d:00', $slot->date->format('d.m.Y'), $slot->hour);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
