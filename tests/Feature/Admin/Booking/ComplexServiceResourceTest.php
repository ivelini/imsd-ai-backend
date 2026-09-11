<?php

namespace Tests\Feature\Admin\Booking;

use App\Filament\Clusters\Booking\Resources\ComplexServices\Pages\CreateComplexService;
use App\Models\Booking\BookingService;
use App\Models\Booking\ComplexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** ComplexServiceResource панели: комплекс услуг с составом. */
class ComplexServiceResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');
    }

    public function test_create_complex_with_composition(): void
    {
        $mounting = BookingService::create([
            'name' => 'Снятие и установка колёс',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 15000,
        ]);
        $balancing = BookingService::create([
            'name' => 'Балансировка колёс',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 14000,
        ]);

        Livewire::test(CreateComplexService::class)
            ->fillForm([
                'name' => 'Комплекс тест',
                'is_active' => true,
                'services' => [$mounting->id, $balancing->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $complex = ComplexService::where('name', 'Комплекс тест')->firstOrFail();
        $this->assertSame(
            [$mounting->id, $balancing->id],
            $complex->services()->orderBy('booking_services.id')->pluck('booking_services.id')->all(),
        );
    }
}
