<?php

namespace Database\Seeders;

use App\Models\TransaksiPembayaran;
use App\Models\Pembelian;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TransaksiPembayaranSeeder
 *
 * Creates Payment Transactions (Transaksi Pembayaran) for Purchase Orders.
 *
 * Payment Method Logic:
 *
 * 1. TUNAI (Cash):
 *    - Single payment: Pelunasan (full amount) immediately
 *    - Status: Always complete payment upfront
 *
 * 2. TRANSFER (Bank Transfer):
 *    - Single payment: Pelunasan (full amount) on agreed date
 *    - Status: Complete payment via transfer
 *
 * 3. TERMIN (Installment):
 *    - DP (Down Payment): 30% of total - created immediately
 *    - Termin: 40% of total - created when status = confirmed/partially_received/fully_received
 *    - Pelunasan: 30% of total - created when status = fully_received
 *    - Staged payments aligned with goods delivery
 *
 * Output:
 * - TransaksiPembayaran records with different payment types and amounts
 * - Summary statistics showing payment distribution by method
 * - Proof documents generated for each transaction
 */
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
        $pembelianList = Pembelian::with('pemasok', 'detail.pengadaanDetail')->get();

        if ($pembelianList->isEmpty()) {
            $this->command->warn('Tidak ditemukan data Pembelian. Seeding Transaksi Pembayaran dilewati.');
            return;
        }

        $paymentStats = [
            'tunai' => 0,
            'transfer' => 0,
            'termin_dp' => 0,
            'termin_full' => 0,
        ];

        foreach ($pembelianList as $pembelian) {
            // Calculate total biaya from pembelian_detail items via pengadaan_detail relationship
            $totalBiaya = 0;
            foreach ($pembelian->detail as $detail) {
                if ($detail->pengadaanDetail) {
                    $qty = $detail->pengadaanDetail->qty_disetujui ?? $detail->pengadaanDetail->qty_diminta;
                    $totalBiaya += $qty * $detail->pengadaanDetail->harga_satuan;
                }
            }

            if ($totalBiaya <= 0) {
                continue;
            }

            // Create payment transactions based on payment method
            if ($pembelian->metode_pembayaran === 'tunai') {
                // Tunai: single full payment
                $this->createPayment(
                    $pembelian,
                    'pelunasan',
                    $totalBiaya,
                    'Pembayaran tunai untuk PO ' . $pembelian->pembelian_id
                );
                $paymentStats['tunai']++;
            } elseif ($pembelian->metode_pembayaran === 'transfer') {
                // Transfer: single full payment
                $this->createPayment(
                    $pembelian,
                    'pelunasan',
                    $totalBiaya,
                    'Pembayaran transfer untuk PO ' . $pembelian->pembelian_id
                );
                $paymentStats['transfer']++;
            } elseif ($pembelian->metode_pembayaran === 'termin') {
                // Termin: multiple payments (DP + termin + pelunasan)
                $jumlahDp = $pembelian->jumlah_dp ?? ($totalBiaya * 0.3);

                // 1. DP Payment (30%) - always created
                $this->createPayment(
                    $pembelian,
                    'dp',
                    $jumlahDp,
                    'Down Payment (30%) untuk PO ' . $pembelian->pembelian_id,
                    now()->subDays(rand(15, 20))
                );

                // 2. Termin Payment (40% - after 50% delivery)
                if (in_array($pembelian->status, ['confirmed', 'partially_received', 'fully_received'])) {
                    $jumlahTermin = $totalBiaya * 0.4;
                    $this->createPayment(
                        $pembelian,
                        'termin',
                        $jumlahTermin,
                        'Pembayaran Termin (40%) setelah pengiriman 50%',
                        now()->subDays(rand(5, 10))
                    );
                    $paymentStats['termin_dp']++;
                }

                // 3. Pelunasan (30% - after full delivery)
                if ($pembelian->status === 'fully_received') {
                    $jumlahPelunasan = $totalBiaya - $jumlahDp - ($totalBiaya * 0.4);
                    $this->createPayment(
                        $pembelian,
                        'pelunasan',
                        $jumlahPelunasan,
                        'Pelunasan (30%) setelah barang diterima lengkap',
                        now()->subDays(rand(0, 3))
                    );
                    $paymentStats['termin_full']++;
                } else {
                    $paymentStats['termin_dp']++;
                }
            }
        }

        // Summary
        $this->command->info('Seeding untuk Transaksi Pembayaran selesai.');
        $this->command->line("  - Pembayaran Tunai: {$paymentStats['tunai']}");
        $this->command->line("  - Pembayaran Transfer: {$paymentStats['transfer']}");
        $this->command->line("  - Pembayaran Termin (DP): {$paymentStats['termin_dp']}");
        $this->command->line("  - Pembayaran Termin (Full): {$paymentStats['termin_full']}");
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

        $this->command->line("  > Transaksi Pembayaran {$jenisPembayaran} untuk PO {$pembelian->pembelian_id} berhasil dibuat (Rp " . number_format($totalPembayaran, 0, ',', '.') . ").");

        return $transaksi;
    }
}
