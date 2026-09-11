<?php

namespace App\Http\Controllers\Booking;

use App\Http\Requests\Booking\IssueCodeRequest;
use App\Jobs\Booking\SendBookingCodeSms;
use App\Preconditions\Booking\EnsureCodeCooldownElapsed;
use App\Services\Booking\BookingCodeService;
use App\Support\Phone;
use Dedoc\Scramble\Attributes\Group;
use DomainException;
use Illuminate\Http\JsonResponse;

/** Выдача SMS-кода подтверждения: кулдаун 429, доставка — через очередь. */
#[Group('Запись на шиномонтаж', weight: 20)]
final readonly class IssueBookingCodeController
{
    public function __construct(
        private BookingCodeService $codes,
        private EnsureCodeCooldownElapsed $ensureCodeCooldownElapsed,
    ) {}

    public function __invoke(IssueCodeRequest $request): JsonResponse
    {
        $canonical = Phone::normalize($request->validated('phone'));

        if ($canonical === null) {
            throw new DomainException('Невалидный телефон', 422);
        }

        $this->ensureCodeCooldownElapsed->ensure($canonical);

        $code = $this->codes->issue($canonical);
        SendBookingCodeSms::dispatch($canonical, $code);

        return response()->json(['data' => ['retry_after' => config('sms.resend_cooldown_seconds')]]);
    }
}
