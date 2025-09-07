<?php

namespace Database\Factories;

use App\Models\Pembelian;
use App\Models\Pengadaan;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PembelianFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Pembelian::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Ambil pengadaan dan supplier yang sudah ada untuk referensi
        // Pastikan Anda sudah menjalankan seeder untuk Pengadaan, Supplier, dan User sebelumnya
        $pengadaan = Pengadaan::inRandomOrder()->first();
        $supplier = Supplier::inRandomOrder()->first();
        $user = User::inRandomOrder()->first();

        return [
            // pembelian_id dan nomor_po akan di-generate oleh boot method di model
            'pengadaan_id' => $pengadaan ? $pengadaan->pengadaan_id : null,
            'supplier_id' => $supplier ? $supplier->supplier_id : null,
            'tanggal_pembelian' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'tanggal_kirim_diharapkan' => $this->faker->dateTimeBetween('now', '+1 month'),
            'total_biaya' => 0, // Akan dihitung ulang oleh seeder
            'status' => $this->faker->randomElement(['sent', 'confirmed', 'partially_received', 'fully_received', 'cancelled']),
            'catatan' => $this->faker->sentence,
            'created_by' => $user ? $user->user_id : null,
        ];
    }
}
