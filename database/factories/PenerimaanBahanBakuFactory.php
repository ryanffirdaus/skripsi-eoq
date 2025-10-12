<?php

namespace Database\Factories;

use App\Models\Pembelian;
use App\Models\PenerimaanBahanBaku;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenerimaanBahanBakuFactory extends Factory
{
    protected $model = PenerimaanBahanBaku::class;

    public function definition(): array
    {
        // Struktur baru: simplified penerimaan
        // Hanya menyimpan pembelian_detail_id dan qty_diterima
        // Data lain diambil dari relasi

        return [
            // pembelian_detail_id dan qty_diterima akan diisi dari seeder
            // karena butuh konteks dari PembelianDetail
        ];
    }
}
