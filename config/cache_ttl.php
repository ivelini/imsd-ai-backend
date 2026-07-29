<?php

/** TTL кеша в секундах. */
return [
    /** Справочники для дропдаунов (бренды, поставщики, страны, enum-ы). */
    'references' => (int) env('CACHE_TTL_REFERENCES', 3600),

    /** Карточка товара (шина/диск) с остатками на складах. */
    'product' => (int) env('CACHE_TTL_PRODUCT', 300),
];
