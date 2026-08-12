<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Supplier\Supplier;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Observers\BrandObserver;
use App\Observers\SupplierObserver;
use App\Services\Cache\Catalog\ReferencesCacheService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReferencesCacheService::class, function (Application $app): ReferencesCacheService {
            return new ReferencesCacheService(
                $app->make(Repository::class),
                (int) config('cache_ttl.references'),
            );
        });
    }

    public function boot(): void
    {
        Relation::morphMap([
            'tire' => TireProduct::class,
            'wheel' => WheelProduct::class,
            'article' => Article::class,
        ]);

        Brand::observe(BrandObserver::class);
        Supplier::observe(SupplierObserver::class);
    }
}
