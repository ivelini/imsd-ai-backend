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
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Правка записи оператором (ФТ-19): состав синхронизируется по услугам, итог
 * пересчитывается из сохранённых строк, цена из формы не пересчитывается прайсом.
 */
class UpdateAdminBookingTest extends TestCase
{
    use RefreshDatabase;

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

        $this->booking = Booking::factory()->create([
            'radius' => 16,
            'car_type' => CarType::Passenger,
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
                'status' => BookingStatus::Arrived,
                'cancelReason' => null,
            ],
        );

        $booking = $this->booking->fresh();
        $this->assertSame('В 001 ВВ 174', $booking->plate);
        $this->assertSame(18, $booking->radius);
        $this->assertSame(CarType::Suv, $booking->car_type);
        $this->assertSame(BookingStatus::Arrived, $booking->status);
        $this->assertNull($booking->cancel_reason);
    }

    /**
     * @param  list<BookingItemInput>  $items
     * @param  array<string, mixed>  $overrides
     */
    private function save(array $items, array $overrides = []): void
    {
        app(UpdateAdminBooking::class)->execute(new UpdateAdminBookingInput(
            booking: $this->booking,
            plate: $overrides['plate'] ?? null,
            radius: $overrides['radius'] ?? 16,
            carType: $overrides['carType'] ?? CarType::Passenger,
            status: $overrides['status'] ?? BookingStatus::Confirmed,
            cancelReason: $overrides['cancelReason'] ?? null,
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
