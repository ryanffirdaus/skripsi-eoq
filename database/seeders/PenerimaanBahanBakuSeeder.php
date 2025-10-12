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

        // Get Pembelian that need receipts based on their status
        // - sent: waiting to be received
        // - confirmed: confirmed, waiting/receiving goods
        // - partially_received: some items received, need more
        // - fully_received: all items received
        $eligiblePembelian = Pembelian::whereIn('status', ['sent', 'confirmed', 'partially_received', 'fully_received'])
            ->with('detail.pengadaanDetail')
            ->get();

        if ($eligiblePembelian->isEmpty()) {
            $this->command->warn('Tidak ditemukan Pembelian yang siap untuk diterima barangnya. Seeding dilewati.');
            return;
        }

        foreach ($eligiblePembelian as $pembelian) {
            // Determine receipt pattern based on status
            $receiptPattern = match ($pembelian->status) {
                'sent' => null, // No receipt yet
                'confirmed' => 'partial', // Start receiving
                'partially_received' => 'partial', // Continue receiving
                'fully_received' => 'full', // Complete receipt
                default => null,
            };

            if ($receiptPattern === null) {
                continue; // Skip if no receipt needed
            }

            // For each pembelian detail, create penerimaan records
            foreach ($pembelian->detail as $pembelianDetail) {
                $pengadaanDetail = $pembelianDetail->pengadaanDetail;

                if (!$pengadaanDetail) {
                    continue;
                }

                $qtyDipesan = $pengadaanDetail->qty_disetujui ?? $pengadaanDetail->qty_diminta;

                if ($receiptPattern === 'full') {
                    // Fully received: create receipts that total 100%
                    $numReceipts = rand(1, 2);
                    $totalReceived = 0;

                    for ($i = 0; $i < $numReceipts; $i++) {
                        $qtyRemaining = $qtyDipesan - $totalReceived;

                        if ($qtyRemaining <= 0) {
                            break;
                        }

                        // Last receipt gets all remaining
                        if ($i === $numReceipts - 1) {
                            $qtyDiterima = $qtyRemaining;
                        } else {
                            $qtyDiterima = rand(ceil($qtyRemaining * 0.5), ceil($qtyRemaining * 0.8));
                        }

                        PenerimaanBahanBaku::create([
                            'pembelian_detail_id' => $pembelianDetail->pembelian_detail_id,
                            'qty_diterima' => $qtyDiterima,
                        ]);

                        $totalReceived += $qtyDiterima;
                    }

                    $this->command->line("  > Penerimaan LENGKAP untuk PO {$pembelian->nomor_po} item {$pembelianDetail->pembelian_detail_id} berhasil dibuat ({$totalReceived}/{$qtyDipesan}).");
                } else {
                    // Partial receipt: 30-70% of ordered quantity
                    $qtyDiterima = rand(ceil($qtyDipesan * 0.3), ceil($qtyDipesan * 0.7));

                    PenerimaanBahanBaku::create([
                        'pembelian_detail_id' => $pembelianDetail->pembelian_detail_id,
                        'qty_diterima' => $qtyDiterima,
                    ]);

                    $this->command->line("  > Penerimaan SEBAGIAN untuk PO {$pembelian->nomor_po} item {$pembelianDetail->pembelian_detail_id} berhasil dibuat ({$qtyDiterima}/{$qtyDipesan}).");
                }
            }
        }

        $this->command->info('Seeding Penerimaan Bahan Baku selesai.');
    }
}
