<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Booking\BookingSource;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\EditBooking;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\ListBookings;
use App\Models\Auth\Admin;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\Models\Booking\BookingService;
use App\Models\Booking\PriceRule;
use App\Models\Booking\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** BookingResource панели: создание записи оператором, статусы, отмена. */
class BookingResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    private BookingService $service;

    private BookingService $extraService;

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
        // Куб прайса: R13/passenger — 150 ₽, R16/passenger — 180 ₽, R16/suv — 200 ₽ (цена строки в форме — рубли)
        PriceRule::create([
            'service_id' => $this->service->id,
            'radius' => 13,
            'car_type' => CarType::Passenger,
            'price' => 15000,
        ]);
        PriceRule::create([
            'service_id' => $this->service->id,
            'radius' => 16,
            'car_type' => CarType::Passenger,
            'price' => 18000,
        ]);
        PriceRule::create([
            'service_id' => $this->service->id,
            'radius' => 16,
            'car_type' => CarType::Suv,
            'price' => 20000,
        ]);

        // Услуга без прайс-правил: её цена от радиуса не зависит
        $this->extraService = BookingService::create([
            'name' => 'Доплата за низкий профиль',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 5000,
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
            'start_time' => '11:00',
            'radius' => 13,
            'car_type' => 'passenger',
            'composition' => [
                ['service_id' => $this->service->id, 'quantity' => 4],
            ],
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
    }

    /** Варианты времени — только из часа выбранного слота (11:00–11:59). */
    public function test_time_options_come_from_selected_slot(): void
    {
        $component = Livewire::test(CreateBooking::class);
        $component->set('data.slot_id', $this->slot->id);

        $component->assertSee('11:59')->assertDontSee('12:00');
    }

    /** Выбор слота подставляет его начало — оператор правит время, только если клиент приедет позже. */
    public function test_selecting_slot_fills_hour_start(): void
    {
        $component = Livewire::test(CreateBooking::class);
        $component->set('data.slot_id', $this->slot->id);

        $this->assertSame('11:00', $component->get('data.start_time'));
    }

    /** Запись ровно на начало часа занимает час: слот закрыт и привязан к записи. */
    public function test_create_at_hour_start_closes_slot(): void
    {
        Livewire::test(CreateBooking::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::firstOrFail();
        $this->assertTrue($this->slot->fresh()->is_closed);
        $this->assertSame($booking->id, $this->slot->fresh()->booking_id);
    }

    /** Запись внутри часа (11:30) час не занимает — время 11:00 остаётся свободным. */
    public function test_create_inside_hour_keeps_slot_open(): void
    {
        Livewire::test(CreateBooking::class)
            ->fillForm([...$this->formData(), 'start_time' => '11:30'])
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::firstOrFail();
        $this->assertSame('11:30:00', $booking->start_time);
        $this->assertFalse($this->slot->fresh()->is_closed);
        $this->assertNull($this->slot->fresh()->booking_id);
    }

    public function test_admin_booking_does_not_reclose_occupied_slot(): void
    {
        // Закрытие — не барьер для записи оператора (ФТ-16): слот остаётся закрытым
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

    /**
     * Регрессия: сумма (Money) попадает в состояние формы и уезжает в снапшот Livewire,
     * а Wireable требует от полезной нагрузки массив — страница падала на первом рендере.
     */
    public function test_edit_page_renders(): void
    {
        $booking = Booking::factory()->create();

        Livewire::test(EditBooking::class, ['record' => $booking->id])
            ->assertOk()
            ->assertFormFieldExists('status');
    }

    public function test_edit_title_shows_time_name_and_phone(): void
    {
        $booking = $this->bookingWithItem();

        Livewire::test(EditBooking::class, ['record' => $booking->id])
            ->assertOk()
            ->assertSee('Запись 11:00, Иван, 79001234567');
    }

    /** Снимок строки не пересчитывается при открытии: правка прайса задним числом старые записи не меняет. */
    public function test_edit_prefills_saved_item_prices(): void
    {
        $booking = $this->bookingWithItem(priceKopecks: 99900);
        $component = Livewire::test(EditBooking::class, ['record' => $booking->id]);

        $this->assertSame(999.0, (float) $component->get($this->itemPath($component, 'price')));
    }

    /** Новая услуга в записи подтягивает готовую цену по комбинации (услуга × радиус × тип) записи. */
    public function test_selecting_service_fills_price_from_rule(): void
    {
        $booking = $this->bookingWithItem(radius: 13, priceKopecks: 5000, service: $this->extraService);
        $component = Livewire::test(EditBooking::class, ['record' => $booking->id]);

        $component->set($this->itemPath($component, 'service_id'), $this->service->id);

        $this->assertSame(150.0, (float) $component->get($this->itemPath($component, 'price')));
    }

    public function test_selecting_service_without_rules_fills_base_price(): void
    {
        $booking = $this->bookingWithItem(radius: 13);
        $component = Livewire::test(EditBooking::class, ['record' => $booking->id]);

        $component->set($this->itemPath($component, 'service_id'), $this->extraService->id);

        $this->assertSame(50.0, (float) $component->get($this->itemPath($component, 'price')));
    }

    /** Правила у услуги есть, комбинации нет: цена не подставляется молча, оператор видит предупреждение. */
    public function test_selecting_service_without_matching_rule_keeps_price_and_notifies(): void
    {
        $booking = $this->bookingWithItem(radius: 20, priceKopecks: 99900, service: $this->extraService);
        $component = Livewire::test(EditBooking::class, ['record' => $booking->id]);

        $component->set($this->itemPath($component, 'service_id'), $this->service->id);

        $this->assertSame(999.0, (float) $component->get($this->itemPath($component, 'price')));
        $component->assertNotified('Нет прайс-правила: услуга Снятие и установка колёс, R20, Легковая');
    }

    public function test_radius_change_reprices_services_with_rules(): void
    {
        $booking = $this->bookingWithItem(radius: 13);
        $component = Livewire::test(EditBooking::class, ['record' => $booking->id]);

        $component->set('data.radius', 16);

        $this->assertSame(180.0, (float) $component->get($this->itemPath($component, 'price')));
    }

    public function test_radius_change_keeps_prices_without_rules(): void
    {
        $booking = $this->bookingWithItem(radius: 13, priceKopecks: 5000, service: $this->extraService);
        $component = Livewire::test(EditBooking::class, ['record' => $booking->id]);

        $component->set('data.radius', 16);

        $this->assertSame(50.0, (float) $component->get($this->itemPath($component, 'price')));
    }

    public function test_car_type_change_reprices_services_with_rules(): void
    {
        $booking = $this->bookingWithItem(radius: 16);
        $component = Livewire::test(EditBooking::class, ['record' => $booking->id]);

        $component->set('data.car_type', CarType::Suv->value);

        $this->assertSame(200.0, (float) $component->get($this->itemPath($component, 'price')));
    }

    /** Зафиксированное поведение: цена услуги с правилами следует за радиусом, ручная правка не сохраняется. */
    public function test_radius_change_overwrites_manual_price(): void
    {
        $booking = $this->bookingWithItem(radius: 13);
        $component = Livewire::test(EditBooking::class, ['record' => $booking->id]);

        $component->set($this->itemPath($component, 'price'), '999');
        $component->set('data.radius', 16);

        $this->assertSame(180.0, (float) $component->get($this->itemPath($component, 'price')));
    }

    public function test_edit_shows_items_total(): void
    {
        $booking = $this->bookingWithItem(radius: 13); // 150 ₽ × 1
        BookingItem::create([
            'booking_id' => $booking->id,
            'service_id' => $this->extraService->id,
            'price' => 5000,
            'quantity' => 2,
        ]);

        Livewire::test(EditBooking::class, ['record' => $booking->id])
            ->assertSee('250 ₽');
    }

    public function test_edit_saves_items_and_total(): void
    {
        $booking = $this->bookingWithItem(radius: 13);
        $component = Livewire::test(EditBooking::class, ['record' => $booking->id]);

        $component->set('data.radius', 16); // цена правила R16/passenger — 180 ₽
        $component->set($this->itemPath($component, 'quantity'), 2);
        $component->call('save')->assertHasNoFormErrors();

        $booking = $booking->fresh();
        $this->assertSame(18000, $booking->items()->sole()->price->toKopecks());
        $this->assertSame(2, $booking->items()->sole()->quantity);
        $this->assertSame(36000, $booking->total_price->toKopecks());
    }

    /** Занятое время со страницы не сохраняется: оператор видит уведомление, запись не меняется. */
    public function test_edit_rejects_taken_time_with_notification(): void
    {
        $booking = $this->bookingWithItem();
        Booking::factory()->forSlot($this->slot)->create(['start_time' => '11:30:00']);

        $component = Livewire::test(EditBooking::class, ['record' => $booking->id]);
        $component->set('data.start_time', '11:30');
        $component->call('save');

        $component->assertNotified('На это время в слоте уже есть запись');
        $this->assertSame('11:00:00', $booking->fresh()->start_time);
    }

    /** Услугу деактивируют, а не удаляют: старая запись с такой услугой сохраняется без правок состава. */
    public function test_edit_saves_booking_with_deactivated_service(): void
    {
        $booking = $this->bookingWithItem(radius: 13);
        $this->service->update(['is_active' => false]);

        Livewire::test(EditBooking::class, ['record' => $booking->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(15000, $booking->fresh()->total_price->toKopecks());
    }

    /** Запись клиента «Иван» на слот из setUp с одной строкой состава (по умолчанию — услуга с правилами). */
    private function bookingWithItem(int $radius = 13, int $priceKopecks = 15000, ?BookingService $service = null): Booking
    {
        $service ??= $this->service;

        $booking = Booking::factory()->forSlot($this->slot)->create([
            'user_id' => User::factory()->bookingClient()->create(['name' => 'Иван', 'phone' => '79001234567'])->id,
            'radius' => $radius,
            'car_type' => CarType::Passenger,
        ]);

        BookingItem::create([
            'booking_id' => $booking->id,
            'service_id' => $service->id,
            'price' => $priceKopecks,
            'quantity' => 1,
        ]);

        return $booking;
    }

    /** Путь поля строки состава: ключ строки генерирует сам повторитель. */
    private function itemPath(Testable $component, string $field): string
    {
        $key = array_key_first($component->get('data.items'));

        return "data.items.{$key}.{$field}";
    }
}
