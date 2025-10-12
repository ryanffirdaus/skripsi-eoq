<?php

namespace Database\Factories;

use App\Models\Pembelian;
use App\Models\Pengadaan;
use App\Models\Pemasok;
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
        // Ambil pengadaan dan pemasok yang sudah ada untuk referensi
        // Pastikan Anda sudah menjalankan seeder untuk Pengadaan, pemasok, dan User sebelumnya
        $pengadaan = Pengadaan::inRandomOrder()->first();
        $pemasok = Pemasok::inRandomOrder()->first();
        $user = User::inRandomOrder()->first();

        return [
            // pembelian_id dan nomor_po akan di-generate oleh boot method di model
            'pengadaan_id' => $pengadaan ? $pengadaan->pengadaan_id : null,
            'pemasok_id' => $pemasok ? $pemasok->pemasok_id : null,
            'tanggal_pembelian' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'tanggal_kirim_diharapkan' => $this->faker->dateTimeBetween('now', '+1 month'),
            'total_biaya' => 0, // Akan dihitung ulang oleh seeder
            'status' => $this->faker->randomElement(['sent', 'confirmed', 'partially_received', 'fully_received', 'cancelled']),
            'catatan' => $this->faker->sentence,
            'created_by' => $user ? $user->user_id : null,
        ];
    }
}
