<?php

namespace Tests\Feature\Admin\Booking;

use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\CreateBookingService;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\EditBookingService;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\ListBookingServices;
use App\Models\Auth\Admin;
use App\Models\Booking\BookingService;
use Database\Seeders\BookingCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** BookingServiceResource панели: рубли в форме, копейки в БД (решение №10). */
class BookingServiceResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_create_service_stores_rubles_as_kopecks(): void
    {
        Livewire::test(CreateBookingService::class)
            ->fillForm([
                'name' => 'Замена вентиля',
                'category' => 'tire',
                'is_active' => true,
                'base_price' => '150.00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('booking_services', [
            'name' => 'Замена вентиля',
            'base_price' => 15000, // рубли формы → копейки БД
        ]);
    }

    public function test_list_shows_services_with_category_label(): void
    {
        $this->seed(BookingCatalogSeeder::class);

        Livewire::test(ListBookingServices::class)
            ->assertSee('Снятие и установка колёс')
            ->assertSee('Шиномонтаж');
    }

    public function test_edit_price_updates_kopecks(): void
    {
        $service = BookingService::create([
            'name' => 'Балансировка',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 5000,
        ]);

        Livewire::test(EditBookingService::class, ['record' => $service->id])
            ->fillForm(['base_price' => '200.50'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(20050, $service->fresh()->base_price->toKopecks());
    }
}
