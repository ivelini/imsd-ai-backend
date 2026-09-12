<?php

namespace Database\Seeders;

use App\Models\System\Setting;
use Illuminate\Database\Seeder;

class BookingSettingsSeeder extends Seeder
{
    /** Ключи таблицы settings; значения по умолчанию из ФТ §7. */
    private const SETTINGS = [
        'reservation_timeout_min' => '15', // TTL кода подтверждения
        'cancel_free_before_h' => '2', // свободная отмена клиентом до начала
        'min_lead_time_h' => '1', // минимальное время записи до начала
        'booking_horizon_days' => '60', // горизонт записи
        'shop_address' => 'г. Челябинск, Копейское шоссе, 12А',
        'shop_phone' => '+7 (351) 70-00-319',
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
