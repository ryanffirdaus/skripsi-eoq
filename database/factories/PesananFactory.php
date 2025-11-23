<?php

namespace Database\Factories;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pesanan>
 */
class PesananFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 1;

        $statuses = [
            'menunggu', 
            'dikonfirmasi', 
            'menunggu_pengadaan', 
            'siap_produksi', 
            'sedang_produksi', 
            'siap_dikirim', 
            'dikirim', 
            'selesai', 
            'dibatalkan'
        ];

        return [
            'pesanan_id' => 'PS' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'pelanggan_id' => Pelanggan::factory(),
            'tanggal_pemesanan' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'status' => $this->faker->randomElement($statuses),
            'total_harga' => $this->faker->numberBetween(100000, 10000000),
            'catatan' => $this->faker->optional()->sentence(),
        ];
    }
}
