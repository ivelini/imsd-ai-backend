<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Статья блога. Заглушка для morphMap.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'body',
    ];
}
