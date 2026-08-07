<?php

namespace Tests\Feature\Admin;

use App\Services\Admin\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Загрузка и замена файлов (logo, image) на public-диске. */
class FileServiceTest extends TestCase
{
    private FileService $fileService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->fileService = new FileService;
    }

    public function test_store_saves_file_and_returns_path(): void
    {
        $path = $this->fileService->store(UploadedFile::fake()->image('logo.png'), 'brands');

        $this->assertStringStartsWith('brands/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_replace_deletes_old_file_and_saves_new(): void
    {
        $old = $this->fileService->store(UploadedFile::fake()->image('old.png'), 'brands');
        $new = $this->fileService->replace($old, UploadedFile::fake()->image('new.png'), 'brands');

        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    public function test_replace_without_old_path_only_saves(): void
    {
        $path = $this->fileService->replace(null, UploadedFile::fake()->image('logo.png'), 'brands');

        Storage::disk('public')->assertExists($path);
    }
}
