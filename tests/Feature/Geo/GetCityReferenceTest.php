<?php

namespace Tests\Feature\Geo;

use App\Models\Delivery\City;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Справочник городов публичного API: список {label, value, slug} + город по умолчанию. */
class GetCityReferenceTest extends TestCase
{
    use RefreshDatabase;

    private const PATH = '/api/reference/city';

    private Region $region;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
    }

    public function test_returns_cities_sorted_and_default(): void
    {
        $other = City::create(['region_id' => $this->region->id, 'name' => 'Екатеринбург', 'sort' => 1]);
        $default = City::create(['region_id' => $this->region->id, 'name' => 'Челябинск', 'sort' => 1]);

        $response = $this->getJson(self::PATH);

        $response->assertOk();
        // default_city из config/shop.php = Челябинск
        $this->assertSame(
            ['label' => $default->name, 'value' => $default->id],
            $response->json('meta.default'),
        );
        $this->assertSame(
            [
                ['label' => 'Екатеринбург', 'value' => $other->id, 'slug' => null, 'region' => ['id' => $this->region->id, 'name' => $this->region->name]],
                ['label' => 'Челябинск', 'value' => $default->id, 'slug' => null, 'region' => ['id' => $this->region->id, 'name' => $this->region->name]],
            ],
            $response->json('data'),
        );
    }

    public function test_default_null_when_default_city_absent(): void
    {
        City::create(['region_id' => $this->region->id, 'name' => 'Екатеринбург', 'sort' => 1]);

        $this->getJson(self::PATH)
            ->assertOk()
            ->assertJsonPath('meta.default', null);
    }
}
