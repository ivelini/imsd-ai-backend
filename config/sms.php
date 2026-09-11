<?php

/** Отправка SMS (код подтверждения записи). */
return [
    'stub' => [
        'is_active' => (bool) env('SMS_STUB', true),
        'code' => (int) env('SMS_STUB_CODE', 1234),
    ],
    'provider' => env('SMS_PROVIDER'),
    'resend_cooldown_seconds' => (int) env('SMS_RESEND_COOLDOWN_SECONDS', 60),
];
