<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Роль администратора. */
class AdminRole extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }
}
