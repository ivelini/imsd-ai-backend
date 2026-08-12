<?php

namespace App\Models\Auth;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Роль администратора.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
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
