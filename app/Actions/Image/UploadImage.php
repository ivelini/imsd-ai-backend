<?php

namespace App\Actions\Image;

use App\DTOs\Image\UploadImageInput;
use App\Models\Image;
use App\Services\Admin\ImageService;

/** Загрузить изображение товара. */
final readonly class UploadImage
{
    public function __construct(
        private ImageService $imageService,
    ) {}

    public function execute(UploadImageInput $input): Image
    {
        $type = $this->imageService->resolveMorphType($input->imageableType);

        $count = Image::where('imageable_type', $type)
            ->where('imageable_id', $input->imageableId)
            ->count();

        $this->imageService->ensureImageLimit($count);

        $path = $input->file->store('images', 'public');

        return Image::create([
            'imageable_type' => $type,
            'imageable_id' => $input->imageableId,
            'path' => $path,
            'sort' => $count,
            'is_main' => $count === 0,
        ]);
    }
}
