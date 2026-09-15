<?php

namespace Tests\Feature\Admin\Booking;

use App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Pages\EditScheduleTemplate;
use App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Pages\ListScheduleTemplates;
use App\Models\Booking\ScheduleTemplate;
use App\Models\Booking\Slot;
use Database\Seeders\BookingScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** ScheduleTemplateResource панели: часы работы дня недели и ручная генерация сетки. */
class ScheduleTemplateResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');
    }

    public function test_edit_weekday_hours(): void
    {
        $template = ScheduleTemplate::create([
            'weekday' => 1,
            'open_time' => '09:00:00',
            'close_time' => '19:00:00',
        ]);

        Livewire::test(EditScheduleTemplate::class, ['record' => $template->id])
            ->fillForm(['open_time' => '10:00', 'close_time' => '18:00'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('booking_schedule_templates', [
            'id' => $template->id,
            'open_time' => '10:00',
            'close_time' => '18:00',
        ]);
    }

    /** Кнопка на расписании — ручной запуск того же планировщика сетки. */
    public function test_generate_grid_action_creates_slots(): void
    {
        $this->seed(BookingScheduleSeeder::class);

        Livewire::test(ListScheduleTemplates::class)
            ->callAction('generateGrid');

        // Пн–Сб × 10 часов, горизонт по умолчанию 30 дней — сетка не пустая и открытая
        $this->assertGreaterThan(0, Slot::count());
        $this->assertSame(0, Slot::where('is_closed', true)->count());
    }
}
