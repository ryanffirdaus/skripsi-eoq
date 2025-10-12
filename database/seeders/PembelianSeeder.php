<?php

namespace Database\Seeders;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Pengadaan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PembelianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai proses seeding untuk Pembelian...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Pembelian::truncate();
        PembelianDetail::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get pengadaan that are approved by finance and ready for PO
        $pengadaanSiapProses = Pengadaan::where('status', 'disetujui_finance')->with('detail.pemasok')->get();

        if ($pengadaanSiapProses->isEmpty()) {
            $this->command->warn('Tidak ditemukan data Pengadaan dengan status "disetujui_finance". Seeding Pembelian dilewati.');
            return;
        }

        // Status options: draft, sent, confirmed, partially_received, fully_received, cancelled
        $statusOptions = ['draft', 'sent', 'confirmed', 'partially_received', 'fully_received'];
        $metodePembayaranOptions = ['tunai', 'transfer', 'termin'];

        $statusIndex = 0; // To cycle through all status options

        foreach ($pengadaanSiapProses as $pengadaan) {
            // Group items by pemasok
            $itemsByPemasok = $pengadaan->detail
                ->filter(function ($item) {
                    return ($item->qty_disetujui !== null && $item->qty_disetujui > 0) && $item->pemasok_id !== null;
                })
                ->groupBy('pemasok_id');

            if ($itemsByPemasok->isEmpty()) {
                $this->command->line("  > Pengadaan {$pengadaan->pengadaan_id} tidak memiliki item yang disetujui dengan pemasok. Dilewati.");
                continue;
            }

            // Create one PO for each pemasok
            foreach ($itemsByPemasok as $pemasokId => $detailItems) {
                // Cycle through status options to ensure all statuses have examples
                $status = $statusOptions[$statusIndex % count($statusOptions)];
                $statusIndex++;

                // Random payment method
                $metodePembayaran = $metodePembayaranOptions[array_rand($metodePembayaranOptions)];

                // Calculate total biaya
                $totalBiaya = $detailItems->sum(function ($item) {
                    return ($item->qty_disetujui ?? $item->qty_diminta) * $item->harga_satuan;
                });

                // Set termin and DP if termin payment
                $terminPembayaran = null;
                $jumlahDp = null;
                if ($metodePembayaran === 'termin') {
                    $terminPembayaran = "30% DP, 40% setelah pengiriman 50%, 30% pelunasan";
                    $jumlahDp = $totalBiaya * 0.3;
                }

                // 1. Create Pembelian header
                $pembelian = Pembelian::create([
                    'pengadaan_id' => $pengadaan->pengadaan_id,
                    'pemasok_id' => $pemasokId,
                    'tanggal_pembelian' => now()->subDays(rand(1, 10)),
                    'tanggal_kirim_diharapkan' => now()->addDays(rand(7, 21)),
                    'metode_pembayaran' => $metodePembayaran,
                    'termin_pembayaran' => $terminPembayaran,
                    'jumlah_dp' => $jumlahDp ?? 0,
                    'status' => $status,
                ]);

                // 2. Create PembelianDetail for each item
                foreach ($detailItems as $item) {
                    PembelianDetail::create([
                        'pembelian_id' => $pembelian->pembelian_id,
                        'pengadaan_detail_id' => $item->pengadaan_detail_id,
                    ]);
                }

                $this->command->line("  > PO {$pembelian->nomor_po} untuk Pemasok ID {$pemasokId} berhasil dibuat dari Pengadaan {$pengadaan->pengadaan_id} (Status: {$status}, Payment: {$metodePembayaran}).");
            }

            // 4. Update pengadaan status to 'diproses'
            $pengadaan->status = 'diproses';
            $pengadaan->save();
        }

        $this->command->info('Seeding untuk Pembelian selesai.');
    }
}
