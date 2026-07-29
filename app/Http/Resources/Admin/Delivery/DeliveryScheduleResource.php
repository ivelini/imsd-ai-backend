<?php

namespace App\Http\Resources\Admin\Delivery;

use App\Models\Catalog\Warehouse;
use App\Models\Delivery\DeliverySchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс графика отгрузки склада. */
final class DeliveryScheduleResource extends JsonResource
{
    /** @var DeliverySchedule */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $schedule = $this->resource;

        return [
            'id' => $schedule->id,
            'warehouse_id' => $schedule->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', function () use ($schedule) {
                /** @var Warehouse $wh */
                $wh = $schedule->warehouse;

                return ['id' => $wh->id, 'name' => $wh->name];
            }),
            'day_of_week' => $schedule->day_of_week,
            'cutoff_time' => $schedule->cutoff_time,
            'days_before' => $schedule->days_before,
            'days_after' => $schedule->days_after,
            'created_at' => $schedule->created_at->toIso8601String(),
        ];
    }
}
