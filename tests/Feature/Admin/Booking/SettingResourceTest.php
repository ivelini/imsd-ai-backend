<?php

namespace Tests\Feature\Admin\Booking;

use App\Filament\Clusters\Booking\Resources\Settings\Pages\EditSetting;
use App\Models\System\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** SettingResource панели: значения параметров записи. */
class SettingResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');
    }

    public function test_edit_setting_value(): void
    {
        Setting::create(['key' => 'reservation_timeout_min', 'value' => '15']);

        Livewire::test(EditSetting::class, ['record' => 'reservation_timeout_min'])
            ->fillForm(['value' => '30'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('settings', [
            'key' => 'reservation_timeout_min',
            'value' => '30',
        ]);
    }
}
