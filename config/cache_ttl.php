<?php

/** TTL кеша в секундах. */
return [
    /** Справочники для дропдаунов (бренды, поставщики, страны, enum-ы). */
    'references' => (int) env('CACHE_TTL_REFERENCES', 3600),

    /** Фасеты фильтра шин (публичный каталог). */
    'tire_filter' => (int) env('CACHE_TTL_TIRE_FILTER', 3600),

    /** Листинг шин (публичный каталог). */
    'tire_list' => (int) env('CACHE_TTL_TIRE_LIST', 3600),
];
