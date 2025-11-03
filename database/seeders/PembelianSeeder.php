<?php

namespace Database\Seeders;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Pengadaan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PembelianSeeder
 *
 * Creates Purchase Orders (Pembelian) from approved Pengadaan records.
 *
 * Workflow:
 * - Filters Pengadaan with status='processed' (approved by all levels including finance)
 * - Only processes pengadaan containing 'bahan_baku' items (raw materials)
 * - Groups items by pemasok (supplier) to create one PO per supplier
 * - Creates PembelianDetail records linking to PengadaanDetail
 * - Assigns various PO statuses: draft, sent, confirmed, partially_received, fully_received
 * - Sets payment methods: tunai (cash), transfer, termin (installment)
 *
 * Output:
 * - Multiple Pembelian records with different statuses for demo/test purposes
 * - Each PO linked to its pengadaan source and supplier
 */
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

        // Get pengadaan that have status 'diproses' and ready for PO (only bahan_baku)
        // These are pengadaan that have passed all approval stages from finance
        $pengadaanSiapProses = Pengadaan::where('status', 'diproses')
            ->with('detail')
            ->get()
            ->filter(function ($pengadaan) {
                // Only include pengadaan that have at least one bahan_baku item
                return $pengadaan->detail->where('jenis_barang', 'bahan_baku')->count() > 0;
            });

        if ($pengadaanSiapProses->isEmpty()) {
            $this->command->warn('Tidak ditemukan data Pengadaan dengan status "diproses" yang berisi bahan baku. Seeding Pembelian dilewati.');
            return;
        }

        // Status options: draft, menunggu, dipesan, dikirim, dikonfirmasi, diterima, dibatalkan
        $statusOptions = ['draft', 'menunggu', 'dipesan', 'dikirim', 'dikonfirmasi', 'diterima'];
        $metodePembayaranOptions = ['tunai', 'transfer', 'termin'];

        $statusIndex = 0; // To cycle through all status options

        foreach ($pengadaanSiapProses as $pengadaan) {
            // Filter only bahan_baku items and group by pemasok
            $itemsByPemasok = $pengadaan->detail
                ->where('jenis_barang', 'bahan_baku')
                ->filter(function ($item) {
                    return ($item->qty_disetujui !== null && $item->qty_disetujui > 0) && $item->pemasok_id !== null;
                })
                ->groupBy('pemasok_id');

            if ($itemsByPemasok->isEmpty()) {
                $this->command->line("  > Pengadaan {$pengadaan->pengadaan_id} tidak memiliki item bahan baku yang disetujui dengan pemasok. Dilewati.");
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

                $this->command->line("  > PO {$pembelian->pembelian_id} untuk Pemasok ID {$pemasokId} berhasil dibuat dari Pengadaan {$pengadaan->pengadaan_id} (Status: {$status}, Payment: {$metodePembayaran}).");
            }
        }

        $this->command->info('Seeding untuk Pembelian selesai.');
    }
}
