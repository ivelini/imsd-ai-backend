<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/** Уведомление для админ-панели. */
final class NotificationResource extends JsonResource
{
    /** @var DatabaseNotification */
    public $resource;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'type' => class_basename($this->resource->type),
            'data' => $this->resource->data,
            'read_at' => $this->resource->read_at?->toIso8601String(),
            'created_at' => $this->resource->created_at->toIso8601String(),
        ];
    }
}
