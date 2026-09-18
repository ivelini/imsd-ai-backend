<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Pages;

use App\Actions\Booking\UpdateAdminBooking;
use App\DTOs\Booking\BookingItemInput;
use App\DTOs\Booking\UpdateAdminBookingInput;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\Bookings\BookingResource;
use App\Filament\Clusters\Booking\Resources\StorageContracts\StorageContractResource;
use App\Filament\Concerns\SavesAndCloses;
use App\Filament\Support\StorageContractPrefill;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\ValueObjects\Money;
use Carbon\Carbon;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class EditBooking extends EditRecord
{
    use SavesAndCloses;

    protected static string $resource = BookingResource::class;

    /** @return array<int, Action> */
    protected function getExtraFormActions(): array
    {
        return [
            $this->createStorageContractAction(),
        ];
    }

    /** Кнопка появляется на услуге категории «Хранение»: с ней запись продолжается договором. */
    protected function createStorageContractAction(): Action
    {
        return Action::make('createStorageContract')
            ->label('Создать договор хранения')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('gray')
            ->visible(fn (): bool => StorageContractPrefill::hasStorageService($this->data['items'] ?? []))
            ->action('createStorageContract');
    }

    /** Запись уже сохранена — сразу ведём на договор с её данными (правки формы в договор не попадут). */
    public function createStorageContract(): void
    {
        /** @var Booking $booking */
        $booking = $this->getRecord();

        $this->redirect(StorageContractResource::getUrl('create', StorageContractPrefill::paramsFor($booking)));
    }

    /**
     * Заголовок несёт дату слота, время и клиента записи: оператор видит, с кем и на какой день работает
     * (поля слота в форме нет — запись не переносится, ADR-карта: перенос это ФТ-20).
     */
    public function getTitle(): string
    {
        /** @var Booking $booking */
        $booking = $this->getRecord();
        $startTime = Carbon::parse($booking->start_time)->format('H:i');

        return 'Запись '.collect([
            $booking->slot?->date->format('d.m.Y'),
            $startTime,
            $booking->user->full_name,
            $booking->user->phone,
        ])
            ->filter()
            ->implode(', ');
    }

    /**
     * Состав правится в форме: строки отдаются снимком цены записи, а не пересчётом по текущему прайсу.
     * ФИО живёт в карточке клиента — подставляем его в поля, чтобы оператор видел текущее и правил его.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Booking $booking */
        $booking = $this->getRecord();

        $data['surname'] = $booking->user->surname;
        $data['name'] = $booking->user->name;
        $data['patronymic'] = $booking->user->patronymic;

        $data['items'] = $booking->items
            ->sortBy('id')
            ->map(fn (BookingItem $item): array => [
                'service_id' => $item->service_id,
                'price' => (string) $item->price->toRubles(),
                'quantity' => $item->quantity,
            ])
            ->values()
            ->all();

        return $data;
    }

    /**
     * Сохранение — через Action: снимок полей, состав и пересчёт итоговой стоимости
     * записи по строкам (цена × количество).
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Booking $record */
        try {
            app(UpdateAdminBooking::class)->execute(new UpdateAdminBookingInput(
                booking: $record,
                surname: (string) $data['surname'],
                name: (string) $data['name'],
                patronymic: filled($data['patronymic'] ?? null) ? (string) $data['patronymic'] : null,
                plate: $data['plate'] ?? null,
                radius: (int) $data['radius'],
                carType: CarType::from($data['car_type']),
                status: BookingStatus::from($data['status']),
                cancelReason: $data['cancel_reason'] ?? null,
                startTime: (string) $data['start_time'],
                items: self::items($data['items'] ?? []),
            ));

            return $record;
        } catch (DomainException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();
            $this->halt();
        }

        // Недостижимо (halt() бросает Halt) — throw только для анализатора
        throw new DomainException('Запись не сохранена', 422);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $rows
     * @return list<BookingItemInput>
     */
    private static function items(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): BookingItemInput {
                /** @var Money $price цена за единицу в рублях — Money отдаёт dehydration поля формы */
                $price = $row['price'];

                return new BookingItemInput(
                    serviceId: (int) $row['service_id'],
                    quantity: (int) $row['quantity'],
                    price: $price,
                );
            })
            ->values()
            ->all();
    }
}
