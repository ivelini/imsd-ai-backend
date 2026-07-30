<?php

namespace App\Actions\Catalog\Wheel;

use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Wheel\WheelProduct;

/** Доступные значения фильтров дисков с учётом контекста. */
final readonly class GetWheelDimensions
{
    public function execute(array $params): array
    {
        $query = WheelProduct::query();

        if (! empty($params['search'])) {
            $query->search($params['search']);
        }

        if (! empty($params['brand_id'])) {
            $query->byBrand((int) $params['brand_id']);
        }

        if (! empty($params['model_id'])) {
            $query->byModel((int) $params['model_id']);
        }

        if (! empty($params['type'])) {
            $query->byType($params['type']);
        }

        if (! empty($params['color'])) {
            $query->byColor($params['color']);
        }

        if (isset($params['is_published'])) {
            $query->published(filter_var($params['is_published'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($params['width'])) {
            $query->byWidths(array_map('floatval', $params['width']));
        }

        if (! empty($params['diameter'])) {
            $query->byDiameters(array_map('intval', $params['diameter']));
        }

        if (! empty($params['pcd'])) {
            $query->byPcds($params['pcd']);
        }

        if (! empty($params['et'])) {
            $query->byEts(array_map('floatval', $params['et']));
        }

        if (! empty($params['hub_diameter'])) {
            $query->byHubDiameters(array_map('floatval', $params['hub_diameter']));
        }

        if (isset($params['is_bestseller'])) {
            $query->bestseller(filter_var($params['is_bestseller'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($params['is_new'])) {
            $query->isNew(filter_var($params['is_new'], FILTER_VALIDATE_BOOLEAN));
        }

        // Бренды и модели — только присутствующие в отфильтрованных товарах.
        $brandIds = (clone $query)->distinct()->pluck('brand_id')->filter()->all();
        $brands = Brand::whereIn('id', $brandIds)->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Brand $b) => ['value' => $b->id, 'label' => $b->name])
            ->values()->all();

        $modelIds = (clone $query)->distinct()->pluck('model_id')->filter()->all();
        $models = ProductModel::with('brand')->whereIn('id', $modelIds)->orderBy('name')
            ->get()
            ->map(function ($model) {
                /** @var ProductModel $model */
                /** @var Brand|null $brand */
                $brand = $model->brand;

                return [
                    'value' => $model->id,
                    'label' => $model->name,
                    'brand_id' => $model->brand_id,
                    'brand_name' => $brand?->name,
                ];
            })
            ->values()->all();

        return [
            'brands' => $brands,
            'models' => $models,
            'types' => $this->wheelTypes(),
            'colors' => (clone $query)->whereNotNull('color')->distinct()->orderBy('color')->pluck('color')->values()->all(),
            'widths' => (clone $query)->whereNotNull('width')->distinct()->orderBy('width')->pluck('width')->map('floatval')->values()->all(),
            'diameters' => (clone $query)->whereNotNull('diameter')->distinct()->orderBy('diameter')->pluck('diameter')->map('intval')->values()->all(),
            'pcds' => (clone $query)->whereNotNull('pcd')->distinct()->orderBy('pcd')->pluck('pcd')->values()->all(),
            'ets' => (clone $query)->whereNotNull('et')->distinct()->orderBy('et')->pluck('et')->map('floatval')->values()->all(),
            'hub_diameters' => (clone $query)->whereNotNull('hub_diameter')->distinct()->orderBy('hub_diameter')->pluck('hub_diameter')->map('floatval')->values()->all(),
            'is_bestseller' => $this->boolValues(clone $query, 'is_bestseller'),
            'is_new' => $this->boolValues(clone $query, 'is_new'),
        ];
    }

    private function wheelTypes(): array
    {
        return [
            ['value' => 'alloy', 'label' => 'Литые'],
            ['value' => 'steel', 'label' => 'Стальные'],
            ['value' => 'forged', 'label' => 'Кованые'],
        ];
    }

    private function boolValues($query, string $column): array
    {
        return $query->whereNotNull($column)->distinct()
            ->pluck($column)
            ->map(fn ($v) => (bool) $v)
            ->values()
            ->all();
    }
}
