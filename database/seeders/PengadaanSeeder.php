<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Pengadaan;
use App\Models\PengadaanDetail;
use App\Models\Pemasok;
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
        $pemasoks = Pemasok::all();
        $bahanBakus = BahanBaku::all();
        $produks = Produk::all();
        $pesanans = Pesanan::all();

        if ($pemasoks->isEmpty() || $bahanBakus->isEmpty() || $produks->isEmpty() || $pesanans->isEmpty()) {
            $this->command->error('Data master (Pemasok, Bahan Baku, Produk, atau Pesanan) tidak ditemukan. Pastikan seeder lain sudah dijalankan.');
            return;
        }

        // --- DATA SEEDER KOMPREHENSIF ---
        $pengadaanData = [
            // 1. Status: Draft, Jenis: Pesanan (Bahan Baku & Produk)
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->random()->pesanan_id,
                'status' => 'pending',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 75],
                    ['type' => 'produk', 'qty' => 10],
                ]
            ],
            // 2. Status: Pending, Jenis: ROP (Hanya Bahan Baku)
            [
                'jenis_pengadaan' => 'rop',
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
                'status' => 'disetujui_procurement',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 100],
                    ['type' => 'bahan_baku', 'qty' => 50],
                ]
            ],
            // 4. Status: Finance Approved -> Siap untuk dibuat PO oleh PembelianSeeder
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->random()->pesanan_id,
                'status' => 'disetujui_finance',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 200],
                ]
            ],
            // 5. Status: Ordered -> Sudah diproses oleh PembelianSeeder
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->random()->pesanan_id,
                'status' => 'diproses',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'produk', 'qty' => 25],
                ]
            ],
            // 6. Status: Partial Received
            [
                'jenis_pengadaan' => 'rop',
                'status' => 'diproses',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 150],
                ]
            ],
            // 7. Status: Received (Lengkap)
            [
                'jenis_pengadaan' => 'rop',
                'status' => 'diterima',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 50],
                ]
            ],
            // 8. Status: Cancelled
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->random()->pesanan_id,
                'status' => 'dibatalkan',
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
                    'pemasok_id' => $pemasoks->random()->pemasok_id,
                    'jenis_barang' => $item['type'],
                    'barang_id' => $detailData['item_id'],
                    'qty_diminta' => $item['qty'],
                    'qty_disetujui' => $item['qty_disetujui'] ?? $item['qty'],
                    'qty_diterima' => $item['qty_diterima'] ?? 0,
                    'harga_satuan' => $detailData['harga_satuan'],
                    'catatan' => 'Catatan seeder ' . $item['type'],
                ]);
            }
        }

        $this->command->info('Seeding untuk Pengadaan selesai dengan sukses.');
    }
}
