<?php

return [
    'disk' => env('POINT_IMPORT_DISK', 'local'),
    'column_map' => [
        'code' => 'region_code',
        'region_name' => 'region_name',
        'city_name' => 'city_name',
        'Срок доставки' => 'delivery_days',
        'address' => 'address',
        'Дней работы в неделю' => 'work_days_per_week',
        'work_days' => 'work_hours',
        'weekend' => 'weekend_hours',
        'Телефон' => 'phone',
        'Эл.почта' => 'email',
        'Доп.информация' => 'info',
        'Выдача с борта' => 'pickup_from_truck_raw',
    ],

    /*
    | Колонки с диапазонами цен и веса определяются динамически:
    | - формат "\\d+-\\d+" → price_range: from/to из заголовка
    | - формат "\\d+-\\d+ кг" → delivery_coeff: from/to из заголовка
    */

    'required_columns' => [
        'code', 'region_name', 'city_name',
    ],

    'boolean_true' => ['да', 'yes', 'true', '1', '+', 'есть'],
];
