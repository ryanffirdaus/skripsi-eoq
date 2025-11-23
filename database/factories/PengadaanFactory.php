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

        $jenis_pengadaan = ['reguler', 'darurat', 'framework'];
        $statuses = ['draft', 'menunggu persetujuan', 'disetujui', 'ditolak', 'diterima', 'selesai'];

        return [
            'pengadaan_id' => 'PG' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'jenis_pengadaan' => $this->faker->randomElement($jenis_pengadaan),
            'status' => $this->faker->randomElement($statuses),
            'catatan' => $this->faker->optional()->sentence(),
        ];
    }
}
