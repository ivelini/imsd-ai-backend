<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Booking\Booking;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Пользователь (клиент магазина и записи на шиномонтаж).
 *
 * @property int $id
 * @property string $name имя клиента
 * @property string|null $surname фамилия
 * @property string|null $patronymic отчество
 * @property string|null $email
 * @property string|null $phone телефон клиента записи (канон «7XXXXXXXXXX»)
 * @property Carbon|null $email_verified_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read string $full_name ФИО для показа: «Фамилия Имя Отчество», пустые части опускаются
 */
#[Fillable(['name', 'surname', 'patronymic', 'email', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * ФИО одной строкой для панели и ответа API: сайт фамилию и отчество не спрашивает,
     * поэтому пустые части опускаются.
     */
    public function getFullNameAttribute(): string
    {
        return collect([$this->surname, $this->name, $this->patronymic])
            ->filter()
            ->implode(' ');
    }
}
