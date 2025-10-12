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
            // 1. Pengadaan ROP - Bahan Baku (Pending)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'pending',
                'catatan' => 'Pengadaan otomatis berdasarkan ROP bahan baku',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 100, 'qty_disetujui' => null],
                    ['type' => 'bahan_baku', 'qty' => 150, 'qty_disetujui' => null],
                ],
            ],

            // 2. Pengadaan ROP - Produk (Approved by Procurement)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'disetujui_procurement',
                'catatan' => 'Pengadaan otomatis berdasarkan ROP produk',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'produk', 'qty' => 50, 'qty_disetujui' => 48],
                    ['type' => 'produk', 'qty' => 30, 'qty_disetujui' => 30],
                ],
            ],

            // 3. Pengadaan dari Pesanan - Mixed (Approved by Procurement)
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->first()->pesanan_id,
                'status' => 'disetujui_procurement',
                'catatan' => 'Pengadaan dari pesanan pelanggan - bahan baku dan produk',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 200, 'qty_disetujui' => 180],
                    ['type' => 'produk', 'qty' => 75, 'qty_disetujui' => 75],
                    ['type' => 'bahan_baku', 'qty' => 120, 'qty_disetujui' => 120],
                ],
            ],

            // 4. Pengadaan dari Pesanan - Bahan Baku Only (Finance Approved - Ready for PO)
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->skip(1)->first()->pesanan_id ?? $pesanans->first()->pesanan_id,
                'status' => 'disetujui_finance',
                'catatan' => 'Pengadaan bahan baku untuk pesanan - siap dibuat PO',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 150, 'qty_disetujui' => 150],
                    ['type' => 'bahan_baku', 'qty' => 100, 'qty_disetujui' => 100],
                    ['type' => 'bahan_baku', 'qty' => 80, 'qty_disetujui' => 80],
                ],
            ],

            // 5. Pengadaan ROP - Produk Only (Finance Approved - Ready for PO)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'disetujui_finance',
                'catatan' => 'Pengadaan produk jadi berdasarkan ROP - siap dibuat PO',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'produk', 'qty' => 40, 'qty_disetujui' => 40],
                    ['type' => 'produk', 'qty' => 60, 'qty_disetujui' => 60],
                ],
            ],

            // 6. Pengadaan Mixed - Bahan & Produk (Finance Approved)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'disetujui_finance',
                'catatan' => 'Pengadaan campuran bahan baku dan produk - siap dibuat PO',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 90, 'qty_disetujui' => 90],
                    ['type' => 'produk', 'qty' => 25, 'qty_disetujui' => 25],
                    ['type' => 'bahan_baku', 'qty' => 110, 'qty_disetujui' => 110],
                    ['type' => 'produk', 'qty' => 35, 'qty_disetujui' => 35],
                ],
            ],

            // 7. Pengadaan Rejected
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->skip(2)->first()->pesanan_id ?? $pesanans->first()->pesanan_id,
                'status' => 'dibatalkan',
                'catatan' => 'Pengadaan dibatalkan - jumlah terlalu besar',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 500, 'qty_disetujui' => null],
                    ['type' => 'produk', 'qty' => 200, 'qty_disetujui' => null],
                ],
            ],

            // 8. Pengadaan Diproses (PO sudah dibuat, menunggu penerimaan)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'diproses',
                'catatan' => 'Pengadaan sedang diproses - PO sudah dikirim ke pemasok',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 120, 'qty_disetujui' => 120],
                    ['type' => 'bahan_baku', 'qty' => 80, 'qty_disetujui' => 80],
                ],
            ],

            // 9. Pengadaan Diterima (Barang sudah diterima lengkap)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'diterima',
                'catatan' => 'Pengadaan selesai - barang sudah diterima lengkap',
                'created_by' => 'US001',
                'details' => [
                    ['type' => 'produk', 'qty' => 50, 'qty_disetujui' => 50],
                    ['type' => 'produk', 'qty' => 30, 'qty_disetujui' => 30],
                ],
            ],
        ];

        // Process each pengadaan
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
                    'qty_disetujui' => $item['qty_disetujui'] ?? null,
                    'qty_diterima' => 0,
                    'harga_satuan' => $detailData['harga_satuan'],
                    'catatan' => 'Catatan seeder ' . $item['type'],
                ]);
            }
        }

        $this->command->info('Seeding untuk Pengadaan selesai dengan sukses.');
    }
}
