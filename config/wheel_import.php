<?php

return [
    'chunk_size' => env('WHEEL_IMPORT_CHUNK_SIZE', 500),
    'disk' => env('WHEEL_IMPORT_DISK', 'local'),
    'chunk_path' => 'import/wheels',

    'column_map' => [
        'product_article' => 'ean',
        'vendor' => 'brand_name',
        'country' => 'country_name',
        'name' => 'name',
        'color' => 'color',
        'diameter' => 'diameter',
        'width' => 'width',
        'pcd1' => 'pcd1',
        'pcd2' => 'pcd2',
        'dia' => 'hub_diameter',
        'et' => 'et',
        'type' => 'wheel_type_raw',
        'stock' => 'warehouse_name',
        'count' => 'quantity',
        'price' => 'purchase_price',
        'minimum_market_price' => 'minimum_market_price',
    ],

    'required_columns' => [
        'product_article', 'vendor', 'name', 'diameter', 'width',
    ],

    'required_fill_percent' => 80,

    'boolean_true' => ['да', 'yes', 'true', '1', '+', 'есть'],

    'wheel_type_map' => [
        'литые' => 'alloy',
        'литой' => 'alloy',
        'штампованные' => 'steel',
        'штампованный' => 'steel',
        'штамповка' => 'steel',
        'кованые' => 'forged',
        'кованый' => 'forged',
        'ковка' => 'forged',
    ],
];
