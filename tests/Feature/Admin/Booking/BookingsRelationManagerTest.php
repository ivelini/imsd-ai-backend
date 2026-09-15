<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\Bookings\BookingResource;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Clusters\Booking\Resources\Slots\Pages\EditSlot;
use App\Filament\Clusters\Booking\Resources\Slots\RelationManagers\BookingsRelationManager;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\Models\Booking\BookingService;
use App\Models\Booking\PriceRule;
use App\Models\Booking\Slot;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Записи на слоте: таблица на странице слота, добавление, удаление и переход в запись. */
class BookingsRelationManagerTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Slot $slot;

    private BookingService $tireService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->tireService = BookingService::create([
            'name' => 'Шиномонтаж R16',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 15000,
        ]);
        PriceRule::create([
            'service_id' => $this->tireService->id,
            'radius' => 16,
            'car_type' => CarType::Passenger,
            'price' => 15000,
        ]);

        // Дата — завтра: форма создания записи предлагает только будущие слоты, прошедший слот она отклонит
        $this->slot = Slot::create(['date' => now()->addDay()->toDateString(), 'hour' => 10]);
    }

    public function test_lists_bookings_with_services_and_quantity(): void
    {
        $balance = BookingService::create([
            'name' => 'Балансировка',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 5000,
        ]);

        $booking = $this->booking($this->slot, 'Иван');
        $this->addItem($booking, $this->tireService, quantity: 2);
        $this->addItem($booking, $balance, quantity: 1);

        $otherSlot = Slot::create(['date' => '2026-09-12', 'hour' => 11]);
        $stranger = $this->booking($otherSlot, 'Сергей');

        Livewire::test(BookingsRelationManager::class, $this->relationManagerParams())
            ->assertCanSeeTableRecords([$booking])
            ->assertCanNotSeeTableRecords([$stranger])
            ->assertTableColumnStateSet('services', ['Шиномонтаж R16 — 2 шт', 'Балансировка — 1 шт'], $booking);
    }

    /** Кнопка добавляет запись к этому же слоту: форма создания открывается с уже выбранным слотом. */
    public function test_create_booking_from_slot(): void
    {
        Livewire::test(BookingsRelationManager::class, $this->relationManagerParams())
            ->assertTableActionHasUrl('createBooking', BookingResource::getUrl('create', ['slot_id' => $this->slot->id]));

        Livewire::withQueryParams(['slot_id' => $this->slot->id])
            ->test(CreateBooking::class)
            ->fillForm([
                'phone' => '79001234567',
                'name' => 'Иван',
                'plate' => 'А 000 АА 174',
                'radius' => 16,
                'car_type' => 'passenger',
                'composition' => [
                    ['service_id' => $this->tireService->id, 'quantity' => 2],
                ],
                'close_slot' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::firstOrFail();
        $this->assertSame($this->slot->id, $booking->slot_id);
        $this->assertSame(30000, $booking->total_price->toKopecks());
        $this->assertSame(2, $booking->items()->sole()->quantity);
    }

    /** Удаление — простая строка БД: привязка закрытия снимается FK-правилом, слот остаётся закрытым. */
    public function test_delete_booking_keeps_slot_closed(): void
    {
        $booking = $this->booking($this->slot, 'Иван');
        $this->slot->update(['is_closed' => true, 'booking_id' => $booking->id]);

        Livewire::test(BookingsRelationManager::class, $this->relationManagerParams())
            ->callTableAction('delete', $booking);

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('booking_slots', [
            'id' => $this->slot->id,
            'is_closed' => true,
            'booking_id' => null,
        ]);
    }

    public function test_edit_action_links_to_booking_page(): void
    {
        $booking = $this->booking($this->slot, 'Иван');

        Livewire::test(BookingsRelationManager::class, $this->relationManagerParams())
            ->assertTableActionHasUrl('edit', BookingResource::getUrl('edit', ['record' => $booking]), $booking);
    }

    /** @return array<string, mixed> */
    private function relationManagerParams(): array
    {
        return [
            'ownerRecord' => $this->slot,
            'pageClass' => EditSlot::class,
        ];
    }

    private function booking(Slot $slot, string $client): Booking
    {
        return Booking::factory()->forSlot($slot)->create([
            'user_id' => User::factory()->bookingClient()->create(['name' => $client])->id,
        ]);
    }

    private function addItem(Booking $booking, BookingService $service, int $quantity): void
    {
        BookingItem::create([
            'booking_id' => $booking->id,
            'service_id' => $service->id,
            'price' => $service->base_price,
            'quantity' => $quantity,
        ]);
    }
}
