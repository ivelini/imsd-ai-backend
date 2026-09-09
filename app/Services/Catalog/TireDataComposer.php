<?php

namespace App\Services\Catalog;

/** Подготовка данных шины к сохранению: name из модели (если пуст) и SEO-slug (ADR 0006). */
final readonly class TireDataComposer
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

        $data['slug'] = $this->slugService->tire(
            brandId: (int) $data['brand_id'],
            modelId: (int) $data['model_id'],
            width: isset($data['width']) ? (int) $data['width'] : null,
            profile: isset($data['profile']) ? (int) $data['profile'] : null,
            diameter: $data['diameter'] ?? null,
            loadIndex: $data['load_index'] ?? null,
            speedIndex: $data['speed_index'] ?? null,
            isStudded: (bool) ($data['is_studded'] ?? false),
            isRunflat: (bool) ($data['is_runflat'] ?? false),
            ignoreId: $ignoreId,
        );

        return $data;
    }
}
