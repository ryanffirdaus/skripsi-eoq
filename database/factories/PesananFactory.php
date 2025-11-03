<?php

namespace Database\Factories;

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
        $statuses = ['menunggu', 'dikonfirmasi', 'diproses', 'siap', 'dikirim', 'diterima', 'dibatalkan', 'selesai'];
        $statusWeights = [20, 15, 20, 10, 15, 10, 5, 5]; // Weighted probability for more realistic distribution

        return [
            'tanggal_pemesanan' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'status' => $this->faker->randomElement($statuses),
            'total_harga' => 0, // Will be calculated when products are attached
        ];
    }
}
