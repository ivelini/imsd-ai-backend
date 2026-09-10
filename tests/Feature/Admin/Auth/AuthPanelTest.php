<?php

namespace Tests\Feature\Admin\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Доступ к Filament-панели: session-guard admin, canAccessPanel по is_active. */
class AuthPanelTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

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
}
