<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Изображение товара (полиморф: шина/диск).
 *
 * @property int $id
 * @property string $imageable_type
 * @property int $imageable_id
 * @property string $path
 * @property int $sort
 * @property bool $is_main
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Image extends Model
{
    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'path',
        'sort',
        'is_main',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_main' => 'boolean',
        ];
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
