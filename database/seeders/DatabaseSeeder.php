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
        if (app()->environment('local')) {
            $this->call(AdminSeeder::class);
            $this->call(WarehouseMarkupRuleSeeder::class);
            $this->call(DeliveryScheduleSeeder::class);
            $this->call(CityDeliveryTimeSeeder::class);
        }
    }
}
