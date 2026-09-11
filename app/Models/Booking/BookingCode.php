<?php

namespace App\Models\Booking;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Код подтверждения SMS: только верификатор и одноразовость.
 * Заявка до подтверждения не хранится — при вводе кода клиент передаёт актуальный выбор,
 * запись строится из него (одна SMS на цикл, изменение выбора не перевыпускает код).
 * TTL без поля: просрочка = created_at + reservation_timeout_min.
 *
 * @property int $id
 * @property string $phone
 * @property string $code_hash
 * @property Carbon|null $used_at код создаёт ровно одну запись
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BookingCode extends Model
{
    protected $table = 'booking_codes';

    protected $fillable = [
        'phone',
        'code_hash',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }
}
