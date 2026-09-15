<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\CreateBookingService;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\EditBookingService;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\ListBookingServices;
use App\Models\Auth\Admin;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\Models\Booking\BookingService;
use App\Models\Booking\ComplexService;
use App\Models\Booking\PriceRule;
use Database\Seeders\BookingCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** BookingServiceResource панели: рубли в форме, копейки в БД (решение №10), удаление — только неиспользуемой услуги. */
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

    public function test_delete_service_with_price_rules_is_blocked(): void
    {
        $service = $this->createService('Балансировка');
        PriceRule::create([
            'service_id' => $service->id,
            'radius' => 13,
            'car_type' => CarType::Passenger,
            'price' => 15000,
        ]);

        Livewire::test(ListBookingServices::class)
            ->callTableAction('delete', $service)
            ->assertNotified(); // правило домена показано нотификацией, а не ошибкой БД

        $this->assertDatabaseHas('booking_services', ['id' => $service->id]);
    }

    public function test_delete_service_with_booking_items_is_blocked(): void
    {
        $service = $this->createService('Снятие и установка колёс');
        $booking = Booking::factory()->create();
        BookingItem::create([
            'booking_id' => $booking->id,
            'service_id' => $service->id,
            'price' => 15000,
            'quantity' => 1,
        ]);

        Livewire::test(ListBookingServices::class)
            ->callTableAction('delete', $service)
            ->assertNotified();

        $this->assertDatabaseHas('booking_services', ['id' => $service->id]);
    }

    public function test_delete_service_in_complex_is_blocked(): void
    {
        $service = $this->createService('Балансировка');
        $complex = ComplexService::create(['name' => 'Сезонный шиномонтаж', 'is_active' => true]);
        $complex->services()->attach($service->id);

        Livewire::test(ListBookingServices::class)
            ->callTableAction('delete', $service)
            ->assertNotified();

        $this->assertDatabaseHas('booking_services', ['id' => $service->id]);
    }

    public function test_delete_unused_service_removes_it(): void
    {
        $service = $this->createService('Замена вентиля');

        Livewire::test(EditBookingService::class, ['record' => $service->id])
            ->callAction('delete')
            ->assertNotified();

        $this->assertDatabaseMissing('booking_services', ['id' => $service->id]);
    }

    private function createService(string $name): BookingService
    {
        return BookingService::create([
            'name' => $name,
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 15000,
        ]);
    }
}
