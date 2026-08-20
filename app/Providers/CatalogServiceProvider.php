<?php

namespace App\Providers;

use App\Http\Controllers\Catalog\GetCityReferenceController;
use App\Http\Controllers\Catalog\GetTireFilterValuesController;
use App\Http\Controllers\Catalog\GetTireListController;
use App\Models\Article;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\DeliverySchedule;
use App\Observers\BrandObserver;
use App\Observers\CityDeliveryTimeObserver;
use App\Observers\CityPriceRuleObserver;
use App\Observers\DeliveryScheduleObserver;
use App\Observers\StockObserver;
use App\Observers\TireProductObserver;
use App\Observers\WarehouseMarkupRuleObserver;
use App\Services\Cache\Catalog\ReferencesCacheService;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;
use App\Services\Cache\Catalog\TireListCacheService;
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

        $this->app->bind(TireFilterValuesCacheService::class, function (Application $app): TireFilterValuesCacheService {
            return new TireFilterValuesCacheService(
                $app->make(Repository::class),
                (int) config('cache_ttl.tire_filter'),
            );
        });

        $this->app->bind(TireListCacheService::class, function (Application $app): TireListCacheService {
            return new TireListCacheService(
                $app->make(Repository::class),
                (int) config('cache_ttl.tire_list'),
            );
        });

        $this->app->when(GetTireFilterValuesController::class)
            ->needs('$defaultCityName')
            ->giveConfig('shop.default_city');

        $this->app->when(GetTireListController::class)
            ->needs('$defaultCityName')
            ->giveConfig('shop.default_city');

        $this->app->when(GetCityReferenceController::class)
            ->needs('$defaultCityName')
            ->giveConfig('shop.default_city');
    }

    public function boot(): void
    {
        Relation::morphMap([
            'tire' => TireProduct::class,
            'wheel' => WheelProduct::class,
            'article' => Article::class,
        ]);

        Brand::observe(BrandObserver::class);

        TireProduct::observe(TireProductObserver::class);
        Stock::observe(StockObserver::class);
        DeliverySchedule::observe(DeliveryScheduleObserver::class);
        CityDeliveryTime::observe(CityDeliveryTimeObserver::class);
        CityPriceRule::observe(CityPriceRuleObserver::class);
        WarehouseMarkupRule::observe(WarehouseMarkupRuleObserver::class);
    }
}
