<?php

namespace App\Services\Catalog;

/** Подготовка данных диска к сохранению: name из модели (если пуст) и SEO-slug (ADR 0006). */
final readonly class WheelDataComposer
{
    public function __construct(
        private DisplayNameResolver $displayName,
        private ProductSlugService $slugService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function compose(array $data, ?int $ignoreId = null): array
    {
        $data = $this->displayName->resolve($data);

        $data['slug'] = $this->slugService->wheel(
            brandId: (int) $data['brand_id'],
            name: $data['name'],
            width: $data['width'] ?? null,
            diameter: isset($data['diameter']) ? (int) $data['diameter'] : null,
            et: $data['et'] ?? null,
            pcd: $data['pcd'] ?? null,
            hubDiameter: $data['hub_diameter'] ?? null,
            ignoreId: $ignoreId,
        );

        return $data;
    }
}
