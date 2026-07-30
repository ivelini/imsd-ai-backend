<?php

namespace Database\Seeders;

use App\Enums\Common\WeekDay;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\DeliverySchedule;
use Illuminate\Database\Seeder;

/** Графики отгрузки для каждого склада. */
class DeliveryScheduleSeeder extends Seeder
{
    /** @var array<int, array{day: WeekDay, cutoff: string, before: int, after: int}> */
    private const SCHEDULES = [
        ['day' => WeekDay::Monday,    'cutoff' => '14:00', 'before' => 1, 'after' => 2],
        ['day' => WeekDay::Tuesday,   'cutoff' => '14:00', 'before' => 1, 'after' => 2],
        ['day' => WeekDay::Wednesday, 'cutoff' => '14:00', 'before' => 1, 'after' => 2],
        ['day' => WeekDay::Thursday,  'cutoff' => '14:00', 'before' => 1, 'after' => 2],
        ['day' => WeekDay::Friday,    'cutoff' => '14:00', 'before' => 1, 'after' => 2],
        ['day' => WeekDay::Saturday,  'cutoff' => '12:00', 'before' => 2, 'after' => 3],
    ];

    public function run(): void
    {
        $warehouses = Warehouse::all();

        if ($warehouses->isEmpty()) {
            $this->command?->warn('Нет складов — создайте хотя бы один Warehouse перед запуском седера.');

            return;
        }

        foreach ($warehouses as $warehouse) {
            foreach (self::SCHEDULES as $schedule) {
                DeliverySchedule::firstOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'day_of_week' => $schedule['day']->value,
                    ],
                    [
                        'cutoff_time' => $schedule['cutoff'],
                        'days_before' => $schedule['before'],
                        'days_after' => $schedule['after'],
                    ],
                );
            }
        }

        $this->command?->info(
            'Графики отгрузки созданы для '.$warehouses->count().' складов (пн–сб, вс — выходной).'
        );
    }
}
