<?php

namespace Tests\Feature\Admin;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

/** HTTP-слой уведомлений админ-панели. */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);
        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@test.ru',
            'password' => bcrypt('password'), 'admin_role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_index_returns_only_own_notifications(): void
    {
        $this->createNotification($this->admin);

        $role = AdminRole::create(['name' => 'Менеджер', 'code' => 'manager']);
        $other = Admin::create([
            'name' => 'Other', 'email' => 'other@test.ru',
            'password' => bcrypt('password'), 'admin_role_id' => $role->id, 'is_active' => true,
        ]);
        $this->createNotification($other);
        $this->createNotification($other);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/notifications')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.unread_count', 1);
    }

    public function test_index_filters_unread_only(): void
    {
        $this->createNotification($this->admin, read: true);
        $this->createNotification($this->admin);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/notifications?unread_only=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_read_marks_notification_as_read(): void
    {
        $notification = $this->createNotification($this->admin);

        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/admin/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('message', 'OK');

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_read_all_marks_all_as_read(): void
    {
        $this->createNotification($this->admin);
        $this->createNotification($this->admin);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, $this->admin->unreadNotifications()->count());
    }

    private function createNotification(Admin $admin, bool $read = false): DatabaseNotification
    {
        $notification = $admin->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => 'App\Notifications\TestNotification',
            'data' => ['message' => 'test'],
        ]);

        if ($read) {
            $notification->markAsRead();
        }

        return $notification;
    }
}
