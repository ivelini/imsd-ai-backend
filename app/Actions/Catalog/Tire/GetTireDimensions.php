<?php

namespace App\Actions\Catalog\Tire;

use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Tire\TireProduct;

/** Доступные значения фильтров шин с учётом контекста. */
final readonly class GetTireDimensions
{
    public function execute(array $params): array
    {
        $query = TireProduct::query();

        if (! empty($params['search'])) {
            $query->search($params['search']);
        }

        if (! empty($params['brand_id'])) {
            $query->byBrand((int) $params['brand_id']);
        }

        if (! empty($params['model_id'])) {
            $query->byModel((int) $params['model_id']);
        }

        if (! empty($params['season'])) {
            $query->bySeason($params['season']);
        }

        if (isset($params['is_published'])) {
            $query->published(filter_var($params['is_published'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($params['is_studded'])) {
            $query->studded(filter_var($params['is_studded'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($params['is_runflat'])) {
            $query->runflat(filter_var($params['is_runflat'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($params['is_xl'])) {
            $query->xl(filter_var($params['is_xl'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($params['width'])) {
            $query->byWidths(array_map('intval', $params['width']));
        }

        if (! empty($params['profile'])) {
            $query->byProfiles(array_map('intval', $params['profile']));
        }

        if (! empty($params['diameter'])) {
            $query->byDiameters($params['diameter']);
        }

        if (! empty($params['load_index'])) {
            $query->byLoadIndexes($params['load_index']);
        }

        if (! empty($params['speed_index'])) {
            $query->bySpeedIndexes($params['speed_index']);
        }

        if (! empty($params['year'])) {
            $query->byYears(array_map('intval', $params['year']));
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
            'seasons' => $this->seasons(),
            'widths' => (clone $query)->whereNotNull('width')->distinct()->orderBy('width')->pluck('width')->map('intval')->values()->all(),
            'profiles' => (clone $query)->whereNotNull('profile')->distinct()->orderBy('profile')->pluck('profile')->map('intval')->values()->all(),
            'diameters' => (clone $query)->whereNotNull('diameter')->distinct()->orderBy('diameter')->pluck('diameter')->values()->all(),
            'load_indexes' => (clone $query)->whereNotNull('load_index')->distinct()->orderBy('load_index')->pluck('load_index')->values()->all(),
            'speed_indexes' => (clone $query)->whereNotNull('speed_index')->distinct()->orderBy('speed_index')->pluck('speed_index')->values()->all(),
            'years' => (clone $query)->whereNotNull('year')->distinct()->orderBy('year')->pluck('year')->map('intval')->values()->all(),
            'is_studded' => $this->boolValues(clone $query, 'is_studded'),
            'is_runflat' => $this->boolValues(clone $query, 'is_runflat'),
            'is_xl' => $this->boolValues(clone $query, 'is_xl'),
            'is_bestseller' => $this->boolValues(clone $query, 'is_bestseller'),
            'is_new' => $this->boolValues(clone $query, 'is_new'),
        ];
    }

    private function seasons(): array
    {
        return [
            ['value' => 'winter', 'label' => 'Зимняя'],
            ['value' => 'summer', 'label' => 'Летняя'],
            ['value' => 'all-season', 'label' => 'Всесезонная'],
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
