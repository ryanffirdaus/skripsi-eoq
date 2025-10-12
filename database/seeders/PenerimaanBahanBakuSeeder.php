<?php

namespace Database\Seeders;

use App\Models\PenerimaanBahanBaku;
use App\Models\PenerimaanBahanBakuDetail;
use App\Models\Pembelian;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PenerimaanBahanBakuSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Memulai proses seeding untuk Penerimaan Bahan Baku...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PenerimaanBahanBaku::truncate();
        PenerimaanBahanBakuDetail::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Cari PO yang sudah dikirim (ordered) atau diterima sebagian (partially_received)
        $eligiblePembelian = Pembelian::whereIn('status', ['ordered', 'partially_received'])->get();

        if ($eligiblePembelian->isEmpty()) {
            $this->command->warn('Tidak ditemukan Pembelian yang siap untuk diterima barangnya. Seeding dilewati.');
            return;
        }

        foreach ($eligiblePembelian as $pembelian) {
            // Buat 1-2 penerimaan per PO
            for ($i = 0; $i < rand(1, 2); $i++) {
                // Pastikan masih ada item yang belum diterima sepenuhnya
                $outstandingItems = $pembelian->detail()->whereRaw('qty_diterima < qty_dipesan')->get();
                if ($outstandingItems->isEmpty()) {
                    continue; // Lanjut ke PO berikutnya jika semua sudah diterima
                }

                $penerimaan = PenerimaanBahanBaku::factory()->create([
                    'pembelian_id' => $pembelian->pembelian_id,
                    'pemasok_id' => $pembelian->pemasok_id,
                ]);

                // Ambil beberapa item yang belum lunas untuk penerimaan ini
                foreach ($outstandingItems->random(rand(1, $outstandingItems->count())) as $itemDetail) {
                    $qtySisa = $itemDetail->getOutstandingQty();
                    if ($qtySisa <= 0) continue;

                    // Terima sebagian atau semua sisa
                    $qtyDiterima = rand(1, $qtySisa);

                    PenerimaanBahanBakuDetail::create([
                        'penerimaan_id' => $penerimaan->penerimaan_id,
                        'pembelian_detail_id' => $itemDetail->pembelian_detail_id,
                        'item_id' => $itemDetail->item_id,
                        'nama_item' => $itemDetail->nama_item,
                        'satuan' => $itemDetail->satuan,
                        'qty_dipesan' => $itemDetail->qty_dipesan,
                        'qty_diterima' => $qtyDiterima,
                        'qty_sisa_sebelumnya' => $qtySisa,
                    ]);
                }
            }
        }

        $this->command->info('Seeding Penerimaan Bahan Baku selesai.');
    }
}
