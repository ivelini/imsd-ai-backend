<?php

namespace App\Models\System;

use App\Enums\System\SettingKeyEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Параметры конфигурации (key/value), управляемые из админки без деплоя.
 *
 * @property string $key
 * @property string $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    /** Актуальное значение параметра; строки нет — дефолт из SettingKeyEnum. */
    public static function get(SettingKeyEnum $key): int
    {
        $setting = static::find($key->value);

        return $setting === null ? $key->default() : (int) $setting->value;
    }
}
