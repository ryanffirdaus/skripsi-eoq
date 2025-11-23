<?php

namespace Database\Seeders;

use App\Models\Pengiriman;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PengirimanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada pesanan dan user yang tersedia
        $pesananIds = Pesanan::pluck('pesanan_id')->toArray();
        $userIds = User::pluck('user_id')->toArray();

        if (empty($pesananIds) || empty($userIds)) {
            $this->command->info('Tidak ada pesanan atau user yang tersedia. Silakan jalankan seeder untuk pesanan dan user terlebih dahulu.');
            return;
        }

        // Buat pengiriman untuk beberapa pesanan yang sudah ada
        $selectedPesananIds = array_slice($pesananIds, 0, min(count($pesananIds), 10));

        foreach ($selectedPesananIds as $pesananId) {
            // Skip jika pesanan sudah memiliki pengiriman
            if (Pengiriman::where('pesanan_id', $pesananId)->exists()) {
                continue;
            }

            $status = fake()->randomElement(['menunggu', 'dikirim', 'selesai', 'dibatalkan']);

            $pengiriman = [
                'pesanan_id' => $pesananId,
                'kurir' => fake()->randomElement(['JNE', 'J&T', 'TIKI', 'POS Indonesia', 'SiCepat', 'AnterAja', 'Gojek']),
                'biaya_pengiriman' => fake()->numberBetween(10000, 50000),
                'estimasi_hari' => fake()->numberBetween(1, 5),
                'status' => $status,
                'catatan' => fake()->optional(0.3)->sentence(),
                'dibuat_oleh' => fake()->randomElement($userIds),
                'diubah_oleh' => fake()->randomElement($userIds),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Set tanggal dan nomor resi berdasarkan status
            switch ($status) {
                case 'menunggu':
                    $pengiriman['nomor_resi'] = null;
                    $pengiriman['tanggal_kirim'] = null;
                    $pengiriman['tanggal_diterima'] = null;
                    break;
                case 'dikirim':
                    $pengiriman['nomor_resi'] = fake()->numerify('##########');
                    $pengiriman['tanggal_kirim'] = fake()->dateTimeBetween('-3 days', 'now');
                    $pengiriman['tanggal_diterima'] = null;
                    break;
                case 'selesai':
                    $tanggalKirim = fake()->dateTimeBetween('-7 days', '-2 days');
                    $pengiriman['nomor_resi'] = fake()->numerify('##########');
                    $pengiriman['tanggal_kirim'] = $tanggalKirim;
                    $pengiriman['tanggal_diterima'] = fake()->dateTimeBetween($tanggalKirim, 'now');
                    break;
                case 'dibatalkan':
                    $pengiriman['nomor_resi'] = fake()->optional(0.3)->numerify('##########');
                    $pengiriman['tanggal_kirim'] = null;
                    $pengiriman['tanggal_diterima'] = null;
                    break;
            }

            Pengiriman::create($pengiriman);
        }

        $this->command->info('Pengiriman seeder completed successfully!');
    }
}
