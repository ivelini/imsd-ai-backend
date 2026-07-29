<?php

namespace App\Models\Catalog\Builders;

use App\Models\Catalog\TireProduct;
use Illuminate\Database\Eloquent\Builder;

/** Кастомный Builder для TireProduct — фильтры каталога.
 *
 * @extends Builder<TireProduct>
 */
class TireProductBuilder extends Builder
{
    public function search(string $search): self
    {
        $q = '%'.$search.'%';
        $this->where(function (Builder $query) use ($q) {
            $query->where('tire_products.name', 'like', $q)
                ->orWhere('tire_products.ean', 'like', $q);
        });

        return $this;
    }

    public function byBrand(int $brandId): self
    {
        $this->where('tire_products.brand_id', $brandId);

        return $this;
    }

    public function published(bool $published): self
    {
        $this->where('tire_products.is_published', $published);

        return $this;
    }
}
