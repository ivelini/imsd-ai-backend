<?php

namespace App\Models\Auth;

use App\Enums\Auth\AdminRoleCode;
use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Администратор панели.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int $admin_role_id
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read AdminRole|null $role
 */
class Admin extends Authenticatable implements FilamentUser
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'admin_role_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return BelongsTo<AdminRole, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'admin_role_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->code === AdminRoleCode::SuperAdmin->value;
    }

    /** Доступ в панель — только активным администраторам. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }
}
