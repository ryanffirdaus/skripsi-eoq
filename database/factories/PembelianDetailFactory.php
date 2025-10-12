<?php

namespace Database\Factories;

use App\Models\PembelianDetail;
use App\Models\PengadaanDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class PembelianDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PembelianDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Factory ini akan lebih banyak diatur dari seeder
        // karena butuh konteks dari Pembelian dan PengadaanDetail
        // Struktur baru: hanya menyimpan pengadaan_detail_id
        // Data lain diambil dari relasi pengadaanDetail

        return [
            // ID akan di-generate oleh model
            // pembelian_id dan pengadaan_detail_id akan diisi dari seeder
        ];
    }
}
