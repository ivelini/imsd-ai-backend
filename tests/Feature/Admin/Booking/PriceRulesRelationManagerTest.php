<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Booking\CarType;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\EditBookingService;
use App\Filament\Clusters\Booking\Resources\BookingServices\RelationManagers\PriceRulesRelationManager;
use App\Models\Booking\BookingService;
use App\Models\Booking\PriceRule;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Прайс-правила в карточке услуги: список услуги-владельца, добавление, правка цены, удаление, уникальность куба. */
class PriceRulesRelationManagerTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->service = $this->createService('Снятие и установка колёс');
    }

    public function test_edit_page_renders_rules_relation_manager(): void
    {
        // Стража привязки: без getRelations() на ресурсе карточка услуги осталась бы без таблицы правил
        Livewire::test(EditBookingService::class, ['record' => $this->service->id])
            ->assertSeeLivewire(PriceRulesRelationManager::class);
    }

    public function test_lists_only_owner_service_rules(): void
    {
        $own16 = $this->createRule($this->service, radius: 16, price: 15000);
        $own17 = $this->createRule($this->service, radius: 17, price: 20000, carType: CarType::Suv);
        $stranger = $this->createRule($this->createService('Балансировка'), radius: 16, price: 10000);

        $this->relationManager()
            ->assertCanSeeTableRecords([$own16, $own17])
            ->assertCanNotSeeTableRecords([$stranger]);
    }

    public function test_create_rule_binds_to_owner_service(): void
    {
        $this->relationManager()
            ->callTableAction('create', data: [
                'radius' => 13,
                'car_type' => CarType::Passenger->value,
                'price' => '150.00',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('booking_price_rules', [
            'service_id' => $this->service->id,
            'radius' => 13,
            'car_type' => CarType::Passenger,
            'price' => 15000, // рубли формы → копейки БД
        ]);
    }

    public function test_create_rejects_duplicate_combination(): void
    {
        $this->createRule($this->service, radius: 13, price: 15000);

        $this->relationManager()
            ->callTableAction('create', data: [
                'radius' => 13,
                'car_type' => CarType::Passenger->value,
                'price' => '160.00',
            ])
            ->assertHasTableActionErrors();

        $this->assertSame(1, PriceRule::where('service_id', $this->service->id)->count());
    }

    public function test_create_allows_same_combination_for_other_service(): void
    {
        // Та же комбинация у другой услуги — не повод для ошибки: уникальность куба считается в пределах услуги
        $this->createRule($this->createService('Балансировка'), radius: 13, price: 10000);

        $this->relationManager()
            ->callTableAction('create', data: [
                'radius' => 13,
                'car_type' => CarType::Passenger->value,
                'price' => '150.00',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(1, PriceRule::where('service_id', $this->service->id)->count());
    }

    public function test_edit_rule_updates_price(): void
    {
        $rule = $this->createRule($this->service, radius: 16, price: 15000);

        $this->relationManager()
            ->callTableAction('edit', $rule, data: ['price' => '180.50'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(18050, $rule->fresh()->price->toKopecks());
    }

    public function test_delete_rule_removes_record(): void
    {
        $rule = $this->createRule($this->service, radius: 16, price: 15000);

        $this->relationManager()->callTableAction('delete', $rule);

        $this->assertDatabaseMissing('booking_price_rules', ['id' => $rule->id]);
    }

    private function relationManager(): Testable
    {
        return Livewire::test(PriceRulesRelationManager::class, [
            'ownerRecord' => $this->service,
            'pageClass' => EditBookingService::class,
        ]);
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

    private function createRule(BookingService $service, int $radius, int $price, CarType $carType = CarType::Passenger): PriceRule
    {
        return PriceRule::create([
            'service_id' => $service->id,
            'radius' => $radius,
            'car_type' => $carType,
            'price' => $price,
        ]);
    }
}
