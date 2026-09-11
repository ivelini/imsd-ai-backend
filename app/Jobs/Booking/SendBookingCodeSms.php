<?php

namespace App\Jobs\Booking;

use App\Services\Booking\SmsSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Доставка SMS с кодом подтверждения (ФТ-23/ФТ-24 tireslot): через очередь с ретраями —
 * сбой провайдера не блокирует запрос кода и подтверждение брони.
 */
final class SendBookingCodeSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(
        public readonly string $phone,
        public readonly int $code,
    ) {}

    public function handle(SmsSender $sender): void
    {
        $sender->send($this->phone, "Код подтверждения записи: {$this->code}");
    }
}
