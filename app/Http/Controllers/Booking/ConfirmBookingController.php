<?php

namespace App\Http\Controllers\Booking;

use App\Actions\Booking\ConfirmBooking;
use App\DTOs\Booking\ConfirmBookingInput;
use App\Enums\Booking\CarType;
use App\Enums\Booking\CodeStatus;
use App\Http\Requests\Booking\ConfirmBookingRequest;
use App\Http\Resources\Booking\BookingResource;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingService;
use App\Preconditions\Booking\EnsureCodeVerifiable;
use App\Preconditions\Booking\EnsurePricingCombinationExists;
use App\Preconditions\Booking\EnsureSlotSelectable;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Group;
use DomainException;
use Illuminate\Http\JsonResponse;

/**
 * Подтверждение записи кодом из SMS: цепочка проверок → атомарное создание.
 * Повторный submit уже использованного кода возвращает созданную ранее запись (200).
 */
#[Group('Запись на шиномонтаж', weight: 20)]
final readonly class ConfirmBookingController
{
    public function __construct(
        private EnsureCodeVerifiable $ensureCodeVerifiable,
        private EnsureSlotSelectable $ensureSlotSelectable,
        private EnsurePricingCombinationExists $ensurePricingCombinationExists,
        private ConfirmBooking $confirmBooking,
    ) {}

    public function __invoke(ConfirmBookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $date = CarbonImmutable::parse($data['date']);

        $verification = $this->ensureCodeVerifiable->ensure($data['phone'], $data['code']);

        // Повторный submit использованного кода (НФ-1): запись уже создана
        if ($verification->status === CodeStatus::Used) {
            $booking = Booking::forCode($verification->code);

            if ($booking === null) {
                throw new DomainException('Код не найден', 422);
            }

            return response()->json(['data' => $this->resource($booking)]);
        }

        $this->ensureSlotSelectable->ensure($date, (int) $data['hour']);

        $services = BookingService::query()->activeByIds($data['service_ids'])->get();
        $this->ensurePricingCombinationExists->ensure(
            $services,
            (int) $data['radius'],
            CarType::from($data['car_type']),
            $data['quantities'],
        );

        $booking = $this->confirmBooking->execute(new ConfirmBookingInput(
            code: $verification->code,
            name: $data['name'],
            phone: $data['phone'],
            plate: $data['plate'],
            date: $date,
            hour: (int) $data['hour'],
            radius: (int) $data['radius'],
            carType: CarType::from($data['car_type']),
            quantities: $data['quantities'],
            // Бронь с сайта занимает час (ФТ-8/ФТ-16): слот закрывается всегда
            closeSlot: true,
        ));

        return response()->json(['data' => $this->resource($booking)], 201);
    }

    /** @return array<string, mixed> */
    private function resource(Booking $booking): array
    {
        return (new BookingResource($booking->load(['slot', 'user', 'items.service'])))->resolve();
    }
}
