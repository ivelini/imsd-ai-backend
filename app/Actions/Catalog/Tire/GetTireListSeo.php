<?php

namespace App\Actions\Catalog\Tire;

use App\Models\Catalog\Brand\Brand;
use App\Services\Catalog\SeoTitleBuilder;

/**
 * SEO-мета листинга: при выбранном brand — из brands (title с городом, description);
 * без brand — дефолт из config/shop.php.
 */
final readonly class GetTireListSeo
{
    /** @param  array{title: string, description: string|null}  $defaultSeo  */
    public function __construct(
        private array $defaultSeo,
    ) {}

    /** @return array{title: string, description: string|null} */
    public function execute(?string $brandSlug, string $cityName): array
    {
        if ($brandSlug === null) {
            // {city} — плейсхолдер выбранного города в конфиг-строке
            return [
                'title' => str_replace('{city}', SeoTitleBuilder::prepositionalCity($cityName), $this->defaultSeo['title']),
                'description' => $this->defaultSeo['description'],
            ];
        }

        $brand = Brand::query()
            ->where('slug', $brandSlug)
            ->first(['name', 'description', 'type']);

        if ($brand === null) {
            return $this->defaultSeo;
        }

        return [
            'title' => SeoTitleBuilder::title($brand->type, $brand->name, $cityName),
            'description' => $brand->description,
        ];
    }
}
