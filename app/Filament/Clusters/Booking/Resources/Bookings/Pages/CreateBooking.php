<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Pages;

use App\Actions\Booking\CreateAdminBooking;
use App\DTOs\Booking\CreateAdminBookingInput;
use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\Bookings\BookingResource;
use App\Models\Auth\Admin;
use App\Models\Booking\Booking;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    /**
     * Создание — через Action CreateAdminBooking: снимок цены пересчитывается сервером,
     * клиент ищется/создаётся по телефону, слот закрывается по чекбоксу.
     */
    public function handleRecordCreation(array $data): Booking
    {
        $quantities = collect($data['composition'])
            ->mapWithKeys(fn (array $row): array => [$row['service_id'] => (int) $row['quantity']])
            ->all();

        try {
            return app(CreateAdminBooking::class)->execute(new CreateAdminBookingInput(
                operator: $this->operator(),
                phone: $data['phone'],
                name: $data['name'],
                plate: $data['plate'],
                slotId: (int) $data['slot_id'],
                radius: (int) $data['radius'],
                carType: CarType::from($data['car_type']),
                quantities: $quantities,
                closeSlot: (bool) $data['close_slot'],
            ));
        } catch (DomainException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();
            $this->halt();
        }

        // Недостижимо (halt() бросает Halt) — throw только для анализатора
        throw new DomainException('Запись не создана', 422);
    }

    private function operator(): Admin
    {
        /** @var Admin $operator */
        $operator = auth('admin')->user();

        return $operator;
    }
}
