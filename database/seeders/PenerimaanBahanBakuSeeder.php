<?php

namespace Database\Seeders;

use App\Models\PenerimaanBahanBaku;
use App\Models\PenerimaanBahanBakuDetail;
use App\Models\Pembelian;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PenerimaanBahanBakuSeeder
 *
 * Creates Goods Receipt records (Penerimaan Bahan Baku) for Purchase Orders.
 *
 * Workflow:
 * - Processes Pembelian with statuses: sent, confirmed, partially_received, fully_received
 * - Sent status: No receipts created (still in delivery)
 * - Confirmed/Partially_received: Creates partial receipts (30-70% of ordered qty)
 * - Fully_received: Creates complete receipts that total 100% of order
 * - Supports multiple partial shipments and incremental receipts
 *
 * Receipt Scenarios:
 * - No receipt: Waiting for delivery (sent status)
 * - Partial receipt: First shipment arrives (30-70% of order)
 * - Full receipt: Complete delivery with possible multi-shipment split
 *
 * Output:
 * - PenerimaanBahanBaku records with various receipt patterns
 * - Summary statistics showing receipt distribution
 */
class PenerimaanBahanBakuSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Memulai proses seeding untuk Penerimaan Bahan Baku...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PenerimaanBahanBaku::truncate();
        PenerimaanBahanBakuDetail::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get Pembelian with various statuses to create diverse receipt examples
        // sent: waiting to be received
        // confirmed: confirmed, ready/receiving goods
        // partially_received: some items received, need more
        // fully_received: all items received (complete)
        $eligiblePembelian = Pembelian::whereIn('status', ['sent', 'confirmed', 'partially_received', 'fully_received'])
            ->with('detail.pengadaanDetail')
            ->get();

        if ($eligiblePembelian->isEmpty()) {
            $this->command->warn('Tidak ditemukan Pembelian dengan status yang sesuai (sent, confirmed, partially_received, fully_received). Seeding dilewati.');
            return;
        }

        $receiptStatusCount = [
            'no_receipt' => 0,
            'partial_receipt' => 0,
            'full_receipt' => 0,
        ];

        foreach ($eligiblePembelian as $pembelian) {
            // Determine receipt pattern based on status
            $receiptPattern = match ($pembelian->status) {
                'sent' => null, // No receipt yet - waiting for goods
                'confirmed' => 'partial', // Start receiving partial shipment
                'partially_received' => 'partial', // Continue receiving (second shipment)
                'fully_received' => 'full', // Complete receipt (all items received)
                default => null,
            };

            if ($receiptPattern === null) {
                $receiptStatusCount['no_receipt']++;
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
                    $receiptStatusCount['full_receipt']++;
                } else {
                    // Partial receipt: 30-70% of ordered quantity
                    $qtyDiterima = rand(ceil($qtyDipesan * 0.3), ceil($qtyDipesan * 0.7));

                    PenerimaanBahanBaku::create([
                        'pembelian_detail_id' => $pembelianDetail->pembelian_detail_id,
                        'qty_diterima' => $qtyDiterima,
                    ]);

                    $this->command->line("  > Penerimaan SEBAGIAN untuk PO {$pembelian->nomor_po} item {$pembelianDetail->pembelian_detail_id} berhasil dibuat ({$qtyDiterima}/{$qtyDipesan}).");
                    $receiptStatusCount['partial_receipt']++;
                }
            }
        }

        // Summary
        $this->command->info('Seeding Penerimaan Bahan Baku selesai.');
        $this->command->line("  - PO tanpa penerimaan (sent): {$receiptStatusCount['no_receipt']}");
        $this->command->line("  - PO dengan penerimaan sebagian: {$receiptStatusCount['partial_receipt']}");
        $this->command->line("  - PO dengan penerimaan lengkap: {$receiptStatusCount['full_receipt']}");
    }
}
