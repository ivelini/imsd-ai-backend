<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\PromotionIndexRequest;
use App\Http\Requests\Admin\Catalog\PromotionRequest;
use App\Http\Resources\Admin\Catalog\PromotionResource;
use App\Models\Catalog\Promotion;
use App\Services\Admin\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD акций. */
final readonly class PromotionController
{
    /** Поля, доступные для сортировки в списке. */
    private const array ALLOWED_SORT = ['id', 'name', 'type', 'value', 'starts_at', 'ends_at', 'created_at'];

    public function __construct(
        private PromotionService $promotionService,
    ) {}

    /**
     * @group Акции
     */
    public function index(PromotionIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $perPage = min(max((int) ($data['per_page'] ?? 50), 10), 100);

        $query = Promotion::query();

        if (! empty($data['search'])) {
            $query->where('name', 'like', '%'.$data['search'].'%');
        }

        if (! empty($data['type'])) {
            $query->where('type', $data['type']);
        }

        if (! empty($data['promotable_type'])) {
            $query->where('promotable_type', $data['promotable_type']);
        }

        if (isset($data['is_active'])) {
            $now = now();
            if (filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)) {
                $query->where('starts_at', '<=', $now)->where('ends_at', '>=', $now);
            } else {
                $query->where(function ($q) use ($now) {
                    $q->where('starts_at', '>', $now)->orWhere('ends_at', '<', $now);
                });
            }
        }

        $sortBy = in_array($data['sort_by'] ?? 'starts_at', self::ALLOWED_SORT, true) ? $data['sort_by'] : 'starts_at';
        $sortDir = ($data['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return PromotionResource::collection(
            $query->orderBy($sortBy, $sortDir)->paginate($perPage)
        );
    }

    /**
     * @group Акции
     */
    public function store(PromotionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data = $this->resolvePromotable($data);

        $promotion = Promotion::create($data);

        return (new PromotionResource($promotion))->response()->setStatusCode(201);
    }

    /**
     * @group Акции
     */
    public function show(int $id): PromotionResource
    {
        return new PromotionResource(Promotion::findOrFail($id));
    }

    /**
     * @group Акции
     */
    public function update(PromotionRequest $request, int $id): PromotionResource
    {
        $data = $request->validated();
        $data = $this->resolvePromotable($data);

        $promotion = Promotion::findOrFail($id);
        $promotion->update($data);

        return new PromotionResource($promotion);
    }

    /**
     * @group Акции
     */
    public function destroy(int $id): JsonResponse
    {
        Promotion::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    /** Преобразовать тип привязки в morph-тип. */
    private function resolvePromotable(array $data): array
    {
        if (! isset($data['promotable_type'])) {
            return $data;
        }

        $data['promotable_type'] = $this->promotionService->resolvePromotableType($data['promotable_type']);

        return $data;
    }
}
