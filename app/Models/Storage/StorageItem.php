<?php

namespace App\Models\Storage;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Позиция договора хранения: что именно оставлено и с какими особенностями.
 *
 * @property int $id
 * @property int $storage_contract_id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StorageItem extends Model
{
    protected $table = 'storage_items';

    protected $fillable = ['storage_contract_id', 'name', 'description'];

    /** @return BelongsTo<StorageContract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(StorageContract::class, 'storage_contract_id');
    }
}
