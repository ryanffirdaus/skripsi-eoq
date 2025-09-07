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

        // --- PERUBAHAN LOGIKA ---
        // Hanya ambil pengadaan yang sudah disetujui oleh finance dan siap dibuatkan PO.
        // Status 'ordered' tidak perlu dicek, karena seeder ini yang akan mengubah statusnya menjadi 'ordered'.
        // Jika seeder dijalankan lagi, pengadaan yang sudah 'ordered' tidak akan terpilih.
        $pengadaanSiapProses = Pengadaan::where('status', 'finance_approved')->with('detail')->get();

        if ($pengadaanSiapProses->isEmpty()) {
            $this->command->warn('Tidak ditemukan data Pengadaan dengan status "finance_approved". Seeding Pembelian dilewati.');
            return;
        }

        foreach ($pengadaanSiapProses as $pengadaan) {
            // Kelompokkan item detail berdasarkan supplier
            $itemsBySupplier = $pengadaan->detail
                ->where('qty_disetujui', '>', 0)
                ->whereNotNull('supplier_id')
                ->groupBy('supplier_id');

            if ($itemsBySupplier->isEmpty()) {
                $this->command->line("  > Pengadaan {$pengadaan->pengadaan_id} tidak memiliki item yang disetujui dengan supplier. Dilewati.");
                continue; // Lanjut ke pengadaan berikutnya jika tidak ada item yang siap
            }

            // Buat satu PO untuk setiap supplier
            foreach ($itemsBySupplier as $supplierId => $detailItems) {

                // 1. Buat Header Pembelian (PO)
                $pembelian = Pembelian::factory()->create([
                    'pengadaan_id' => $pengadaan->pengadaan_id,
                    'supplier_id' => $supplierId,
                    'status' => 'sent', // Set status awal yang logis
                ]);

                // 2. Buat Detail Pembelian untuk setiap item dari supplier ini
                foreach ($detailItems as $item) {
                    PembelianDetail::factory()->create([
                        'pembelian_id' => $pembelian->pembelian_id,
                        'pengadaan_detail_id' => $item->pengadaan_detail_id,
                        'item_type' => $item->item_type,
                        'item_id' => $item->item_id,
                        'nama_item' => $item->nama_item,
                        'satuan' => $item->satuan,
                        'qty_dipesan' => $item->qty_disetujui, // Ambil qty yang disetujui
                        'harga_satuan' => $item->harga_satuan, // Ambil harga dari pengadaan
                        'total_harga' => $item->qty_disetujui * $item->harga_satuan,
                    ]);
                }

                // 3. Update total biaya di header PO
                $pembelian->updateTotalBiaya();
                $this->command->line("  > PO {$pembelian->nomor_po} untuk Supplier ID {$supplierId} berhasil dibuat dari Pengadaan {$pengadaan->pengadaan_id}.");
            }

            // 4. Update status pengadaan menjadi 'ordered'
            $pengadaan->status = 'ordered';
            $pengadaan->save();
        }

        $this->command->info('Seeding untuk Pembelian selesai.');
    }
}
