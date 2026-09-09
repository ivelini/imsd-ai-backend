<?php

namespace App\Enums\Auth;

/** Коды ролей администраторов (admin_roles.code). */
enum AdminRoleCode: string
{
    case SuperAdmin = 'super-admin';
    case ContentManager = 'content-manager';
    case OrderManager = 'order-manager';
    case WarehouseManager = 'warehouse-manager';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Главный администратор',
            self::ContentManager => 'Контент-менеджер',
            self::OrderManager => 'Менеджер по заказам',
            self::WarehouseManager => 'Складской менеджер',
        };
    }
}
