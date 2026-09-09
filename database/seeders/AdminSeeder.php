<?php

namespace Database\Seeders;

use App\Enums\Auth\AdminRoleCode;
use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use Illuminate\Database\Seeder;

/** Роли администраторов и учётная запись главного администратора. */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminId = null;

        foreach (AdminRoleCode::cases() as $roleCode) {
            $role = AdminRole::firstOrCreate(
                ['code' => $roleCode->value],
                ['name' => $roleCode->label()],
            );

            if ($roleCode === AdminRoleCode::SuperAdmin) {
                $superAdminId = $role->id;
            }
        }

        Admin::firstOrCreate(
            ['email' => 'admin@aalyans.ru'],
            [
                'name' => AdminRoleCode::SuperAdmin->label(),
                'password' => bcrypt(env('ADMIN_SEED_PASSWORD', 'password')),
                'admin_role_id' => $superAdminId,
                'is_active' => true,
            ],
        );
    }
}
