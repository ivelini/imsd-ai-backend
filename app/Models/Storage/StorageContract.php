<?php

namespace App\Models\Storage;

use App\Casts\MoneyCast;
use App\Enums\Storage\StorageContractStatus;
use App\Models\Auth\Admin;
use App\Models\User;
use App\ValueObjects\Money;
use Carbon\Carbon;
use Database\Factories\Storage\StorageContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Договор хранения колёс клиента: срок, стоимость и позиции — что оставлено на хранении.
 *
 * @property int $id
 * @property int $user_id
 * @property string $personal_document
 * @property Carbon $starts_on срок хранения, от
 * @property Carbon $ends_on срок хранения, до
 * @property Money $price стоимость за весь срок, копейки
 * @property StorageContractStatus $status
 * @property Carbon|null $closed_at дата фактической выдачи колёс
 * @property int|null $operator_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $number номер договора для панели и бумаги: ID с ведущими нулями
 */
class StorageContract extends Model
{
    /** @use HasFactory<StorageContractFactory> */
    use HasFactory;

    protected $table = 'storage_contracts';

    protected $fillable = [
        'user_id',
        'personal_document',
        'starts_on',
        'ends_on',
        'price',
        'status',
        'closed_at',
        'operator_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'price' => MoneyCast::class,
            'status' => StorageContractStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> клиент, чьи колёса лежат на хранении */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Admin, $this> сотрудник панели, заведший договор (nullable) */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'operator_id');
    }

    /** @return HasMany<StorageItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(StorageItem::class);
    }

    /**
     * Номер договора: ID с ведущими нулями до пяти знаков. Отдельной колонки нет — ID не меняется,
     * а копия завела бы второй источник правды (номер-то и есть ID).
     */
    public function getNumberAttribute(): string
    {
        return str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
