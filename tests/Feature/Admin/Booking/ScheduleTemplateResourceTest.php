<?php

namespace Tests\Feature\Admin\Booking;

use App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Pages\EditScheduleTemplate;
use App\Models\Booking\ScheduleTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** ScheduleTemplateResource панели: часы работы дня недели. */
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
}
