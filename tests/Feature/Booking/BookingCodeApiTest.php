<?php

namespace Tests\Feature\Booking;

use App\Jobs\Booking\SendBookingCodeSms;
use App\Models\Booking\BookingCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingCodeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-09-09 10:00:00');
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_issue_creates_code_row_and_dispatches_sms(): void
    {
        Queue::fake();

        $this->postJson('/api/booking/code', ['phone' => '+7 (900) 123-45-67'])
            ->assertOk()
            ->assertJson(['data' => ['retry_after' => 60]]);

        $row = BookingCode::query()->firstOrFail();
        $this->assertSame('79001234567', $row->phone); // канон
        $this->assertSame(hash('sha256', (string) config('sms.stub.code').config('app.key')), $row->code_hash);

        Queue::assertPushed(SendBookingCodeSms::class, fn ($job) => $job->phone === '79001234567');
    }

    public function test_issue_within_cooldown_returns_429(): void
    {
        Queue::fake();

        $this->postJson('/api/booking/code', ['phone' => '79001234567'])->assertOk();

        $this->travelTo('2026-09-09 10:00:30'); // +30 с < кулдауна 60 с

        $this->postJson('/api/booking/code', ['phone' => '79001234567'])
            ->assertStatus(429);

        Queue::assertPushed(SendBookingCodeSms::class, 1);
    }

    public function test_issue_rejects_invalid_phone(): void
    {
        $this->postJson('/api/booking/code', ['phone' => 'abc'])
            ->assertUnprocessable();

        $this->assertSame(0, BookingCode::count());
    }
}
