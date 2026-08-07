<?php

namespace Tests\Feature\Catalog;

use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use App\Services\Catalog\DisplayNameResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Заполнение названия товара из названия модели. */
class DisplayNameResolverTest extends TestCase
{
    use RefreshDatabase;

    private DisplayNameResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new DisplayNameResolver;
    }

    public function test_resolve_fills_name_from_model(): void
    {
        $model = $this->createModel('Test Model');

        $result = $this->resolver->resolve(['name' => '', 'model_id' => $model->id]);

        $this->assertSame(['name' => 'Test Model', 'model_id' => $model->id], $result);
    }

    public function test_resolve_does_not_overwrite_existing_name(): void
    {
        $result = $this->resolver->resolve(['name' => 'Custom', 'model_id' => 1]);

        $this->assertSame(['name' => 'Custom', 'model_id' => 1], $result);
    }

    public function test_resolve_throws_when_model_missing(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->resolver->resolve(['name' => '', 'model_id' => 999]);
    }

    private function createModel(string $name): ProductModel
    {
        $brand = Brand::factory()->create();

        return ProductModel::create([
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => 'test-model',
            'type' => 'tire',
        ]);
    }
}
