<?php

/** TTL кеша в секундах. */
return [
    /** Справочники для дропдаунов (бренды, поставщики, страны, enum-ы). */
    'references' => (int) env('CACHE_TTL_REFERENCES', 3600),
];
