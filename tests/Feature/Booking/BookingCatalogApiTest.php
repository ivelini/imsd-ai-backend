<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\BookingService;
use Database\Seeders\BookingCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_active_services_and_complexes(): void
    {
        $this->seed(BookingCatalogSeeder::class);

        $response = $this->getJson('/api/booking/catalog')->assertOk();

        $response->assertJsonCount(10, 'data.services');

        $mounting = collect($response->json('data.services'))->firstWhere('name', 'Снятие и установка колёс');
        $this->assertSame(['id', 'name', 'base_price', 'has_rules'], array_keys($mounting));
        $this->assertTrue($mounting['has_rules']);

        $valve = collect($response->json('data.services'))->firstWhere('name', 'Замена вентиля');
        $this->assertFalse($valve['has_rules']);

        $complex = $response->json('data.complexes.0');
        $this->assertSame('Сезонный шиномонтаж', $complex['name']);
        $this->assertCount(4, $complex['service_ids']);
    }

    public function test_catalog_hides_inactive_services(): void
    {
        $this->seed(BookingCatalogSeeder::class);

        BookingService::where('name', 'Утилизация шины')->update(['is_active' => false]);

        $names = collect($this->getJson('/api/booking/catalog')->json('data.services'))->pluck('name');

        $this->assertCount(9, $names);
        $this->assertNotContains('Утилизация шины', $names);
    }
}
