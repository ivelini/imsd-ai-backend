<?php

namespace Tests\Feature\Admin\Auth;

use App\Enums\Auth\AdminRoleCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Политика доступа к ресурсам панели по коду роли (admin_roles.code). */
class AdminRolePolicyTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    public function test_role_without_access_forbidden(): void
    {
        $admin = $this->createAdmin(AdminRoleCode::ContentManager);

        $this->actingAs($admin, 'admin')
            ->get('/panel/admins')
            ->assertForbidden();
    }

    public function test_super_admin_has_full_access(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->get('/panel/admins')
            ->assertOk();
    }
}
