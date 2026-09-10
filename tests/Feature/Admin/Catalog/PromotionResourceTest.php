<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Resources\Promotions\Pages\CreatePromotion;
use App\Filament\Resources\Promotions\Pages\ListPromotions;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Promotion\Promotion;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** PromotionResource панели: CRUD акций и привязка к товару/бренду/каталогу. */
class PromotionResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private TireProduct $tire;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');

        $this->brand = Brand::factory()->create(['name' => 'Nokian', 'type' => 'both']);
        $this->tire = TireProduct::factory()->create(['brand_id' => $this->brand->id, 'name' => 'Hakka']);
    }

    public function test_create_promotion(): void
    {
        Livewire::test(CreatePromotion::class)
            ->fillForm([
                'name' => 'Осенняя распродажа',
                'type' => 'percent',
                'value' => 10,
                'starts_at' => '2026-09-01 00:00:00',
                'ends_at' => '2026-09-30 00:00:00',
                'promotable_type' => 'tire',
                'promotable_id' => $this->tire->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('promotions', [
            'name' => 'Осенняя распродажа',
            'type' => 'percent',
            'promotable_type' => 'tire',
            'promotable_id' => $this->tire->id,
        ]);
    }

    public function test_create_rejects_invalid_date_range(): void
    {
        Livewire::test(CreatePromotion::class)
            ->fillForm([
                'name' => 'Кривые даты',
                'type' => 'percent',
                'value' => 10,
                'starts_at' => '2026-09-30 00:00:00',
                'ends_at' => '2026-09-01 00:00:00',
            ])
            ->call('create')
            ->assertHasFormErrors(['ends_at']);

        $this->assertDatabaseCount('promotions', 0);
    }

    public function test_create_catalog_wide_promotion(): void
    {
        Livewire::test(CreatePromotion::class)
            ->fillForm([
                'name' => 'Вся витрина',
                'type' => 'percent',
                'value' => 5,
                'starts_at' => '2026-09-01 00:00:00',
                'ends_at' => '2026-09-30 00:00:00',
                'promotable_type' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $promotion = Promotion::firstOrFail();
        $this->assertNull($promotion->promotable_type);
        $this->assertNull($promotion->promotable_id);
    }

    public function test_create_promotion_for_brand(): void
    {
        Livewire::test(CreatePromotion::class)
            ->fillForm([
                'name' => 'Акция бренда',
                'type' => 'percent',
                'value' => 12,
                'starts_at' => '2026-09-01 00:00:00',
                'ends_at' => '2026-09-30 00:00:00',
                'promotable_type' => 'brand',
                'promotable_id' => $this->brand->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('promotions', [
            'promotable_type' => 'brand',
            'promotable_id' => $this->brand->id,
        ]);
    }

    public function test_table_filters_by_type(): void
    {
        $percent = $this->createPromotion('percent');
        $gift = $this->createPromotion('gift');

        Livewire::test(ListPromotions::class)
            ->filterTable('type', 'gift')
            ->assertCanSeeTableRecords([$gift])
            ->assertCanNotSeeTableRecords([$percent]);
    }

    public function test_delete_promotion(): void
    {
        $promotion = $this->createPromotion('percent');

        Livewire::test(ListPromotions::class)
            ->callTableAction('delete', $promotion);

        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    private function createPromotion(string $type): Promotion
    {
        return Promotion::create([
            'name' => "Акция {$type}",
            'type' => $type,
            'value' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);
    }
}
