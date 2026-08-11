<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Изображение товара (полиморф: шина/диск). */
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
