<?php

namespace Tests\Unit\Http\Resources;

use App\DTOs\Catalog\Tire\EuroLabel;
use App\Http\Resources\Admin\Catalog\Tire\TireProductResource;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/** Форматирование ответа шины. */
class TireProductResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_returns_expected_structure(): void
    {
        $brand = Brand::factory()->create();
        $tire = TireProduct::factory()->make([
            'brand_id' => $brand->id,
            'ean' => 'EAN-001',
            'name' => 'Test Tire',
            'season' => 'winter',
            'width' => 205,
            'profile' => 55,
            'diameter' => '16',
        ]);

        $tire->setAttribute('created_at', now());

        $resource = new TireProductResource($tire);
        $result = $resource->toArray(new Request);

        $this->assertSame('Test Tire', $result['name']);
        $this->assertSame('winter', $result['season']);
        $this->assertSame(205, $result['width']);
        $this->assertArrayNotHasKey('pcd', $result);
    }

    public function test_to_array_serializes_euro_label_object_or_null(): void
    {
        $withLabel = TireProduct::factory()->make(['euro_label' => new EuroLabel('D', 'C', '71')]);
        $withLabel->setAttribute('created_at', now());

        $json = (new TireProductResource($withLabel))->toJson();

        $this->assertSame(
            ['rollingResistance' => 'D', 'wetGrip' => 'C', 'noiseEmission' => '71'],
            json_decode($json, true)['euro_label'],
        );

        $withoutLabel = TireProduct::factory()->make();
        $withoutLabel->setAttribute('created_at', now());

        $json = (new TireProductResource($withoutLabel))->toJson();

        $this->assertNull(json_decode($json, true)['euro_label']);
    }
}
