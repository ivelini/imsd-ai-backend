<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Pages;

use App\Actions\Booking\UpdateAdminBooking;
use App\DTOs\Booking\BookingItemInput;
use App\DTOs\Booking\UpdateAdminBookingInput;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\Bookings\BookingResource;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\ValueObjects\Money;
use Carbon\Carbon;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    /** Заголовок несёт клиента записи: оператор видит, с кем работает. */
    public function getTitle(): string
    {
        /** @var Booking $booking */
        $booking = $this->getRecord();
        $startTime = Carbon::parse($booking->start_time)->format('H:i');

        return 'Запись '.collect([
            $startTime,
            $booking->user->name,
            $booking->user->phone,
        ])
            ->filter()
            ->implode(', ');
    }

    /** Состав правится в форме: строки отдаются снимком цены записи, а не пересчётом по текущему прайсу. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Booking $booking */
        $booking = $this->getRecord();

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
