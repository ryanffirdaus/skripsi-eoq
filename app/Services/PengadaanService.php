<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\Produk;
use App\Models\Pengadaan;
use App\Models\PengadaanDetail;
use App\Models\Pesanan;
use App\Models\PesananProduk;
use Illuminate\Support\Facades\DB;

class PengadaanService
{
    /**
     * Detect items below ROP (Reorder Point)
     */
    public function detectBelowROP()
    {
        $bahanBakuBelowROP = BahanBaku::whereRaw('stok_bahan <= rop_bahan')
            ->where('rop_bahan', '>', 0)
            ->get();

        $produkBelowROP = Produk::whereRaw('stok_produk <= rop_produk')
            ->where('rop_produk', '>', 0)
            ->get();

        return [
            'bahan_baku' => $bahanBakuBelowROP,
            'produk' => $produkBelowROP
        ];
    }

    /**
     * Generate automatic procurement based on ROP
     */
    public function generateROPProcurement($pemasokId = null)
    {
        $itemsBelowROP = $this->detectBelowROP();

        if ($itemsBelowROP['bahan_baku']->count() === 0 && $itemsBelowROP['produk']->count() === 0) {
            return null;
        }

        // Create pengadaan with correct columns matching the model
        $pengadaan = Pengadaan::create([
            'jenis_pengadaan' => 'rop',
            'status' => 'menunggu_persetujuan_gudang',
            'catatan' => 'Pengadaan otomatis berdasarkan Reorder Point (ROP)'
        ]);

        // Add bahan baku below ROP
        foreach ($itemsBelowROP['bahan_baku'] as $bahanBaku) {
            $qtyNeeded = $bahanBaku->eoq_bahan ?: max(100, $bahanBaku->rop_bahan * 2);

            PengadaanDetail::create([
                'pengadaan_id' => $pengadaan->pengadaan_id,
                'pemasok_id' => $pemasokId,
                'jenis_barang' => 'bahan_baku',
                'barang_id' => $bahanBaku->bahan_baku_id,
                'qty_diminta' => $qtyNeeded,
                'harga_satuan' => $bahanBaku->harga_bahan,
                'catatan' => "Stok saat ini ({$bahanBaku->stok_bahan}) di bawah ROP ({$bahanBaku->rop_bahan})"
            ]);
        }

        // Add produk below ROP
        foreach ($itemsBelowROP['produk'] as $produk) {
            $qtyNeeded = $produk->eoq_produk ?: max(50, $produk->rop_produk * 2);

            PengadaanDetail::create([
                'pengadaan_id' => $pengadaan->pengadaan_id,
                'pemasok_id' => $pemasokId,
                'jenis_barang' => 'produk',
                'barang_id' => $produk->produk_id,
                'qty_diminta' => $qtyNeeded,
                'harga_satuan' => $produk->hpp_produk,
                'catatan' => "Stok saat ini ({$produk->stok_produk}) di bawah ROP ({$produk->rop_produk})"
            ]);
        }

        return $pengadaan;
    }

    /**
     * Generate procurement based on pesanan
     */
    public function generatePesananProcurement($pesananId, $pemasokId)
    {
        $pesanan = Pesanan::with('produk.bahanProduksi.bahanBaku')->findOrFail($pesananId);

        // Calculate required materials
        $requiredMaterials = [];

        foreach ($pesanan->produk as $pesananProduk) {
            $produk = $pesananProduk->produk;
            $qtyDipesan = $pesananProduk->qty;

            foreach ($produk->bahanProduksi as $bahanProduksi) {
                $bahanBaku = $bahanProduksi->bahanBaku;
                $qtyRequired = $bahanProduksi->qty_dibutuhkan * $qtyDipesan;

                if (isset($requiredMaterials[$bahanBaku->bahan_baku_id])) {
                    $requiredMaterials[$bahanBaku->bahan_baku_id]['qty_needed'] += $qtyRequired;
                } else {
                    $requiredMaterials[$bahanBaku->bahan_baku_id] = [
                        'bahan_baku' => $bahanBaku,
                        'qty_needed' => $qtyRequired,
                        'stok_tersedia' => $bahanBaku->stok_bahan
                    ];
                }
            }
        }

        // Filter materials that need procurement (not enough stock)
        $needProcurement = array_filter($requiredMaterials, function ($material) {
            return $material['stok_tersedia'] < $material['qty_needed'];
        });

        if (empty($needProcurement)) {
            return null; // No procurement needed
        }

        $pengadaan = Pengadaan::create([
            'pemasok_id' => $pemasokId,
            'jenis_pengadaan' => 'pesanan',
            'pesanan_id' => $pesananId,
            'tanggal_pengadaan' => now(),
            'tanggal_dibutuhkan' => now()->parse($pesanan->tanggal_pemesanan)->addDays(3), // Need materials 3 days before delivery
            'status' => 'draft',
            'prioritas' => 'normal',
            'alasan_pengadaan' => "Pengadaan untuk memenuhi pesanan {$pesanan->pesanan_id}"
        ]);

        foreach ($needProcurement as $material) {
            $bahanBaku = $material['bahan_baku'];
            $qtyShortage = $material['qty_needed'] - $material['stok_tersedia'];
            $qtyToProcure = max($qtyShortage, $bahanBaku->eoq ?: $qtyShortage);

            PengadaanDetail::create([
                'pengadaan_id' => $pengadaan->pengadaan_id,
                'item_type' => 'bahan_baku',
                'item_id' => $bahanBaku->bahan_baku_id,
                'nama_item' => $bahanBaku->nama_bahan,
                'satuan' => $bahanBaku->satuan,
                'qty_diminta' => $qtyToProcure,
                'harga_satuan' => $bahanBaku->harga_per_unit,
                'total_harga' => $qtyToProcure * $bahanBaku->harga_per_unit,
                'alasan_kebutuhan' => "Untuk pesanan {$pesanan->pesanan_id}: dibutuhkan {$material['qty_needed']}, tersedia {$material['stok_tersedia']}"
            ]);
        }

        $pengadaan->updateTotalBiaya();

        return $pengadaan;
    }

