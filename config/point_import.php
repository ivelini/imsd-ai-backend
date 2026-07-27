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
        '0-5000' => 'price_0_5000',
        '5001-8500' => 'price_5001_8500',
        '8501-10000' => 'price_8501_10000',
        '10001-15000' => 'price_10001_15000',
        '15001-100000' => 'price_15001_100000',
        '31-60 кг' => 'coeff_31_60',
        '61-100кг' => 'coeff_61_100',
    ],

    'required_columns' => [
        'code', 'region_name', 'city_name',
    ],

    'boolean_true' => ['да', 'yes', 'true', '1', '+', 'есть'],
];
