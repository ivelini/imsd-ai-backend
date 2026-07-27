<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Статья блога. Заглушка для morphMap. */
class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'body',
    ];
}