    /**
     * Process procurement receipt
     */
    public function processProcurementReceipt($pengadaanId, $receipts)
    {
        DB::transaction(function () use ($pengadaanId, $receipts) {
            $pengadaan = Pengadaan::findOrFail($pengadaanId);

            foreach ($receipts as $receipt) {
                $detail = PengadaanDetail::findOrFail($receipt['pengadaan_detail_id']);
                $qtyReceived = $receipt['qty_received'];

                // Update detail receipt
                $detail->qty_diterima += $qtyReceived;
                $detail->save();

                // Update stock
                if ($detail->item_type === 'bahan_baku') {
                    $bahanBaku = BahanBaku::find($detail->item_id);
                    $bahanBaku->stok_bahan += $qtyReceived;
                    $bahanBaku->save();
                } elseif ($detail->item_type === 'produk') {
                    $produk = Produk::find($detail->item_id);
                    $produk->stok_produk += $qtyReceived;
                    $produk->save();
                }
            }

            // Update pengadaan status
            $allDetails = $pengadaan->detail;
            $allReceived = $allDetails->every(function ($detail) {
                return $detail->isFullyReceived();
            });

            $anyReceived = $allDetails->some(function ($detail) {
                return $detail->qty_diterima > 0;
            });

            if ($allReceived) {
                $pengadaan->status = 'received';
                $pengadaan->tanggal_delivery = now();
            } elseif ($anyReceived) {
                $pengadaan->status = 'partial_received';
            }

            $pengadaan->save();
        });
    }

    /**
     * Get procurement recommendations
     */
    public function getProcurementRecommendations()
    {
        $recommendations = [];

        // ROP-based recommendations
        $belowROP = $this->detectBelowROP();
        if ($belowROP['bahan_baku']->count() > 0 || $belowROP['produk']->count() > 0) {
            $recommendations[] = [
                'type' => 'rop',
                'title' => 'Item di Bawah ROP',
                'description' => 'Ada ' . ($belowROP['bahan_baku']->count() + $belowROP['produk']->count()) . ' item yang stoknya di bawah reorder point',
                'priority' => 'high',
                'action' => 'generate_rop_procurement'
            ];
        }

        // Pending orders that need materials
        $pendingPesanan = Pesanan::where('status', 'dikonfirmasi')
            ->whereDoesntHave('pengadaan')
            ->get();

        foreach ($pendingPesanan as $pesanan) {
            // Check if materials are sufficient for this order
            $materialsNeeded = $this->checkMaterialAvailability($pesanan->pesanan_id);
            if (!empty($materialsNeeded)) {
                $recommendations[] = [
                    'type' => 'pesanan',
                    'title' => "Pengadaan untuk Pesanan {$pesanan->pesanan_id}",
                    'description' => 'Bahan baku tidak mencukupi untuk memenuhi pesanan ini',
                    'priority' => 'normal',
                    'action' => 'generate_pesanan_procurement',
                    'pesanan_id' => $pesanan->pesanan_id
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Check material availability for a pesanan
     */
    public function checkMaterialAvailability($pesananId)
    {
        $pesanan = Pesanan::with('produk.bahanProduksi.bahanBaku')->findOrFail($pesananId);

        $requiredMaterials = [];
        foreach ($pesanan->produk as $pesananProduk) {
            $produk = $pesananProduk->produk;
            $qtyDipesan = $pesananProduk->qty;

            foreach ($produk->bahanProduksi as $bahanProduksi) {
                $bahanBaku = $bahanProduksi->bahanBaku;
                $qtyRequired = $bahanProduksi->qty_dibutuhkan * $qtyDipesan;

                if (isset($requiredMaterials[$bahanBaku->bahan_baku_id])) {
                    $requiredMaterials[$bahanBaku->bahan_baku_id] += $qtyRequired;
                } else {
                    $requiredMaterials[$bahanBaku->bahan_baku_id] = $qtyRequired;
                }
            }
        }

        $shortages = [];
        foreach ($requiredMaterials as $bahanBakuId => $qtyNeeded) {
            $bahanBaku = BahanBaku::find($bahanBakuId);
            if ($bahanBaku->stok_bahan < $qtyNeeded) {
                $shortages[] = [
                    'bahan_baku_id' => $bahanBakuId,
                    'nama_bahan' => $bahanBaku->nama_bahan,
                    'qty_needed' => $qtyNeeded,
                    'qty_available' => $bahanBaku->stok_bahan,
                    'qty_shortage' => $qtyNeeded - $bahanBaku->stok_bahan
                ];
            }
        }

        return $shortages;
    }
}
