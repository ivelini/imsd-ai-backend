<?php

namespace App\Filament\Clusters\Booking\Resources\StorageContracts\Pages;

use App\Filament\Clusters\Booking\Resources\StorageContracts\StorageContractResource;
use App\Filament\Concerns\SavesAndCloses;
use App\Models\Storage\StorageContract;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditStorageContract extends EditRecord
{
    use SavesAndCloses;

    protected static string $resource = StorageContractResource::class;

    /** Заголовок несёт номер и клиента: оператор видит, чей договор открыт */
    public function getTitle(): string
    {
        /** @var StorageContract $contract */
        $contract = $this->getRecord();

        return sprintf('Договор № %s — %s', $contract->number, $contract->user->full_name);
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            $this->createWordDocumentAction(),
        ];
    }

    protected function createWordDocumentAction(): Action
    {
        return Action::make('printDocument')
            ->label('Распечатать договор')
            ->icon(Heroicon::Printer)
            ->color('gray')
            ->action('printDocument');
    }

    public function printDocument(): void
    {
        $storageContract = $this->getRecord()->refresh()->load('items');
        dd($storageContract);
    }
}
