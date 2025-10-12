<?php

namespace Database\Factories;

use App\Models\TransaksiPembayaran;
use App\Models\Pembelian;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaksiPembayaranFactory extends Factory
{
    protected $model = TransaksiPembayaran::class;

    public function definition(): array
    {
        $pembelian = Pembelian::where('metode_pembayaran', 'termin')
            ->whereIn('status', ['confirmed', 'partially_received', 'fully_received'])
            ->inRandomOrder()
            ->first();

        if (!$pembelian) {
            $pembelian = Pembelian::whereIn('status', ['confirmed', 'partially_received', 'fully_received'])
                ->inRandomOrder()
                ->first();
        }

        $jenisPembayaran = $this->faker->randomElement(['dp', 'termin', 'pelunasan']);

        // Hitung jumlah pembayaran berdasarkan jenis
        $jumlahPembayaran = 0;
        if ($pembelian) {
            if ($jenisPembayaran === 'dp') {
                $jumlahPembayaran = $pembelian->jumlah_dp;
            } elseif ($jenisPembayaran === 'pelunasan') {
                $jumlahPembayaran = $pembelian->sisa_pembayaran;
            } else {
                $jumlahPembayaran = $this->faker->numberBetween(1000000, $pembelian->sisa_pembayaran);
            }
        }

        return [
            // transaksi_pembayaran_id akan di-generate oleh model
            'pembelian_id' => $pembelian ? $pembelian->pembelian_id : null,
            'jenis_pembayaran' => $jenisPembayaran,
            'tanggal_pembayaran' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'jumlah_pembayaran' => $jumlahPembayaran,
            'metode_pembayaran' => $this->faker->randomElement(['tunai', 'transfer']),
            'bukti_pembayaran' => null, // Will be handled by seeder if needed
            'deskripsi' => $this->faker->sentence,
        ];
    }
}
