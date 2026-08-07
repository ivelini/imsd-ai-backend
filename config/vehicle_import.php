<?php

return [
    /*
    | Размер чанка (строк CSV) для одного ChunkJob.
    */
    'chunk_size' => env('VEHICLE_IMPORT_CHUNK_SIZE', 500),

    /*
    | Путь к директории JSON-чанков внутри storage/app.
    */
    'chunk_path' => 'import/vehicles',

    /*
    | Разделитель CSV.
    */
    'delimiter' => ';',

    /*
    | Ожидаемое количество колонок в CSV.
    */
    'expected_columns' => 14,
];
