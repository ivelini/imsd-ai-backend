<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\EditBooking;
use App\Filament\Clusters\Booking\Resources\BookingServices\BookingServiceResource;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\EditBookingService;
use App\Filament\Clusters\Booking\Resources\Slots\Pages\EditSlot;
use App\Filament\Clusters\Booking\Resources\Slots\SlotResource;
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

/**
 * Кнопка «Сохранить и закрыть» на страницах правки: сохраняет запись и возвращает
 * на экран, откуда оператор пришёл (адрес входа — заголовок referer).
 */
class SaveAndCloseTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_service_save_and_close_saves_and_returns_to_previous_screen(): void
    {
        $service = $this->createService('Балансировка');
        $from = BookingServiceResource::getUrl('index');

        Livewire::withHeaders(['referer' => $from])
            ->test(EditBookingService::class, ['record' => $service->id])
            ->assertSee('Сохранить и закрыть') // кнопка есть в форме
            ->fillForm(['base_price' => '200.50'])
            ->call('saveAndClose')
            ->assertHasNoFormErrors()
            ->assertRedirect($from);

        $this->assertSame(20050, $service->fresh()->base_price->toKopecks());
    }

    public function test_slot_save_and_close_saves_and_returns_to_previous_screen(): void
    {
        $slot = Slot::create(['date' => now()->addDay()->toDateString(), 'hour' => 10]);
        $from = SlotResource::getUrl('index');

        Livewire::withHeaders(['referer' => $from])
            ->test(EditSlot::class, ['record' => $slot->id])
            ->assertSee('Сохранить и закрыть')
            ->fillForm(['is_closed' => true])
            ->call('saveAndClose')
            ->assertHasNoFormErrors()
            ->assertRedirect($from);

        $this->assertTrue($slot->fresh()->is_closed);
    }

    public function test_booking_save_and_close_saves_and_returns_to_previous_screen(): void
    {
        // Запись оператор открывает с карточки слота — туда и должен вернуться
        $slot = Slot::create(['date' => now()->addDay()->toDateString(), 'hour' => 10]);
        $booking = $this->createBooking($slot);

        $from = SlotResource::getUrl('edit', ['record' => $slot]);

        Livewire::withHeaders(['referer' => $from])
            ->test(EditBooking::class, ['record' => $booking->id])
            ->assertSee('Сохранить и закрыть')
            ->fillForm(['status' => BookingStatus::Done->value])
            ->call('saveAndClose')
            ->assertHasNoFormErrors()
            ->assertRedirect($from);

        $this->assertSame(BookingStatus::Done, $booking->fresh()->status);
    }

    public function test_save_and_close_keeps_form_on_validation_error(): void
    {
        $service = $this->createService('Балансировка');

        Livewire::withHeaders(['referer' => BookingServiceResource::getUrl('index')])
            ->test(EditBookingService::class, ['record' => $service->id])
            ->fillForm(['name' => ''])
            ->call('saveAndClose')
            ->assertHasFormErrors(['name'])
            ->assertNoRedirect();

        $this->assertSame('Балансировка', $service->fresh()->name);
    }

    public function test_save_button_stays_on_page(): void
    {
        // Стража: штатное «Сохранить» сохраняет и остаётся на странице
        $service = $this->createService('Балансировка');

        Livewire::withHeaders(['referer' => BookingServiceResource::getUrl('index')])
            ->test(EditBookingService::class, ['record' => $service->id])
            ->fillForm(['base_price' => '200.50'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNoRedirect();

        $this->assertSame(20050, $service->fresh()->base_price->toKopecks());
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

    /** Запись с одной строкой состава — как её создаёт оператор. */
    private function createBooking(Slot $slot): Booking
    {
        $service = $this->createService('Снятие и установка колёс');
        PriceRule::create([
            'service_id' => $service->id,
            'radius' => 16,
            'car_type' => CarType::Passenger,
            'price' => 15000,
        ]);

        $booking = Booking::factory()->forSlot($slot)->create([
            // ФИО клиента форма требует целиком: карточка записи правит и его
            'user_id' => User::factory()->bookingClient()->create([
                'name' => 'Иван',
                'surname' => 'Петров',
                'phone' => '79001234567',
            ])->id,
            'radius' => 16,
            'car_type' => CarType::Passenger,
        ]);

        BookingItem::create([
            'booking_id' => $booking->id,
            'service_id' => $service->id,
            'price' => 15000,
            'quantity' => 1,
        ]);

        return $booking;
    }
}
