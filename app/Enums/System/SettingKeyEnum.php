<?php

namespace App\Enums\System;

/**
 * Ключи таблицы settings (параметры конфигурации, управляемые из админки без деплоя):
 * значения по умолчанию — здесь, актуальное значение — Setting::get().
 */
enum SettingKeyEnum: string
{
    /** ФТ-9 tireslot: срок действия кода подтверждения (неподтверждённый), минуты */
    case ReservationTimeoutMin = 'reservation_timeout_min';

    /** ФТ-10 tireslot: минимальное время записи до начала, часы */
    case MinLeadTimeH = 'min_lead_time_h';

    /** ФТ-10 tireslot: горизонт записи (сколько дней вперёд открыта сетка), дни */
    case HorizonDays = 'booking_horizon_days';

    public function default(): int
    {
        return match ($this) {
            self::ReservationTimeoutMin => 15,
            self::MinLeadTimeH => 1,
            self::HorizonDays => 30,
        };
    }
}
