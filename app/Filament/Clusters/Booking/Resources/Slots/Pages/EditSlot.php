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

    /**
     * Возврат — в список слотов, даже если в карточку попали из записи: возврат в запись замкнул бы
     * круг «запись → слот → запись». Адрес экрана входа берётся, только когда он сам список, —
     * так переживают фильтры и страница листинга.
     */
    protected function getReturnUrl(): string
    {
        $list = static::getResource()::getUrl('index');

        return $this->previousUrl !== null && str_starts_with($this->previousUrl, $list)
            ? $this->previousUrl
            : $list;
    }
}
