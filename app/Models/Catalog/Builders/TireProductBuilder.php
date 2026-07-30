<?php

namespace App\Models\Catalog\Builders;

use App\Models\Catalog\Tire\TireProduct;
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
                ->orWhere('tire_products.ean', 'like', $q)
                ->orWhereHas('model', fn (Builder $m) => $m->where('name', 'like', $q));
        });

        return $this;
    }

    public function byBrand(int $brandId): self
    {
        $this->where('tire_products.brand_id', $brandId);

        return $this;
    }

    public function byModel(int $modelId): self
    {
        $this->where('tire_products.model_id', $modelId);

        return $this;
    }

    public function published(bool $published): self
    {
        $this->where('tire_products.is_published', $published);

        return $this;
    }

    public function bySeason(string $season): self
    {
        $this->where('tire_products.season', $season);

        return $this;
    }

    public function studded(bool $studded): self
    {
        $this->where('tire_products.is_studded', $studded);

        return $this;
    }

    public function runflat(bool $runflat): self
    {
        $this->where('tire_products.is_runflat', $runflat);

        return $this;
    }

    public function xl(bool $xl): self
    {
        $this->where('tire_products.is_xl', $xl);

        return $this;
    }

    public function byWidths(array $widths): self
    {
        $this->whereIn('tire_products.width', $widths);

        return $this;
    }

    public function byProfiles(array $profiles): self
    {
        $this->whereIn('tire_products.profile', $profiles);

        return $this;
    }

    public function byDiameters(array $diameters): self
    {
        $this->whereIn('tire_products.diameter', $diameters);

        return $this;
    }

    public function byLoadIndexes(array $indexes): self
    {
        $this->whereIn('tire_products.load_index', $indexes);

        return $this;
    }

    public function bySpeedIndexes(array $indexes): self
    {
        $this->whereIn('tire_products.speed_index', $indexes);

        return $this;
    }

    public function byYears(array $years): self
    {
        $this->whereIn('tire_products.year', $years);

        return $this;
    }

    public function bestseller(bool $bestseller): self
    {
        $this->where('tire_products.is_bestseller', $bestseller);

        return $this;
    }

    public function isNew(bool $isNew): self
    {
        $this->where('tire_products.is_new', $isNew);

        return $this;
    }
}
