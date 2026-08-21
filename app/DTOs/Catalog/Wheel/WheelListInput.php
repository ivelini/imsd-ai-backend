<?php

namespace App\DTOs\Catalog\Wheel;

/** Входные данные публичного листинга дисков. */
final readonly class WheelListInput
{
    /**
     * @param  array<string, mixed>  $filters  Валидированные фильтры каталога (без city_id, city, page, per_page, sort_*)
     */
    public function __construct(
        public int $cityId,
        public array $filters,
        public int $page,
        public int $perPage,
        public ?string $sortBy,
        public string $sortDir,
    ) {}
}
