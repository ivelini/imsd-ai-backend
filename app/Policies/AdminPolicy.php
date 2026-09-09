<?php

namespace App\Policies;

use App\Models\Auth\Admin;

/** Управление администраторами и ролями — только суперадмин. */
class AdminPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->isSuperAdmin();
    }

    public function view(Admin $admin, Admin $target): bool
    {
        return $admin->isSuperAdmin();
    }

    public function create(Admin $admin): bool
    {
        return $admin->isSuperAdmin();
    }

    public function update(Admin $admin, Admin $target): bool
    {
        return $admin->isSuperAdmin();
    }

    public function delete(Admin $admin, Admin $target): bool
    {
        return $admin->isSuperAdmin() && $admin->id !== $target->id;
    }
}
