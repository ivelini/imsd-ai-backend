<?php

namespace App\DTOs\Catalog;

use App\Http\Requests\Admin\Catalog\CatalogProductIndexRequest;

/** Входные данные для Action GetCatalogProducts. */
final readonly class GetCatalogProductsInput
{
    public function __construct(
        public ?string $type,
        public ?int $brandId,
        public ?bool $isPublished,
        public ?string $search,
        public string $sortBy,
        public string $sortDir,
        public int $perPage,
    ) {}

    public static function fromRequest(CatalogProductIndexRequest $request): self
    {
        $data = $request->validated();

        return new self(
            type: $data['type'] ?? null,
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            isPublished: isset($data['is_published']) ? (bool) $data['is_published'] : null,
            search: $data['search'] ?? null,
            sortBy: $data['sort_by'] ?? 'created_at',
            sortDir: $data['sort_dir'] ?? 'desc',
            perPage: min(max((int) ($data['per_page'] ?? 50), 10), 100),
        );
    }
}
