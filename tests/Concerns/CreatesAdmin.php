<?php

namespace Tests\Concerns;

use App\Enums\Auth\AdminRoleCode;
use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;

/** Админ с ролью для тестов: по умолчанию суперадмин с доступом в панель. */
trait CreatesAdmin
{
    protected function createAdmin(
        AdminRoleCode $roleCode = AdminRoleCode::SuperAdmin,
        bool $isActive = true,
        string $email = 'admin@test.ru',
    ): Admin {
        $role = AdminRole::firstOrCreate(
            ['code' => $roleCode->value],
            ['name' => $roleCode->label()],
        );

        return Admin::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => bcrypt('password'),
            'admin_role_id' => $role->id,
            'is_active' => $isActive,
        ]);
    }
}
