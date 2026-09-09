<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Доступ к Filament-панели: session-guard admin, canAccessPanel по is_active. */
class AuthPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_to_login(): void
    {
        $this->get('/panel')->assertRedirect('/panel/login');
    }

    public function test_active_admin_can_access_panel(): void
    {
        $admin = $this->createAdmin(isActive: true);

        $this->actingAs($admin, 'admin')
            ->get('/panel')
            ->assertOk();
    }

    public function test_inactive_admin_cannot_access_panel(): void
    {
        $admin = $this->createAdmin(isActive: false);

        $this->actingAs($admin, 'admin')
            ->get('/panel')
            ->assertForbidden();
    }

    private function createAdmin(bool $isActive): Admin
    {
        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);

        return Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.ru',
            'password' => bcrypt('password'),
            'admin_role_id' => $role->id,
            'is_active' => $isActive,
        ]);
    }
}
