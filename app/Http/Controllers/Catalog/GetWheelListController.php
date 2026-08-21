<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Catalog\GetCatalogListSeo;
use App\Actions\Catalog\Wheel\GetWheelList;
use App\DTOs\Catalog\Wheel\WheelListInput;
use App\Http\Requests\Catalog\WheelListRequest;
use App\Http\Resources\Catalog\WheelListItemResource;
use App\Models\Delivery\City;
use App\Preconditions\Geo\EnsureCityExists;
use App\Services\Cache\Catalog\WheelListCacheService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/** Пагинированный список дисков каталога. */
final readonly class GetWheelListController
{
    public function __construct(
        private WheelListCacheService $wheelListCache,
        private GetWheelList $getWheelList,
        private GetCatalogListSeo $getCatalogListSeo,
        private EnsureCityExists $ensureCityExists,
        private string $defaultCityName,
    ) {}

    /**
     * Список дисков для выбранного города (по умолчанию — из config/shop.php).
     *
     * Фильтры каталога, пагинация, сортировка по цене города.
     */
    #[Group('Каталог', weight: 10)]
    #[Response(type: 'array{data: list<array{id: int, name: string, slug: string, brand: array{id: int, name: string, slug: string}, model: array{id: int, name: string, slug: string}|null, width: string|null, diameter: int|null, pcd: string|null, et: string|null, hub_diameter: string|null, type: array{label: string, value: string}|null, color: string|null, price: float|null, delivery_min: int|null, delivery_max: int|null, images: list<array{id: int, url: string}>}>, meta: array{current_page: int, last_page: int, per_page: int, total: int, seo: array{title: string, description: string|null}}}')]
    public function __invoke(WheelListRequest $request): JsonResponse
    {
        $cityId = $request->validated('city_id');
        $citySlug = $request->validated('city');
        $filters = $request->safe()->except(['city_id', 'city', 'page', 'per_page', 'sort_by', 'sort_dir']);
        $page = (int) ($request->validated('page') ?? 1);
        $perPage = (int) ($request->validated('per_page') ?? 48);
        $sortBy = $request->validated('sort_by');
        $sortDir = $request->validated('sort_dir') ?? 'desc';

        // Резолв id города до кеша: ключ строится по резолвленному id (слаг → id или дефолт).
        // Лёгкий value('id') — 1 запрос; тяжёлая загрузка модели — только при промахе кеша.
        // Приоритет: city_id (страховка от удаления после exists-валидации) → city-слаг → дефолт.
        $resolvedCityId = $cityId ?? ($citySlug !== null ? City::where('slug', $citySlug)->value('id') : null);

        // Precondition — только при промахе кеша, перед Action (hit → Action не вызывается).
        // В кеш — сериализованный массив (правило: в кеш только массивы/скаляры, не модели).
        $payload = $this->wheelListCache->remember(function () use ($resolvedCityId, $filters, $page, $perPage, $sortBy, $sortDir): array {
            $city = $resolvedCityId !== null
                ? City::findOrFail($resolvedCityId) // страховка от удаления города после exists-валидации
                : $this->ensureCityExists->ensure($this->defaultCityName);

            $paginator = $this->getWheelList->execute(new WheelListInput(
                cityId: $city->id,
                filters: $filters,
                page: $page,
                perPage: $perPage,
                sortBy: $sortBy,
                sortDir: $sortDir,
            ));

            $seo = $this->getCatalogListSeo->execute($filters['brand'] ?? null, $city->name);

            return $this->buildPayload($paginator, $seo);
        }, $resolvedCityId, $filters, $page, $perPage, $sortBy, $sortDir);

        return response()->json($payload);
    }

    /** @param  array{title: string, description: string|null}  $seo
     *  @return array{data: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, per_page: int, total: int, seo: array{title: string, description: string|null}}} */
    private function buildPayload(LengthAwarePaginator $paginator, array $seo): array
    {
        return [
            // resolve() не рекурсивный — вложенные Resource остаются объектами и ломают кеш;
            // чистые массивы даёт только JSON-roundtrip (JsonSerializable)
            'data' => json_decode(WheelListItemResource::collection($paginator->items())->toJson(), true),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'seo' => $seo,
            ],
        ];
    }
}
