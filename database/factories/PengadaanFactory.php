<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pengadaan>
 */
class PengadaanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 1;

        $jenis_pengadaan = ['pesanan', 'rop'];
        $statuses = [
            'menunggu_persetujuan_gudang', 
            'menunggu_alokasi_pemasok', 
            'menunggu_persetujuan_pengadaan', 
            'menunggu_persetujuan_keuangan', 
            'diproses', 
            'diterima', 
            'dibatalkan', 
            'ditolak'
        ];

        return [
            'pengadaan_id' => 'PG' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'jenis_pengadaan' => $this->faker->randomElement($jenis_pengadaan),
            'status' => $this->faker->randomElement($statuses),
            'catatan' => $this->faker->optional()->sentence(),
        ];
    }
}
