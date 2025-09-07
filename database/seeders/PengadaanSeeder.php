<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pengadaan;
use App\Models\PengadaanDetail;
use App\Models\Supplier;
use App\Models\BahanBaku;

class PengadaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = Supplier::all();
        $bahanBakus = BahanBaku::all();

        if ($suppliers->isEmpty() || $bahanBakus->isEmpty()) {
            $this->command->info('Suppliers atau Bahan Baku tidak ditemukan. Pastikan SupplierSeeder dan BahanBakuSeeder sudah dijalankan.');
            return;
        }

        // Create sample pengadaan records
        $pengadaanData = [
            [
                'jenis_pengadaan' => 'rop',
                'tanggal_pengadaan' => now()->subDays(10),
                'status' => 'procurement_approved',
                'created_by' => 'US001',
                'details' => [
                    [
                        'item_type' => 'bahan_baku',
                        'item_id' => $bahanBakus->random()->bahan_baku_id,
                        'nama_item' => $bahanBakus->random()->nama_bahan,
                        'supplier_id' => $suppliers->first()->supplier_id,
                        'satuan' => 'kg',
                        'qty_diminta' => 100,
                        'qty_disetujui' => 100,
                        'harga_satuan' => 15000,
                        'catatan' => 'Urgent untuk produksi',
                        'alasan_kebutuhan' => 'Stok di bawah ROP'
                    ],
                    [
                        'item_type' => 'bahan_baku',
                        'item_id' => $bahanBakus->random()->bahan_baku_id,
                        'nama_item' => $bahanBakus->random()->nama_bahan,
                        'supplier_id' => $suppliers->first()->supplier_id,
                        'satuan' => 'kg',
                        'qty_diminta' => 50,
                        'qty_disetujui' => 50,
                        'harga_satuan' => 25000,
                        'catatan' => 'Stok buffer',
                        'alasan_kebutuhan' => 'Stok di bawah ROP'
                    ]
                ]
            ],
            [
                'jenis_pengadaan' => 'rop',
                'tanggal_pengadaan' => now()->subDays(5),
                'status' => 'finance_approved',
                'nomor_po' => 'PO-' . now()->format('Ymd') . '-001',
                'created_by' => 'US001',
                'details' => [
                    [
                        'item_type' => 'bahan_baku',
                        'item_id' => $bahanBakus->random()->bahan_baku_id,
                        'nama_item' => $bahanBakus->random()->nama_bahan,
                        'supplier_id' => $suppliers->first()->supplier_id,
                        'satuan' => 'kg',
                        'qty_diminta' => 200,
                        'qty_disetujui' => null,
                        'harga_satuan' => 12000,
                        'catatan' => 'Pengadaan rutin',
                        'alasan_kebutuhan' => 'Restock bulanan'
                    ]
                ]
            ],
            [
                'jenis_pengadaan' => 'pesanan',
                'tanggal_pengadaan' => now()->subDays(2),
                'status' => 'draft',
                'created_by' => 'US001',
                'details' => [
                    [
                        'item_type' => 'bahan_baku',
                        'item_id' => $bahanBakus->random()->bahan_baku_id,
                        'nama_item' => $bahanBakus->random()->nama_bahan,
                        'supplier_id' => $suppliers->first()->supplier_id,
                        'satuan' => 'kg',
                        'qty_diminta' => 75,
                        'qty_disetujui' => null,
                        'harga_satuan' => 20000,
                        'catatan' => 'Untuk pesanan urgent',
                        'alasan_kebutuhan' => 'Pesanan khusus'
                    ],
                    [
                        'item_type' => 'bahan_baku',
                        'item_id' => $bahanBakus->random()->bahan_baku_id,
                        'nama_item' => $bahanBakus->random()->nama_bahan,
                        'supplier_id' => $suppliers->first()->supplier_id,
                        'satuan' => 'kg',
                        'qty_diminta' => 30,
                        'qty_disetujui' => null,
                        'harga_satuan' => 35000,
                        'catatan' => 'Material premium',
                        'alasan_kebutuhan' => 'Pesanan khusus'
                    ]
                ]
            ],
            [
                'jenis_pengadaan' => 'rop',
                'tanggal_pengadaan' => now()->subDays(15),
                'tanggal_delivery' => now()->subDays(3),
                'status' => 'received',
                'nomor_po' => 'PO-' . now()->subDays(15)->format('Ymd') . '-001',
                'created_by' => 'US001',
                'details' => [
                    [
                        'item_type' => 'bahan_baku',
                        'item_id' => $bahanBakus->random()->bahan_baku_id,
                        'nama_item' => $bahanBakus->random()->nama_bahan,
                        'supplier_id' => $suppliers->first()->supplier_id,
                        'satuan' => 'kg',
                        'qty_diminta' => 150,
                        'qty_disetujui' => 150,
                        'qty_diterima' => 150,
                        'harga_satuan' => 18000,
                        'catatan' => 'Sudah diterima lengkap',
                        'alasan_kebutuhan' => 'Stok di bawah ROP'
                    ]
                ]
            ],
        ];

        foreach ($pengadaanData as $data) {
            $details = $data['details'];
            unset($data['details']);

            $pengadaan = Pengadaan::create($data);

            foreach ($details as $detail) {
                $detail['pengadaan_id'] = $pengadaan->pengadaan_id;
                $detail['total_harga'] = $detail['qty_diminta'] * $detail['harga_satuan'];
                PengadaanDetail::create($detail);
            }

            // Update total biaya
            $pengadaan->updateTotalBiaya();
        }

        $this->command->info('PengadaanSeeder completed successfully.');
    }
}
