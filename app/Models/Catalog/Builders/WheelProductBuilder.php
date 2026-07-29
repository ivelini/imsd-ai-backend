<?php

namespace App\Models\Catalog\Builders;

use App\Models\Catalog\WheelProduct;
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
                ->orWhere('wheel_products.ean', 'like', $q);
        });

        return $this;
    }

    public function byBrand(int $brandId): self
    {
        $this->where('wheel_products.brand_id', $brandId);

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
}
