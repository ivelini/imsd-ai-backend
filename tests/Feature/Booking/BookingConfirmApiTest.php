<?php

namespace Tests\Feature\Booking;

use App\Enums\Booking\CarType;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingService;
use App\Models\Booking\PriceRule;
use App\Models\Booking\Slot;
use App\Models\System\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingConfirmApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::create(['key' => 'min_lead_time_h', 'value' => '1']);
        Setting::create(['key' => 'booking_horizon_days', 'value' => '30']);
        Setting::create(['key' => 'reservation_timeout_min', 'value' => '15']);
        $this->travelTo('2026-09-09 10:00:00');
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function serviceWithRule(): BookingService
    {
        $service = BookingService::create([
            'name' => 'Снятие и установка колёс',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 15000,
        ]);
        PriceRule::create([
            'service_id' => $service->id,
            'radius' => 13,
            'car_type' => CarType::Passenger,
            'price' => 15000,
        ]);

        return $service;
    }

    /** @return array<string, mixed> */
    private function confirmParams(BookingService $service): array
    {
        return [
            'phone' => '79001234567',
            'code' => (string) config('sms.stub.code'),
            'name' => 'Иван',
            'plate' => 'А 000 АА 174',
            'date' => '2026-09-10',
            'hour' => 11,
            'radius' => 13,
            'car_type' => 'passenger',
            'service_ids' => [$service->id],
            'quantities' => [$service->id => 4],
        ];
    }

    private function issueCode(): void
    {
        $this->postJson('/api/booking/code', ['phone' => '79001234567'])->assertOk();
    }

    public function test_confirm_full_cycle_creates_booking(): void
    {
        $service = $this->serviceWithRule();
        Slot::create(['date' => '2026-09-10', 'hour' => 11]);
        $this->issueCode();

        $response = $this->postJson('/api/booking/confirm', $this->confirmParams($service))
            ->assertCreated();

        $response->assertJson([
            'data' => [
                'status' => 'confirmed',
                'source' => 'site',
                'total_price' => 60000, // 15000 × 4
                'radius' => 13,
                'car_type' => 'passenger',
                'plate' => 'А 000 АА 174',
                'user' => ['phone' => '79001234567'],
            ],
        ]);
        $response->assertJsonCount(1, 'data.items');
        $response->assertJsonPath('data.items.0.price', 15000);
        $response->assertJsonPath('data.items.0.quantity', 4);
        $response->assertJsonPath('data.items.0.service.name', 'Снятие и установка колёс');

        // слот закрыт и привязан к записи, код одноразово использован
        $booking = Booking::firstOrFail();
        $this->assertTrue($booking->slot->is_closed);
        $this->assertSame($booking->id, $booking->slot->booking_id);
        $this->assertNotNull($booking->bookingCode->used_at);
    }

    public function test_confirm_rejects_invalid_code(): void
    {
        $service = $this->serviceWithRule();
        Slot::create(['date' => '2026-09-10', 'hour' => 11]);

        $this->postJson('/api/booking/confirm', [...$this->confirmParams($service), 'code' => '0000'])
            ->assertUnprocessable();

        $this->assertSame(0, Booking::count());
    }

    public function test_confirm_rejects_expired_code(): void
    {
        $service = $this->serviceWithRule();
        Slot::create(['date' => '2026-09-10', 'hour' => 11]);
        $this->issueCode();

        $this->travelTo('2026-09-09 10:16:00'); // +16 мин > TTL 15

        $this->postJson('/api/booking/confirm', $this->confirmParams($service))
            ->assertStatus(409);

        $this->assertSame(0, Booking::count());
    }

    public function test_used_code_resubmit_returns_existing_booking(): void
    {
        $service = $this->serviceWithRule();
        Slot::create(['date' => '2026-09-10', 'hour' => 11]);
        $this->issueCode();

        $created = $this->postJson('/api/booking/confirm', $this->confirmParams($service))->assertCreated();

        $this->postJson('/api/booking/confirm', $this->confirmParams($service))
            ->assertOk()
            ->assertJsonPath('data.id', $created->json('data.id'));

        $this->assertSame(1, Booking::count());
    }

    public function test_confirm_rejects_closed_slot(): void
    {
        $service = $this->serviceWithRule();
        Slot::create(['date' => '2026-09-10', 'hour' => 11, 'is_closed' => true]);
        $this->issueCode();

        $this->postJson('/api/booking/confirm', $this->confirmParams($service))
            ->assertStatus(409);

        $this->assertSame(0, Booking::count());
    }

    public function test_confirm_validates_format(): void
    {
        $service = $this->serviceWithRule();
        Slot::create(['date' => '2026-09-10', 'hour' => 11]);

        $this->postJson('/api/booking/confirm', [...$this->confirmParams($service), 'date' => 'мусор'])
            ->assertUnprocessable();

        $this->assertSame(0, Booking::count());
    }
}
