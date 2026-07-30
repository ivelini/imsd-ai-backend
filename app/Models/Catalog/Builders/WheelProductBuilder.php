<?php

namespace App\Models\Catalog\Builders;

use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Database\Eloquent\Builder;

/** Кастомный Builder для WheelProduct — фильтры каталога.
 *
 * @extends Builder<WheelProduct>
 */
class WheelProductBuilder extends Builder
{
    public function search(string $search): self
    {
        $q = '%'.$search.'%';
        $this->where(function (Builder $query) use ($q) {
            $query->where('wheel_products.name', 'like', $q)
                ->orWhere('wheel_products.ean', 'like', $q)
                ->orWhereHas('model', fn (Builder $m) => $m->where('name', 'like', $q));
        });

        return $this;
    }

    public function byBrand(int $brandId): self
    {
        $this->where('wheel_products.brand_id', $brandId);

        return $this;
    }

    public function byModel(int $modelId): self
    {
        $this->where('wheel_products.model_id', $modelId);

        return $this;
    }

    public function published(bool $published): self
    {
        $this->where('wheel_products.is_published', $published);

        return $this;
    }

    public function byType(string $type): self
    {
        $this->where('wheel_products.type', $type);

        return $this;
    }

    public function byColor(string $color): self
    {
        $this->where('wheel_products.color', $color);

        return $this;
    }

    public function byWidths(array $widths): self
    {
        $this->whereIn('wheel_products.width', $widths);

        return $this;
    }

    public function byDiameters(array $diameters): self
    {
        $this->whereIn('wheel_products.diameter', $diameters);

        return $this;
    }

    public function byPcds(array $pcds): self
    {
        $this->whereIn('wheel_products.pcd', $pcds);

        return $this;
    }

    public function byEts(array $ets): self
    {
        $this->whereIn('wheel_products.et', $ets);

        return $this;
    }

    public function byHubDiameters(array $hubDiameters): self
    {
        $this->whereIn('wheel_products.hub_diameter', $hubDiameters);

        return $this;
    }

    public function bestseller(bool $bestseller): self
    {
        $this->where('wheel_products.is_bestseller', $bestseller);

        return $this;
    }

    public function isNew(bool $isNew): self
    {
        $this->where('wheel_products.is_new', $isNew);

        return $this;
    }
}
