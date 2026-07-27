<?php

return [
    /*
    | Размер чанка (строк XLSX) для одного ChunkJob.
    */
    'chunk_size' => env('TIRE_IMPORT_CHUNK_SIZE', 500),

    /*
    | Диск для хранения JSON-чанков.
    */
    'disk' => env('TIRE_IMPORT_DISK', 'local'),

    /*
    | Путь к директории чанков внутри диска.
    */
    'chunk_path' => 'import/tires',

    /*
    | Маппинг колонок XLSX → поля DTO.
    | Ключ — заголовок из XLSX, значение — поле ImportTireRow.
    */
    'column_map' => [
        'product_article' => 'ean',
        'vendor' => 'brand_name',
        'season' => 'season_raw',
        'country' => 'country_name',
        'name' => 'name',
        'width' => 'width',
        'height' => 'profile',
        'diameter' => 'diameter',
        'load_speed_index' => 'load_speed_index',
        'is_runflat' => 'is_runflat_raw',
        'is_spike' => 'is_studded_raw',
        'supplier' => 'supplier_name',
        'stock' => 'warehouse_name',
        'count' => 'quantity',
        'price' => 'purchase_price',
        'minimum_market_price' => 'minimum_market_price',
        'vendor_description' => 'description_vendor',
        'description_default' => 'description_default',
        'description_manufacture_country' => 'description_manufacture_country',
        'description_manufacture_year' => 'description_manufacture_year',
        'description_euro_label' => 'description_euro_label',
    ],

    /*
    | Обязательные колонки.
    */
    'required_columns' => [
        'product_article', 'vendor', 'name', 'width', 'height', 'diameter',
    ],

    /*
    | Минимальный процент заполненных обязательных полей.
    */
    'required_fill_percent' => 80,

    /*
    | Значения, считающиеся true для boolean-полей.
    */
    'boolean_true' => ['да', 'yes', 'true', '1', '+', 'есть'],

    /*
    | Маппинг сезонов: XLSX → enum.
    */
    'season_map' => [
        'зимняя' => 'winter',
        'зимние' => 'winter',
        'зима' => 'winter',
        'зимняя шипованная' => 'winter',
        'зимняя нешипованная' => 'winter',
        'летняя' => 'summer',
        'летние' => 'summer',
        'лето' => 'summer',
        'всесезон' => 'all-season',
        'всесезонная' => 'all-season',
        'всесезонные' => 'all-season',
    ],
];
