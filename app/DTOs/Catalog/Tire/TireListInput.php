<?php

namespace App\DTOs\Catalog\Tire;

/** Входные данные публичного листинга шин. */
final readonly class TireListInput
{
    /**
     * @param  array<string, mixed>  $filters  Валидированные фильтры каталога (без city_id, page, per_page, sort_*)
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
