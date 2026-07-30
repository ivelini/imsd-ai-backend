<?php

namespace App\Http\Controllers\Admin\Catalog\Image;

use App\Actions\Image\DeleteImage;
use App\Actions\Image\ListImages;
use App\Actions\Image\ReorderImages;
use App\Actions\Image\SetMainImage;
use App\Actions\Image\UploadImage;
use App\DTOs\Image\UploadImageInput;
use App\Http\Requests\Admin\Catalog\Image\ImageIndexRequest;
use App\Http\Requests\Admin\Catalog\Image\ReorderImagesRequest;
use App\Http\Requests\Admin\Catalog\Image\UploadImageRequest;
use App\Models\Image;
use Illuminate\Http\JsonResponse;

/** Изображения товаров: загрузка, удаление, порядок, главное. */
final readonly class ImageController
{
    public function __construct(
        private ListImages $listImages,
        private UploadImage $uploadImage,
        private DeleteImage $deleteImage,
        private SetMainImage $setMainImage,
        private ReorderImages $reorderImages,
    ) {}

    /**
     * Список изображений товара.
     *
     * @group Изображения
     */
    public function index(ImageIndexRequest $request): JsonResponse
    {
        $data = $request->validated();

        $images = $this->listImages->execute($data['imageable_type'], (int) $data['imageable_id']);

        return response()->json(['data' => $images]);
    }

    /**
     * Загрузить изображение.
     *
     * @group Изображения
     */
    public function store(UploadImageRequest $request): JsonResponse
    {
        $data = $request->validated();

        $image = $this->uploadImage->execute(new UploadImageInput(
            imageableType: $data['imageable_type'],
            imageableId: (int) $data['imageable_id'],
            file: $request->file('image'),
        ));

        return response()->json(['data' => $image], 201);
    }

    /**
     * Удалить изображение.
     *
     * @group Изображения
     */
    public function destroy(int $id): JsonResponse
    {
        $this->deleteImage->execute($id);

        return response()->json(null, 204);
    }

    /**
     * Установить главное изображение.
     *
     * @group Изображения
     */
    public function setMain(int $id): JsonResponse
    {
        $this->setMainImage->execute($id);

        $image = Image::findOrFail($id);

        return response()->json(['data' => $image]);
    }

    /**
     * Обновить порядок изображений.
     *
     * @group Изображения
     */
    public function reorder(ReorderImagesRequest $request): JsonResponse
    {
        $this->reorderImages->execute($request->validated('ids'));

        return response()->json(['message' => 'Порядок обновлён.']);
    }
}
