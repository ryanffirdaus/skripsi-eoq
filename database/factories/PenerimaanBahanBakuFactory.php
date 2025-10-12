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
        // Ambil pembelian yang sudah dikirim atau sedang dalam proses penerimaan
        $pembelian = Pembelian::whereIn('status', ['sent', 'partially_received'])->inRandomOrder()->first();

        return [
            'pembelian_id' => $pembelian->pembelian_id,
            'pemasok_id' => $pembelian->pemasok_id,
            'nomor_surat_jalan' => 'SJ-' . $this->faker->unique()->numberBetween(1000, 9999),
            'tanggal_penerimaan' => $this->faker->dateTimeBetween($pembelian->tanggal_pembelian, '+2 weeks'),
            'status' => 'confirmed',
            'catatan' => $this->faker->sentence,
            'created_by' => 'US001',
        ];
    }
}
