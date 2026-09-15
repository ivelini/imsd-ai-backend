<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Pages;

use App\Actions\Booking\CreateAdminBooking;
use App\DTOs\Booking\BookingItemInput;
use App\DTOs\Booking\CreateAdminBookingInput;
use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\Bookings\BookingResource;
use App\Models\Auth\Admin;
use App\Models\Booking\Booking;
use App\ValueObjects\Money;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    /** Слот из строки запроса — переход со страницы слота (?slot_id=): форма открывается с выбранным слотом. */
    #[Url]
    public ?int $slot_id = null;

    protected function fillForm(): void
    {
        parent::fillForm();

        if ($this->slot_id === null) {
            return;
        }

        // Родитель уже применил дефолты полей — слот вписываем в сырое состояние: fill() дефолты теряет,
        // а getState() в этой версии Filament валидирует форму и на пустой форме бросает исключение
        $this->form->rawState([...$this->form->getRawState(), 'slot_id' => $this->slot_id]);
    }

    /**
     * Создание — через Action CreateAdminBooking: состав и цены строк берутся из формы
     * (как на правке), клиент ищется/создаётся по телефону, запись на начало часа занимает час.
     */
    public function handleRecordCreation(array $data): Booking
    {
        try {
            return app(CreateAdminBooking::class)->execute(new CreateAdminBookingInput(
                operator: $this->operator(),
                phone: (string) $data['phone'],
                name: (string) $data['name'],
                surname: (string) $data['surname'],
                patronymic: filled($data['patronymic'] ?? null) ? (string) $data['patronymic'] : null,
                plate: $data['plate'],
                slotId: (int) $data['slot_id'],
                radius: (int) $data['radius'],
                carType: CarType::from($data['car_type']),
                items: self::items($data['items'] ?? []),
                startTime: (string) $data['start_time'],
            ));
        } catch (DomainException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();
            $this->halt();
        }

        // Недостижимо (halt() бросает Halt) — throw только для анализатора
        throw new DomainException('Запись не создана', 422);
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

    private function operator(): Admin
    {
        /** @var Admin $operator */
        $operator = auth('admin')->user();

        return $operator;
    }
}
