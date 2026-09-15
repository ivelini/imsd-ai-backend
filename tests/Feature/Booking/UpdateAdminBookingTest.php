<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\UpdateAdminBooking;
use App\DTOs\Booking\BookingItemInput;
use App\DTOs\Booking\UpdateAdminBookingInput;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\Models\Booking\BookingService;
use App\Models\Booking\PriceRule;
use App\Models\Booking\Slot;
use App\ValueObjects\Money;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Правка записи оператором (ФТ-19): состав синхронизируется по услугам, итог
 * пересчитывается из сохранённых строк, цена из формы не пересчитывается прайсом,
 * время внутри часа держит час закрытым и не даёт занять занятое время.
 */
class UpdateAdminBookingTest extends TestCase
{
    use RefreshDatabase;

    private Slot $slot;

    private Booking $booking;

    private BookingService $tireService;

    private BookingService $extraService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tireService = BookingService::create([
            'name' => 'Снятие и установка колёс',
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

        // Услуга без прайс-правил: её цена от радиуса и типа не зависит
        $this->extraService = BookingService::create([
            'name' => 'Доплата за низкий профиль',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 5000,
        ]);

        $this->slot = Slot::create(['date' => now()->addDay()->toDateString(), 'hour' => 14]);

        $this->booking = Booking::factory()->forSlot($this->slot)->create([
            'radius' => 16,
            'car_type' => CarType::Passenger,
            'start_time' => '14:30:00',
        ]);
    }

    public function test_recalculates_total_from_items_with_quantity(): void
    {
        $this->save([
            $this->item($this->tireService->id, quantity: 4, priceKopecks: 15000),
            $this->item($this->extraService->id, quantity: 1, priceKopecks: 5000),
        ]);

        // 15000 × 4 + 5000 × 1: суммирование цен за единицу без количества дало бы 20000
        $this->assertSame(65000, $this->booking->fresh()->total_price->toKopecks());
    }

    public function test_syncs_items_by_service(): void
    {
        $existing = $this->addItem($this->tireService, quantity: 4, priceKopecks: 15000);

        $this->save([
            $this->item($this->tireService->id, quantity: 2, priceKopecks: 9000),
            $this->item($this->extraService->id, quantity: 1, priceKopecks: 5000),
        ]);

        $this->assertSame(2, $this->booking->items()->count());
        $this->assertSame(2, $existing->fresh()->quantity);
        $this->assertSame(9000, $existing->fresh()->price->toKopecks());
        $this->assertSame(23000, $this->booking->fresh()->total_price->toKopecks());
    }

    public function test_keeps_item_ids_on_resave(): void
    {
        $existing = $this->addItem($this->tireService, quantity: 4, priceKopecks: 15000);

        $this->save([$this->item($this->tireService->id, quantity: 4, priceKopecks: 15000)]);

        $this->assertSame($existing->id, $this->booking->items()->sole()->id);
    }

    public function test_saves_price_from_form_as_is(): void
    {
        $this->save([$this->item($this->tireService->id, quantity: 1, priceKopecks: 999)]);

        $this->assertSame(999, $this->booking->items()->sole()->price->toKopecks());
        $this->assertSame(999, $this->booking->fresh()->total_price->toKopecks());
    }

    public function test_updates_booking_snapshot_fields(): void
    {
        $this->save(
            [$this->item($this->tireService->id, quantity: 1, priceKopecks: 15000)],
            [
                'plate' => 'В 001 ВВ 174',
                'radius' => 18,
                'carType' => CarType::Suv,
                'status' => BookingStatus::Done,
                'cancelReason' => null,
            ],
        );

        $booking = $this->booking->fresh();
        $this->assertSame('В 001 ВВ 174', $booking->plate);
        $this->assertSame(18, $booking->radius);
        $this->assertSame(CarType::Suv, $booking->car_type);
        $this->assertSame(BookingStatus::Done, $booking->status);
        $this->assertNull($booking->cancel_reason);
    }

    /** Запись ровно на начало часа занимает час: слот закрыт и привязан к записи. */
    public function test_closes_slot_when_time_is_hour_start(): void
    {
        $this->save([$this->item($this->tireService->id, quantity: 1, priceKopecks: 15000)], ['startTime' => '14:00:00']);

        $slot = $this->slot->fresh();
        $this->assertTrue($slot->is_closed);
        $this->assertSame($this->booking->id, $slot->booking_id);
    }

    /** Запись внутри часа (14:30) час не занимает: время 14:00 остаётся свободным. */
    public function test_keeps_slot_open_when_time_inside_hour(): void
    {
        $this->save([$this->item($this->tireService->id, quantity: 1, priceKopecks: 15000)], ['startTime' => '14:30:00']);

        $slot = $this->slot->fresh();
        $this->assertFalse($slot->is_closed);
        $this->assertNull($slot->booking_id);
    }

    /** Перенос записи внутрь часа освобождает час, который она занимала. */
    public function test_releases_slot_when_time_moves_inside_hour(): void
    {
        $this->booking->update(['start_time' => '14:00:00']);
        $this->slot->update(['is_closed' => true, 'booking_id' => $this->booking->id]);

        $this->save([$this->item($this->tireService->id, quantity: 1, priceKopecks: 15000)], ['startTime' => '14:30:00']);

        $slot = $this->slot->fresh();
        $this->assertFalse($slot->is_closed);
        $this->assertNull($slot->booking_id);
    }

    /** Час, закрытый вручную оператором, правка времени не открывает. */
    public function test_keeps_manual_closing_when_time_moves(): void
    {
        $this->booking->update(['start_time' => '14:00:00']);
        $this->slot->update(['is_closed' => true, 'close_reason' => 'Инвентаризация', 'booking_id' => null]);

        $this->save([$this->item($this->tireService->id, quantity: 1, priceKopecks: 15000)], ['startTime' => '14:30:00']);

        $slot = $this->slot->fresh();
        $this->assertTrue($slot->is_closed);
        $this->assertSame('Инвентаризация', $slot->close_reason);
    }

    /** Две записи на одно время в слоте невозможны: сохранение второй отклоняется, первая не меняется. */
    public function test_rejects_time_taken_by_another_booking(): void
    {
        $this->booking->update(['start_time' => '14:00:00']);
        Booking::factory()->forSlot($this->slot)->create(['start_time' => '14:30:00']);

        try {
            $this->save([$this->item($this->tireService->id, quantity: 1, priceKopecks: 15000)], ['startTime' => '14:30:00']);
            $this->fail('Сохранение на занятое время должно падать');
        } catch (DomainException $exception) {
            $this->assertSame(409, $exception->getCode());
        }

        $this->assertSame('14:00:00', $this->booking->fresh()->start_time);
    }

    /** Отменённая запись время не держит — на её время можно поставить новую. */
    public function test_allows_time_of_cancelled_booking(): void
    {
        Booking::factory()->forSlot($this->slot)->cancelled()->create(['start_time' => '14:30:00']);

        $this->save([$this->item($this->tireService->id, quantity: 1, priceKopecks: 15000)], ['startTime' => '14:30:00']);

        $this->assertSame('14:30:00', $this->booking->fresh()->start_time);
    }

    /** Сохранение без смены времени не упирается в саму запись. */
    public function test_allows_own_time_on_resave(): void
    {
        $this->save([$this->item($this->tireService->id, quantity: 1, priceKopecks: 15000)], ['startTime' => '14:30:00']);

        $this->assertSame('14:30:00', $this->booking->fresh()->start_time);
    }

    /**
     * @param  list<BookingItemInput>  $items
     * @param  array<string, mixed>  $overrides
     */
    private function save(array $items, array $overrides = []): void
    {
        app(UpdateAdminBooking::class)->execute(new UpdateAdminBookingInput(
            booking: $this->booking,
            surname: 'Петров',
            name: 'Иван',
            patronymic: null,
            plate: $overrides['plate'] ?? null,
            radius: $overrides['radius'] ?? 16,
            carType: $overrides['carType'] ?? CarType::Passenger,
            status: $overrides['status'] ?? BookingStatus::Confirmed,
            cancelReason: $overrides['cancelReason'] ?? null,
            startTime: $overrides['startTime'] ?? $this->booking->start_time,
            items: $items,
        ));
    }

    private function item(int $serviceId, int $quantity, int $priceKopecks): BookingItemInput
    {
        return new BookingItemInput(
            serviceId: $serviceId,
            quantity: $quantity,
            price: Money::fromKopecks($priceKopecks),
        );
    }

    private function addItem(BookingService $service, int $quantity, int $priceKopecks): BookingItem
    {
        return BookingItem::create([
            'booking_id' => $this->booking->id,
            'service_id' => $service->id,
            'price' => $priceKopecks,
            'quantity' => $quantity,
        ]);
    }
}
