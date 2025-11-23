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

        // --- DATA SEEDER KOMPREHENSIF UNTUK WORKFLOW PENGADAAN 7 TAHAP ---
        // Workflow: draft → pending_approval_gudang → pending_supplier_allocation → pending_approval_pengadaan
        //           → pending_approval_keuangan → processed → received (+ cancelled from any stage)

        $pengadaanData = [
            // ============ STAGE 1: DRAFT ============
            // 1. ROP - Bahan Baku (Draft)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'menunggu_persetujuan_gudang',
                'catatan' => 'Pengadaan otomatis berdasarkan ROP bahan baku - menunggu review gudang',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 100, 'qty_disetujui' => null],
                    ['type' => 'bahan_baku', 'qty' => 150, 'qty_disetujui' => null],
                ],
            ],

            // 2. Pesanan - Mixed (Draft -> Menunggu Persetujuan Gudang)
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->first()->pesanan_id,
                'status' => 'menunggu_persetujuan_gudang',
                'catatan' => 'Pengadaan dari pesanan pelanggan - draft untuk review',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 200, 'qty_disetujui' => null],
                    ['type' => 'produk', 'qty' => 75, 'qty_disetujui' => null],
                ],
            ],

            // ============ STAGE 2: PENDING APPROVAL GUDANG ============
            // 3. ROP - Produk (Pending Approval Gudang)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'menunggu_persetujuan_gudang',
                'catatan' => 'Pengadaan otomatis berdasarkan ROP produk - menunggu persetujuan manajer gudang',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'produk', 'qty' => 50, 'qty_disetujui' => null],
                    ['type' => 'produk', 'qty' => 30, 'qty_disetujui' => null],
                ],
            ],

            // 4. Pesanan - Bahan Baku (Pending Approval Gudang)
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->skip(1)->first()->pesanan_id ?? $pesanans->first()->pesanan_id,
                'status' => 'menunggu_persetujuan_gudang',
                'catatan' => 'Pengadaan bahan untuk pesanan - menunggu persetujuan gudang',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 200, 'qty_disetujui' => null],
                    ['type' => 'bahan_baku', 'qty' => 150, 'qty_disetujui' => null],
                ],
            ],

            // ============ STAGE 3: PENDING SUPPLIER ALLOCATION ============
            // 5. ROP - Mixed (Pending Supplier Allocation)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'menunggu_alokasi_pemasok',
                'catatan' => 'Pengadaan campuran - menunggu alokasi pemasok dari manajer pengadaan',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 90, 'qty_disetujui' => 90],
                    ['type' => 'produk', 'qty' => 25, 'qty_disetujui' => 25],
                ],
            ],

            // 6. Pesanan - Bahan Baku (Pending Supplier Allocation)
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->skip(2)->first()->pesanan_id ?? $pesanans->first()->pesanan_id,
                'status' => 'menunggu_alokasi_pemasok',
                'catatan' => 'Bahan baku dari pesanan - menunggu penunjukan pemasok',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 120, 'qty_disetujui' => 120],
                ],
            ],

            // ============ STAGE 4: PENDING APPROVAL PENGADAAN ============
            // 7. ROP - Produk (Pending Approval Pengadaan)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'menunggu_persetujuan_pengadaan',
                'catatan' => 'Produk jadi ROP - menunggu persetujuan akhir manajer pengadaan',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'produk', 'qty' => 40, 'qty_disetujui' => 40],
                    ['type' => 'produk', 'qty' => 60, 'qty_disetujui' => 60],
                ],
            ],

            // ============ STAGE 5: PENDING APPROVAL KEUANGAN ============
            // 8. ROP - Bahan Baku (Pending Approval Keuangan)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'menunggu_persetujuan_keuangan',
                'catatan' => 'Bahan baku ROP - menunggu persetujuan budget dari manajer keuangan',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 100, 'qty_disetujui' => 100],
                    ['type' => 'bahan_baku', 'qty' => 150, 'qty_disetujui' => 150],
                ],
            ],

            // 9. Pesanan - Mixed (Pending Approval Keuangan)
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->first()->pesanan_id,
                'status' => 'menunggu_persetujuan_keuangan',
                'catatan' => 'Pengadaan dari pesanan - review finansial oleh keuangan',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 150, 'qty_disetujui' => 150],
                    ['type' => 'produk', 'qty' => 75, 'qty_disetujui' => 75],
                ],
            ],

            // ============ STAGE 6: PROCESSED (Ready for PO) ============
            // 10. ROP - Bahan Baku Only (Processed - Siap PO)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'diproses',
                'catatan' => 'Pengadaan bahan baku siap dibuat PO - persetujuan lengkap dari semua level',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 200, 'qty_disetujui' => 200],
                    ['type' => 'bahan_baku', 'qty' => 180, 'qty_disetujui' => 180],
                ],
            ],

            // 11. Pesanan - Bahan Baku (Processed - Siap PO)
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->skip(1)->first()->pesanan_id ?? $pesanans->first()->pesanan_id,
                'status' => 'diproses',
                'catatan' => 'Bahan untuk pesanan siap PO - approved all stages',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 300, 'qty_disetujui' => 300],
                ],
            ],

            // 12. ROP - Produk Only (Processed - Siap PO)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'diproses',
                'catatan' => 'Produk jadi siap dibuat PO - semua approval selesai',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'produk', 'qty' => 50, 'qty_disetujui' => 50],
                    ['type' => 'produk', 'qty' => 40, 'qty_disetujui' => 40],
                ],
            ],

            // ============ STAGE 7: RECEIVED ============
            // 13. ROP - Bahan Baku (Received)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'diterima',
                'catatan' => 'Pengadaan bahan baku selesai - barang sudah diterima di gudang',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 120, 'qty_disetujui' => 120],
                    ['type' => 'bahan_baku', 'qty' => 80, 'qty_disetujui' => 80],
                ],
            ],

            // 14. Pesanan - Mixed (Received)
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->skip(2)->first()->pesanan_id ?? $pesanans->first()->pesanan_id,
                'status' => 'diterima',
                'catatan' => 'Pengadaan dari pesanan selesai - semua barang diterima',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 100, 'qty_disetujui' => 100],
                    ['type' => 'produk', 'qty' => 50, 'qty_disetujui' => 50],
                ],
            ],

            // 15. ROP - Produk (Received)
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'diterima',
                'catatan' => 'Pengadaan produk selesai - stok sudah diupdate di sistem',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'produk', 'qty' => 75, 'qty_disetujui' => 75],
                    ['type' => 'produk', 'qty' => 60, 'qty_disetujui' => 60],
                ],
            ],

            // ============ CANCELLED STATES ============
            // 16. Cancelled from Draft
            [
                'jenis_pengadaan' => 'rop',
                'pesanan_id' => null,
                'status' => 'dibatalkan',
                'catatan' => 'Pengadaan dibatalkan dari draft - tidak diperlukan lagi',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 500, 'qty_disetujui' => null],
                ],
            ],

            // 17. Cancelled from Pending Approval
            [
                'jenis_pengadaan' => 'pesanan',
                'pesanan_id' => $pesanans->first()->pesanan_id,
                'status' => 'dibatalkan',
                'catatan' => 'Pengadaan dibatalkan saat pending approval - budget terpotong',
                'dibuat_oleh' => 'US001',
                'details' => [
                    ['type' => 'bahan_baku', 'qty' => 400, 'qty_disetujui' => null],
                    ['type' => 'produk', 'qty' => 200, 'qty_disetujui' => null],
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
