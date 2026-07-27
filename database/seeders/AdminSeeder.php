<?php

namespace Database\Seeders;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use Illuminate\Database\Seeder;

/** Роли администраторов и учётная запись главного администратора. */
class AdminSeeder extends Seeder
{
    /** @var array<string, string> Код роли → отображаемое имя. */
    private const ROLES = [
        'super-admin' => 'Главный администратор',
        'content-manager' => 'Контент-менеджер',
        'order-manager' => 'Менеджер по заказам',
        'warehouse-manager' => 'Складской менеджер',
    ];

    public function run(): void
    {
        $superAdminId = null;

        foreach (self::ROLES as $code => $name) {
            $role = AdminRole::firstOrCreate(
                ['code' => $code],
                ['name' => $name],
            );

            if ($code === 'super-admin') {
                $superAdminId = $role->id;
            }
        }

        Admin::firstOrCreate(
            ['email' => 'admin@aalyans.ru'],
            [
                'name' => self::ROLES['super-admin'],
                'password' => bcrypt(env('ADMIN_SEED_PASSWORD', 'password')),
                'admin_role_id' => $superAdminId,
                'is_active' => true,
            ],
        );
    }
}
