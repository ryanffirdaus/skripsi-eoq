<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Pengadaan;
use App\Models\PengadaanDetail;
use App\Models\Supplier;
use App\Models\BahanBaku;
use App\Models\Produk;
use App\Models\Pesanan;

class PengadaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai proses seeding untuk Pengadaan...');

        // Membersihkan tabel untuk mencegah error duplikasi saat seeding ulang
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Pengadaan::truncate();
        PengadaanDetail::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Mengambil data master yang diperlukan
        $suppliers = Supplier::all();
        $bahanBakus = BahanBaku::all();
        $produks = Produk::all();
        $pesanans = Pesanan::all();

        if ($suppliers->isEmpty() || $bahanBakus->isEmpty() || $produks->isEmpty() || $pesanans->isEmpty()) {
            $this->command->error('Data master (Supplier, Bahan Baku, Produk, atau Pesanan) tidak ditemukan. Pastikan seeder lain sudah dijalankan.');
            return;
        }

        // --- DATA SEEDER KOMPREHENSIF ---
        $pengadaanData = [
            // 1. Status: Draft, Jenis: Pesanan (Bahan Baku & Produk)
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->random()->pesanan_id,
                'tanggal_pengadaan' => now()->subDays(2),
                'status' => 'draft',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 75],
                    ['type' => 'produk', 'qty' => 10],
                ]
            ],
            // 2. Status: Pending, Jenis: ROP (Hanya Bahan Baku)
            [
                'jenis_pengadaan' => 'rop',
                'tanggal_pengadaan' => now()->subDays(8),
                'status' => 'pending',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 120],
                    ['type' => 'bahan_baku', 'qty' => 80],
                ]
            ],
            // 3. Status: Procurement Approved
            [
                'jenis_pengadaan' => 'rop',
                'tanggal_pengadaan' => now()->subDays(10),
                'status' => 'procurement_approved',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 100, 'qty_disetujui' => 100],
                    ['type' => 'bahan_baku', 'qty' => 50, 'qty_disetujui' => 45], // Contoh qty disetujui beda
                ]
            ],
            // 4. Status: Finance Approved -> Siap untuk dibuat PO oleh PembelianSeeder
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->random()->pesanan_id,
                'tanggal_pengadaan' => now()->subDays(5),
                'status' => 'finance_approved',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 200, 'qty_disetujui' => 200],
                ]
            ],
            // 5. Status: Ordered -> Sudah diproses oleh PembelianSeeder
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->random()->pesanan_id,
                'tanggal_pengadaan' => now()->subDays(20),
                'status' => 'ordered',
                'nomor_po' => 'PO-' . now()->subDays(20)->format('Ymd') . '-001',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'produk', 'qty' => 25, 'qty_disetujui' => 25],
                ]
            ],
            // 6. Status: Partial Received
            [
                'jenis_pengadaan' => 'rop',
                'tanggal_pengadaan' => now()->subDays(25),
                'tanggal_delivery' => now()->subDays(5),
                'status' => 'partial_received',
                'nomor_po' => 'PO-' . now()->subDays(25)->format('Ymd') . '-002',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 150, 'qty_disetujui' => 150, 'qty_diterima' => 100],
                ]
            ],
            // 7. Status: Received (Lengkap)
            [
                'jenis_pengadaan' => 'rop',
                'tanggal_pengadaan' => now()->subDays(30),
                'tanggal_delivery' => now()->subDays(10),
                'status' => 'received',
                'nomor_po' => 'PO-' . now()->subDays(30)->format('Ymd') . '-003',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 50, 'qty_disetujui' => 50, 'qty_diterima' => 50],
                ]
            ],
            // 8. Status: Cancelled
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->random()->pesanan_id,
                'tanggal_pengadaan' => now()->subDays(4),
                'status' => 'cancelled',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'produk', 'qty' => 5],
                ]
            ],
        ];

        foreach ($pengadaanData as $data) {
            $detailItems = $data['details'];
            unset($data['details']);

            $pengadaan = Pengadaan::create($data);

            foreach ($detailItems as $item) {
                $itemModel = null;
                if ($item['type'] === 'bahan_baku') {
                    $itemModel = $bahanBakus->random();
                    $detailData = [
                        'item_id' => $itemModel->bahan_baku_id,
                        'nama_item' => $itemModel->nama_bahan,
                        'satuan' => $itemModel->satuan_bahan,
                        'harga_satuan' => $itemModel->harga_bahan,
                    ];
                } else { // produk
                    $itemModel = $produks->random();
                    $detailData = [
                        'item_id' => $itemModel->produk_id,
                        'nama_item' => $itemModel->nama_produk,
                        'satuan' => $itemModel->satuan_produk,
                        'harga_satuan' => $itemModel->hpp_produk,
                    ];
                }

                PengadaanDetail::create([
                    'pengadaan_id' => $pengadaan->pengadaan_id,
                    'supplier_id' => $suppliers->random()->supplier_id,
                    'item_type' => $item['type'],
                    'item_id' => $detailData['item_id'],
                    'nama_item' => $detailData['nama_item'],
                    'satuan' => $detailData['satuan'],
                    'harga_satuan' => $detailData['harga_satuan'],
                    'qty_diminta' => $item['qty'],
                    'qty_disetujui' => $item['qty_disetujui'] ?? null,
                    'qty_diterima' => $item['qty_diterima'] ?? 0,
                    'total_harga' => ($item['qty_disetujui'] ?? $item['qty']) * $detailData['harga_satuan'],
                    'catatan' => 'Catatan seeder ' . $item['type'],
                    'alasan_kebutuhan' => 'Kebutuhan dari seeder'
                ]);
            }

            // Update total biaya setelah semua detail ditambahkan
            $pengadaan->updateTotalBiaya();
        }

        $this->command->info('Seeding untuk Pengadaan selesai dengan sukses.');
    }
}
