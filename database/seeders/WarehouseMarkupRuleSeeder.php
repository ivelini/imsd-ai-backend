<?php

namespace Database\Seeders;

use App\Models\Catalog\Warehouse;
use App\Models\Catalog\WarehouseMarkupRule;
use Illuminate\Database\Seeder;

/** Коэффициенты наценки для каждого склада по диапазонам цен. */
class WarehouseMarkupRuleSeeder extends Seeder
{
    /** @var array<int, array{price_from: int, price_to: int, coefficient: float}> */
    private const RULES = [
        ['price_from' => 0,       'price_to' => 5000,     'coefficient' => 1.20],
        ['price_from' => 5001,    'price_to' => 10000,    'coefficient' => 1.15],
        ['price_from' => 10001,   'price_to' => 15000,    'coefficient' => 1.10],
        ['price_from' => 15001,   'price_to' => 100000,   'coefficient' => 1.05],
    ];

    public function run(): void
    {
        $warehouses = Warehouse::all();

        if ($warehouses->isEmpty()) {
            $this->command?->warn('Нет складов — создайте хотя бы один Warehouse перед запуском седера.');

            return;
        }

        foreach ($warehouses as $warehouse) {
            foreach (self::RULES as $rule) {
                WarehouseMarkupRule::firstOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'price_from' => $rule['price_from'],
                        'price_to' => $rule['price_to'],
                    ],
                    ['coefficient' => $rule['coefficient']],
                );
            }
        }

        $this->command?->info('Коэффициенты наценки созданы для '.$warehouses->count().' складов.');
    }
}
