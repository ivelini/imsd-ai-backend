<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\NotificationResource\Pages\ListNotifications;
use App\Models\Auth\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** NotificationResource панели: список уведомлений админа и действие markAsRead. */
class NotificationResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
    }

    public function test_mark_as_read_updates_read_at(): void
    {
        $notification = $this->createNotification();

        $this->actingAs($this->admin, 'admin');

        Livewire::test(ListNotifications::class)
            ->callTableAction('markAsRead', $notification)
            ->assertHasNoErrors();

        $this->assertNotNull($notification->refresh()->read_at);
        $this->assertSame(0, $this->admin->unreadNotifications()->count());
    }

    private function createNotification(): DatabaseNotification
    {
        return $this->admin->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'test'],
        ]);
    }
}
