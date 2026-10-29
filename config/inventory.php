<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | Products with stock at or below this number are flagged in the low-stock alert.
    | Can be overridden via environment variable or per API query.
    |
    */
    'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK_THRESHOLD', 5),
];
