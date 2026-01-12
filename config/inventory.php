<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Biaya Penyimpanan Inventory
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk biaya penyimpanan yang digunakan dalam kalkulasi EOQ.
    | Biaya penyimpanan dapat dihitung sebagai persentase dari harga barang
    | atau menggunakan nilai tetap per unit per tahun.
    |
    */

    // Metode perhitungan: 'percentage' atau 'fixed'
    'calculation_method' => env('INVENTORY_STORAGE_COST_METHOD', 'percentage'),

    // Persentase dari harga barang per tahun (jika method = percentage)
    // Contoh: 0.20 = 20% dari harga barang per tahun
    'percentage' => env('INVENTORY_STORAGE_COST_PERCENTAGE', 0.20),

    // Nilai tetap per unit per tahun (jika method = fixed)
    'fixed_amount' => env('INVENTORY_STORAGE_COST_FIXED', 0),

    /*
    |--------------------------------------------------------------------------
    | Service Level untuk Safety Stock
    |--------------------------------------------------------------------------
    |
    | Service level yang digunakan untuk kalkulasi safety stock.
    | 95% service level = Z-score 1.65
    | 99% service level = Z-score 2.33
    |
    */
    'service_level' => env('INVENTORY_SERVICE_LEVEL', 0.95),
    'z_score' => env('INVENTORY_Z_SCORE', 1.65),
];
