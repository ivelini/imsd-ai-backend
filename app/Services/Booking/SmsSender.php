<?php

namespace App\Services\Booking;

/**
 * Отправка SMS. Провайдер в v1 не выбран: dev использует LogSmsSender;
 * подключение провайдера — новая реализация контракта без изменения кода (см. config('sms.provider')).
 */
interface SmsSender
{
    public function send(string $phone, string $message): void;
}
