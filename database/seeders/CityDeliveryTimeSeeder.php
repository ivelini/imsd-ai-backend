<?php

namespace Database\Seeders;

use App\Models\Delivery\City;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\Region;
use Illuminate\Database\Seeder;

/** Срок доставки до города по умолчанию: Челябинск — 0 дней. */
class CityDeliveryTimeSeeder extends Seeder
{
    private const CHELYABINSK_DELIVERY_DAYS = 0;

    public function run(): void
    {
        $region = Region::firstOrCreate(
            ['code' => '74'],
            ['name' => 'Челябинская область'],
        );

        $city = City::firstOrCreate(
            ['region_id' => $region->id, 'name' => 'Челябинск'],
            ['name' => 'Челябинск', 'sort' => 1],
        );

        CityDeliveryTime::updateOrCreate(
            ['city_id' => $city->id],
            ['delivery_days' => self::CHELYABINSK_DELIVERY_DAYS],
        );
    }
}
