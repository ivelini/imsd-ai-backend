<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\TireProducts\Pages\EditTireProduct;
use App\Filament\Resources\WheelProducts\Pages\EditWheelProduct;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** RelationManager изображений товара: загрузка, лимит, главное, порядок, удаление файла. */
class ImagesRelationManagerTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private TireProduct $tire;

    private WheelProduct $wheel;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->actingAs($this->createAdmin(), 'admin');

        $brand = Brand::factory()->create();
        $this->tire = TireProduct::factory()->create(['brand_id' => $brand->id]);
        $this->wheel = WheelProduct::factory()->create(['brand_id' => $brand->id]);
    }

    public function test_upload_via_relation_manager_creates_image(): void
    {
        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('create', data: [
                'path' => UploadedFile::fake()->image('tire.jpg'),
            ])
            ->assertHasNoActionErrors();

        $image = Image::where('imageable_id', $this->tire->id)->firstOrFail();

        $this->assertSame($this->tire->getMorphClass(), $image->imageable_type);
        $this->assertTrue($image->is_main);
        $this->assertSame(0, $image->sort);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_upload_rejects_above_limit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            Image::create([
                'imageable_type' => $this->tire->getMorphClass(),
                'imageable_id' => $this->tire->id,
                'path' => "images/{$i}.jpg",
                'sort' => $i,
                'is_main' => $i === 0,
            ]);
        }

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('create', data: [
                'path' => UploadedFile::fake()->image('eleventh.jpg'),
            ]);

        $this->assertSame(10, Image::where('imageable_id', $this->tire->id)->count());
    }

    public function test_delete_main_reassigns_next(): void
    {
        $main = $this->createImage(sort: 0, isMain: true, path: 'images/a.jpg');
        $next = $this->createImage(sort: 1, isMain: false, path: 'images/b.jpg');

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('delete', $main);

        $this->assertDatabaseMissing('images', ['id' => $main->id]);
        $this->assertDatabaseHas('images', ['id' => $next->id, 'is_main' => true]);
    }

    public function test_delete_non_main_keeps_main_flag(): void
    {
        $main = $this->createImage(sort: 0, isMain: true, path: 'images/a.jpg');
        $other = $this->createImage(sort: 1, isMain: false, path: 'images/b.jpg');

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('delete', $other);

        $this->assertDatabaseHas('images', ['id' => $main->id, 'is_main' => true]);
        $this->assertDatabaseMissing('images', ['id' => $other->id]);
    }

    public function test_delete_removes_file_from_disk(): void
    {
        Storage::disk('public')->put('images/a.jpg', 'content');

        $image = $this->createImage(sort: 0, isMain: true, path: 'images/a.jpg');

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('delete', $image);

        Storage::disk('public')->assertMissing('images/a.jpg');
    }

    public function test_set_main_action_switches_main_image(): void
    {
        $first = $this->createImage(sort: 0, isMain: true);
        $this->createImage(sort: 1, isMain: false);
        $third = $this->createImage(sort: 2, isMain: false);

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->callTableAction('setMain', $third);

        $this->assertFalse($first->refresh()->is_main);
        $this->assertTrue($third->refresh()->is_main);
    }

    public function test_reorder_updates_sort(): void
    {
        $first = $this->createImage(sort: 0, isMain: true);
        $second = $this->createImage(sort: 1, isMain: false);
        $third = $this->createImage(sort: 2, isMain: false);

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $this->tire,
            'pageClass' => EditTireProduct::class,
        ])
            ->call('reorderTable', [$third->id, $first->id, $second->id]);

        $this->assertSame(0, $third->refresh()->sort);
        $this->assertSame(1, $first->refresh()->sort);
        $this->assertSame(2, $second->refresh()->sort);
    }

    public function test_upload_works_for_wheel(): void
    {
        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $this->wheel,
            'pageClass' => EditWheelProduct::class,
        ])
            ->callTableAction('create', data: [
                'path' => UploadedFile::fake()->image('wheel.jpg'),
            ])
            ->assertHasNoActionErrors();

        $image = Image::where('imageable_id', $this->wheel->id)->firstOrFail();

        $this->assertSame($this->wheel->getMorphClass(), $image->imageable_type);
    }

    private function createImage(int $sort, bool $isMain, string $path = 'images/x.jpg'): Image
    {
        return Image::create([
            'imageable_type' => $this->tire->getMorphClass(),
            'imageable_id' => $this->tire->id,
            'path' => $path,
            'sort' => $sort,
            'is_main' => $isMain,
        ]);
    }
}
