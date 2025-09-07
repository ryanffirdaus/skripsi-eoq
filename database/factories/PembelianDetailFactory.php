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

        $hargaSatuan = $this->faker->numberBetween(5000, 100000);
        $qtyDipesan = $this->faker->numberBetween(5, 50);

        return [
            // ID akan di-generate oleh model
            'qty_dipesan' => $qtyDipesan,
            'qty_diterima' => $this->faker->numberBetween(0, $qtyDipesan),
            'harga_satuan' => $hargaSatuan,
            'total_harga' => $hargaSatuan * $qtyDipesan,
            // Kolom lain (pembelian_id, pengadaan_detail_id, item_type, dll.) akan diisi dari seeder
        ];
    }
}
