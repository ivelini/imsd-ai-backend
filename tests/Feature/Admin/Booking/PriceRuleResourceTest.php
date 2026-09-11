<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\PriceRules\Pages\CreatePriceRule;
use App\Models\Auth\Admin;
use App\Models\Booking\BookingService;
use App\Models\Booking\PriceRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** PriceRuleResource панели: прайс-правило (услуга × радиус × тип), уникальность комбинации. */
class PriceRuleResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
        $this->actingAs($this->admin, 'admin');

        $this->service = BookingService::create([
            'name' => 'Снятие и установка колёс',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 15000,
        ]);
    }

    public function test_create_price_rule_stores_kopecks(): void
    {
        Livewire::test(CreatePriceRule::class)
            ->fillForm([
                'service_id' => $this->service->id,
                'radius' => 13,
                'car_type' => 'passenger',
                'price' => '150.00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('booking_price_rules', [
            'service_id' => $this->service->id,
            'radius' => 13,
            'car_type' => CarType::Passenger,
            'price' => 15000,
        ]);
    }

    public function test_duplicate_combination_rejected(): void
    {
        PriceRule::create([
            'service_id' => $this->service->id,
            'radius' => 13,
            'car_type' => CarType::Passenger,
            'price' => 15000,
        ]);

        Livewire::test(CreatePriceRule::class)
            ->fillForm([
                'service_id' => $this->service->id,
                'radius' => 13,
                'car_type' => 'passenger',
                'price' => '160.00',
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertSame(1, PriceRule::count());
    }
}
