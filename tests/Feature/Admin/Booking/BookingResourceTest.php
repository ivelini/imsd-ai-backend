<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Booking\BookingSource;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\ListBookings;
use App\Models\Auth\Admin;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingService;
use App\Models\Booking\PriceRule;
use App\Models\Booking\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** BookingResource панели: создание записи оператором, статусы, отмена. */
class BookingResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    private BookingService $service;

    private Slot $slot;

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
        PriceRule::create([
            'service_id' => $this->service->id,
            'radius' => 13,
            'car_type' => CarType::Passenger,
            'price' => 15000,
        ]);

        $this->slot = Slot::create(['date' => now()->addDay()->toDateString(), 'hour' => 11]);
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'phone' => '79001234567',
            'name' => 'Иван',
            'plate' => 'А 000 АА 174',
            'slot_id' => $this->slot->id,
            'radius' => 13,
            'car_type' => 'passenger',
            'composition' => [
                ['service_id' => $this->service->id, 'quantity' => 4],
            ],
            'close_slot' => true,
        ];
    }

    public function test_create_booking_from_admin_with_snapshot(): void
    {
        Livewire::test(CreateBooking::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::firstOrFail();
        $this->assertSame(BookingSource::Admin, $booking->source);
        $this->assertSame($this->admin->id, $booking->operator_id);
        $this->assertSame(60000, $booking->total_price->toKopecks()); // серверный пересчёт, сумма не передаётся
        $this->assertSame('79001234567', $booking->user->phone); // клиент — единая users
        $this->assertSame(15000, $booking->items()->firstOrFail()->price->toKopecks());

        // чекбокс «закрыть слот»: слот закрыт и привязан к записи
        $this->assertTrue($this->slot->fresh()->is_closed);
        $this->assertSame($booking->id, $this->slot->fresh()->booking_id);
    }

    public function test_admin_booking_does_not_reclose_occupied_slot(): void
    {
        // Закрытие — не барьер для записи оператора (ФТ-16 tireslot): слот остаётся закрытым
        // с прежней привязкой, новая запись на него не перезакрывает
        $this->slot->update(['is_closed' => true, 'booking_id' => null]);

        Livewire::test(CreateBooking::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::firstOrFail();
        $this->assertTrue($this->slot->fresh()->is_closed);
        $this->assertNull($this->slot->fresh()->booking_id); // привязка не перезаписана
        $this->assertSame($this->slot->id, $booking->slot_id);
    }

    public function test_list_shows_bookings_with_status_labels(): void
    {
        Booking::factory()->create(['status' => BookingStatus::Confirmed]);
        Booking::factory()->done()->create();

        Livewire::test(ListBookings::class)
            ->assertSee('Подтверждена')
            ->assertSee('Завершена');
    }

    public function test_complete_action_changes_status(): void
    {
        $booking = Booking::factory()->create();

        Livewire::test(ListBookings::class)
            ->callTableAction('complete', $booking);

        $this->assertSame(BookingStatus::Done, $booking->fresh()->status);
    }

    public function test_cancel_action_saves_reason(): void
    {
        $booking = Booking::factory()->create();

        Livewire::test(ListBookings::class)
            ->callTableAction('cancel', $booking, data: ['cancel_reason' => 'Клиент передумал']);

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertSame('Клиент передумал', $booking->fresh()->cancel_reason);
    }
}
