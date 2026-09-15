<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\PriceRules\Pages\CreatePriceRule;
use App\Filament\Clusters\Booking\Resources\PriceRules\Pages\ListPriceRules;
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

    public function test_search_by_radius_with_prefix(): void
    {
        // Радиус в списке подписан как «R17» — поиск обязан понимать ту же запись
        $r17 = $this->createRule($this->service, radius: 17, price: 20000);
        $r16 = $this->createRule($this->secondService(), radius: 16, price: 15000);

        Livewire::test(ListPriceRules::class)
            ->searchTable('R17')
            ->assertCanSeeTableRecords([$r17])
            ->assertCanNotSeeTableRecords([$r16]);
    }

    public function test_search_by_radius_digits(): void
    {
        $r16 = $this->createRule($this->service, radius: 16, price: 15000);
        $r15 = $this->createRule($this->secondService(), radius: 15, price: 14000);

        Livewire::test(ListPriceRules::class)
            ->searchTable('16')
            ->assertCanSeeTableRecords([$r16])
            ->assertCanNotSeeTableRecords([$r15]);
    }

    public function test_search_by_service_name_still_works(): void
    {
        $balancing = $this->createRule($this->secondService(), radius: 16, price: 15000);
        $mounting = $this->createRule($this->service, radius: 17, price: 20000);

        Livewire::test(ListPriceRules::class)
            ->searchTable('Балансировка')
            ->assertCanSeeTableRecords([$balancing])
            ->assertCanNotSeeTableRecords([$mounting]);
    }

    private function secondService(): BookingService
    {
        return BookingService::create([
            'name' => 'Балансировка',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 14000,
        ]);
    }

    private function createRule(BookingService $service, int $radius, int $price): PriceRule
    {
        return PriceRule::create([
            'service_id' => $service->id,
            'radius' => $radius,
            'car_type' => CarType::Passenger,
            'price' => $price,
        ]);
    }
}
