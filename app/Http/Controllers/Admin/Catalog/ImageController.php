<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\UploadImageRequest;
use App\Models\Catalog\TireProduct;
use App\Models\Catalog\WheelProduct;
use App\Models\Image;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Изображения товаров: загрузка, удаление, порядок, главное. */
final readonly class ImageController
{
    private const MAX_IMAGES = 10;

    /**
     * Список изображений товара.
     *
     * @queryParam imageable_type string required Тип: tire, wheel.
     * @queryParam imageable_id int required ID товара.
     *
     * @group Изображения
     */
    public function index(Request $request): JsonResponse
    {
        $images = Image::where('imageable_type', $this->resolveMorphType($request))
            ->where('imageable_id', (int) $request->query('imageable_id'))
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $images]);
    }

    /**
     * Загрузить изображение.
     *
     * @group Изображения
     */
    public function store(UploadImageRequest $request): JsonResponse
    {
        $type = $this->resolveMorphType($request);
        $id = (int) $request->imageable_id;

        $count = Image::where('imageable_type', $type)
            ->where('imageable_id', $id)
            ->count();

        if ($count >= self::MAX_IMAGES) {
            throw new DomainException('Не более '.self::MAX_IMAGES.' изображений на товар.');
        }

        $path = $request->file('image')->store('images', 'public');

        $isMain = $count === 0;

        $image = Image::create([
            'imageable_type' => $type,
            'imageable_id' => $id,
            'path' => $path,
            'sort' => $count,
            'is_main' => $isMain,
        ]);

        return response()->json(['data' => $image], 201);
    }

    /**
     * Удалить изображение.
     *
     * @group Изображения
     */
    public function destroy(int $id): JsonResponse
    {
        $image = Image::findOrFail($id);
        $image->delete();

        // Если удалили главное — сделать главным следующее по sort
        if ($image->is_main) {
            $next = Image::where('imageable_type', $image->imageable_type)
                ->where('imageable_id', $image->imageable_id)
                ->orderBy('sort')
                ->first();

            if ($next) {
                $next->update(['is_main' => true]);
            }
        }

        return response()->json(null, 204);
    }

    /**
     * Установить главное изображение.
     *
     * @group Изображения
     */
    public function setMain(int $id): JsonResponse
    {
        $image = Image::findOrFail($id);

        Image::where('imageable_type', $image->imageable_type)
            ->where('imageable_id', $image->imageable_id)
            ->update(['is_main' => false]);

        $image->update(['is_main' => true]);

        return response()->json(['data' => $image]);
    }

    /**
     * Обновить порядок изображений.
     *
     * @bodyParam ids int[] required Массив ID изображений в новом порядке.
     *
     * @group Изображения
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        foreach ((array) $request->ids as $index => $imageId) {
            Image::where('id', $imageId)->update(['sort' => $index]);
        }

        return response()->json(['message' => 'Порядок обновлён.']);
    }

    private function resolveMorphType(Request $request): string
    {
        $value = $request->input('imageable_type', $request->query('imageable_type'));

        return match ($value) {
            'tire' => (new TireProduct)->getMorphClass(),
            'wheel' => (new WheelProduct)->getMorphClass(),
            default => throw new DomainException("Некорректный тип товара: {$value}"),
        };
    }
}
