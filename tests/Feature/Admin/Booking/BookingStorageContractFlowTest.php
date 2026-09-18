<?php

namespace Tests\Feature\Admin\Booking;

use App\Filament\Clusters\Booking\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\EditBooking;
use App\Models\Auth\Admin;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\Models\Booking\BookingService;
use App\Models\Booking\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/**
 * Кнопка «Создать договор хранения» в форме записи: появляется на услуге категории «Хранение»,
 * на создании сначала сохраняет запись, затем уводит на форму договора с данными записи.
 */
class BookingStorageContractFlowTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    private BookingService $storageService;

    private BookingService $tireService;

    private Slot $slot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
        $this->actingAs($this->admin, 'admin');

        $this->storageService = BookingService::create([
            'name' => 'Сезонное хранение шин',
            'category' => 'storage',
            'is_active' => true,
            'base_price' => 300000,
        ]);
        $this->tireService = BookingService::create([
            'name' => 'Снятие и установка колёс',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => 15000,
        ]);

        $this->slot = Slot::create(['date' => now()->addDay()->toDateString(), 'hour' => 11]);
    }

    /** @return array<string, mixed> */
    private function storageRow(int $quantity = 1, string $price = '3000'): array
    {
        return ['service_id' => $this->storageService->id, 'quantity' => $quantity, 'price' => $price];
    }

    /** @return array<string, mixed> */
    private function tireRow(): array
    {
        return ['service_id' => $this->tireService->id, 'quantity' => 4, 'price' => '150'];
    }

    /** @param  list<array<string, mixed>>  $items
     * @return array<string, mixed> */
    private function formData(array $items): array
    {
        return [
            'phone' => '79001234567',
            'surname' => 'Петров',
            'name' => 'Иван',
            'start_time' => '11:00',
            'radius' => 13,
            'car_type' => 'passenger',
            'items' => $items,
        ];
    }

    private function createPage(): Testable
    {
        return Livewire::withQueryParams(['slot_id' => $this->slot->id])->test(CreateBooking::class);
    }

    /** @return array<string, string> параметры адреса, на который ушёл оператор */
    private function redirectQuery(Testable $component): array
    {
        $url = $component->effects['redirect'] ?? null;
        $this->assertNotNull($url, 'Оператор не ушёл на форму договора хранения');

        parse_str((string) parse_url((string) $url, PHP_URL_QUERY), $query);

        return $query;
    }

    /** Кнопку показывает категория услуги: название правится в справочнике, категория — нет */
    public function test_action_visible_for_storage_service(): void
    {
        $this->createPage()
            ->fillForm($this->formData([$this->storageRow()]))
            ->assertActionVisible('createStorageContract')
            ->assertSee('Создать договор хранения'); // кнопка отрисована, а не только объявлена
    }

    public function test_action_hidden_without_storage_service(): void
    {
        $this->createPage()
            ->fillForm($this->formData([$this->tireRow()]))
            ->assertActionHidden('createStorageContract')
            ->assertDontSee('Создать договор хранения');
    }

    /** Запись ещё не сохранена — кнопка создаёт её и уводит на договор с клиентом записи */
    public function test_action_saves_booking_and_redirects_to_contract(): void
    {
        $component = $this->createPage()
            ->fillForm($this->formData([$this->storageRow()]))
            ->call('createStorageContract');

        $booking = Booking::sole();
        $this->assertSame(1, Booking::count());
        $this->assertSame((string) $booking->user_id, $this->redirectQuery($component)['user_id']);
    }

    /** Невалидная форма: записи нет, оператор остаётся на форме с ошибками */
    public function test_action_does_not_save_invalid_booking(): void
    {
        $component = $this->createPage()
            ->fillForm([...$this->formData([$this->storageRow()]), 'phone' => null, 'name' => null])
            ->call('createStorageContract')
            ->assertHasFormErrors(['phone', 'name']);

        $this->assertSame(0, Booking::count());
        $this->assertArrayNotHasKey('redirect', $component->effects);
    }

    /** Стоимость договора — итог строки хранения (цена × количество), а не цена за единицу */
    public function test_redirect_carries_slot_date_and_line_total(): void
    {
        $component = $this->createPage()
            ->fillForm($this->formData([$this->storageRow(quantity: 2, price: '3000')]))
            ->call('createStorageContract');

        $query = $this->redirectQuery($component);

        $this->assertSame($this->slot->date->format('Y-m-d'), $query['starts_on']);
        $this->assertSame('6000', $query['price']);
    }

    /** Правка: запись уже есть — кнопка только уводит на договор с её клиентом */
    public function test_edit_redirects_without_creating_booking(): void
    {
        $booking = Booking::factory()->forSlot($this->slot)->create();
        BookingItem::create([
            'booking_id' => $booking->id,
            'service_id' => $this->storageService->id,
            'price' => 300000,
            'quantity' => 1,
        ]);

        $component = Livewire::test(EditBooking::class, ['record' => $booking->id])
            ->call('createStorageContract');

        $this->assertSame(1, Booking::count());
        $this->assertSame((string) $booking->user_id, $this->redirectQuery($component)['user_id']);
    }
}
