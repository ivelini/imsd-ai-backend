<?php

namespace App\Filament\Clusters\Booking\Resources\StorageContracts\Pages;

use App\Filament\Clusters\Booking\Resources\StorageContracts\StorageContractResource;
use App\Filament\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

/**
 * Договор заводят и с нуля, и из записи на шиномонтаж: тогда адрес несёт клиента, срок «с»
 * и стоимость (см. BookingStorageContractFlowTest, StorageContractPrefill).
 */
class CreateStorageContract extends CreateRecord
{
    protected static string $resource = StorageContractResource::class;

    /** Клиент записи — по нему заполняется поле «Клиент». */
    #[Url]
    public ?int $user_id = null;

    /** Дата слота записи — срок хранения начинается в день визита. */
    #[Url]
    public ?string $starts_on = null;

    /** Итог строки хранения из записи, в рублях — как в поле формы (ADR 0013). */
    #[Url]
    public ?string $price = null;

    protected function fillForm(): void
    {
        parent::fillForm();

        $prefill = array_filter([
            'user_id' => $this->user_id,
            'starts_on' => $this->starts_on,
            'price' => $this->price,
        ], fn (string|int|null $value): bool => filled($value));

        if ($prefill === []) {
            return;
        }

        // Родитель уже применил дефолты полей — состояние дописываем сырым: fill() дефолты теряет
        $this->form->rawState([...$this->form->getRawState(), ...$prefill]);
    }

    /** Оператора проставляет панель — из формы поле не приходит. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['operator_id'] = auth('admin')->id();

        return $data;
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            $this->createClientAction(),
        ];
    }

    /** Нового клиента заводят, не бросая заполненный договор: форма клиента открывается в соседней вкладке. */
    protected function createClientAction(): Action
    {
        return Action::make('createClient')
            ->label('Создать пользователя')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('gray')
            ->url(UserResource::getUrl('create'))
            ->openUrlInNewTab();
    }
}
