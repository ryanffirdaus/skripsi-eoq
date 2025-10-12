<?php

namespace Database\Seeders;

use App\Models\TransaksiPembayaran;
use App\Models\Pembelian;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransaksiPembayaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai proses seeding untuk Transaksi Pembayaran...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        TransaksiPembayaran::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get all Pembelian that need payment transactions
        $pembelianList = Pembelian::with('pemasok')->get();

        if ($pembelianList->isEmpty()) {
            $this->command->warn('Tidak ditemukan data Pembelian. Seeding Transaksi Pembayaran dilewati.');
            return;
        }

        foreach ($pembelianList as $pembelian) {
            $totalBiaya = $pembelian->detail->sum(function ($detail) {
                return $detail->qty_dipesan * $detail->harga_satuan;
            });

            if ($totalBiaya <= 0) {
                continue;
            }

            // Create payment transactions based on payment method
            if ($pembelian->metode_pembayaran === 'tunai') {
                // Tunai: single payment (pelunasan)
                $this->createPayment(
                    $pembelian,
                    'pelunasan',
                    $totalBiaya,
                    'Pembayaran tunai untuk PO ' . $pembelian->nomor_po
                );
            } elseif ($pembelian->metode_pembayaran === 'transfer') {
                // Transfer: single payment (pelunasan)
                $this->createPayment(
                    $pembelian,
                    'pelunasan',
                    $totalBiaya,
                    'Pembayaran transfer untuk PO ' . $pembelian->nomor_po
                );
            } elseif ($pembelian->metode_pembayaran === 'termin') {
                // Termin: multiple payments (DP + termin + pelunasan)
                $jumlahDp = $pembelian->jumlah_dp ?? ($totalBiaya * 0.3);

                // 1. DP Payment (30%)
                $this->createPayment(
                    $pembelian,
                    'dp',
                    $jumlahDp,
                    'Down Payment (30%) untuk PO ' . $pembelian->nomor_po,
                    now()->subDays(rand(15, 20))
                );

                // Depending on status, add more payments
                if (in_array($pembelian->status, ['confirmed', 'partially_received', 'fully_received'])) {
                    // 2. Termin Payment (40% - after 50% delivery)
                    $jumlahTermin = $totalBiaya * 0.4;
                    $this->createPayment(
                        $pembelian,
                        'termin',
                        $jumlahTermin,
                        'Pembayaran Termin (40%) setelah pengiriman 50%',
                        now()->subDays(rand(5, 10))
                    );
                }

                if ($pembelian->status === 'fully_received') {
                    // 3. Pelunasan (30% - after full delivery)
                    $jumlahPelunasan = $totalBiaya - $jumlahDp - ($totalBiaya * 0.4);
                    $this->createPayment(
                        $pembelian,
                        'pelunasan',
                        $jumlahPelunasan,
                        'Pelunasan (30%) setelah barang diterima lengkap',
                        now()->subDays(rand(0, 3))
                    );
                }
            }
        }

        $this->command->info('Seeding untuk Transaksi Pembayaran selesai.');
    }

    /**
     * Helper method to create payment transaction
     */
    private function createPayment(Pembelian $pembelian, string $jenisPembayaran, float $totalPembayaran, string $deskripsi, $tanggalPembayaran = null)
    {
        $transaksi = TransaksiPembayaran::create([
            'pembelian_id' => $pembelian->pembelian_id,
            'jenis_pembayaran' => $jenisPembayaran,
            'tanggal_pembayaran' => $tanggalPembayaran ?? now()->subDays(rand(1, 5)),
            'total_pembayaran' => $totalPembayaran,
            'bukti_pembayaran' => 'BUKTI-' . strtoupper($jenisPembayaran) . '-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT) . '.pdf',
            'deskripsi' => $deskripsi,
        ]);

        $this->command->line("  > Transaksi Pembayaran {$jenisPembayaran} untuk PO {$pembelian->nomor_po} berhasil dibuat (Rp " . number_format($totalPembayaran, 0, ',', '.') . ").");

        return $transaksi;
    }
}
