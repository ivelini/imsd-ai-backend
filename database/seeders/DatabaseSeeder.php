<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/** Начальные данные для разработки и продакшена. */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Справочники домена записи на шиномонтаж — и в тестах, и в проде
        $this->call(BookingCatalogSeeder::class);
        $this->call(BookingScheduleSeeder::class);
        $this->call(BookingSettingsSeeder::class);

        if (app()->environment('local')) {
            $this->call(AdminSeeder::class);
            $this->call(WarehouseMarkupRuleSeeder::class);
            $this->call(DeliveryScheduleSeeder::class);
            $this->call(CityDeliveryTimeSeeder::class);
        }
    }
}
