<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Политика доступа к ресурсам панели по коду роли (admin_roles.code). */
class AdminRolePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_without_access_forbidden(): void
    {
        $admin = $this->createAdmin('manager');

        $this->actingAs($admin, 'admin')
            ->get('/panel/admins')
            ->assertForbidden();
    }

    public function test_super_admin_has_full_access(): void
    {
        $admin = $this->createAdmin('super-admin');

        $this->actingAs($admin, 'admin')
            ->get('/panel/admins')
            ->assertOk();
    }

    private function createAdmin(string $roleCode): Admin
    {
        $role = AdminRole::create(['name' => 'Менеджер', 'code' => $roleCode]);

        return Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.ru',
            'password' => bcrypt('password'),
            'admin_role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
