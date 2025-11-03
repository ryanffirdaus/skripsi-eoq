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
        $bahanBakuBelowROP = BahanBaku::whereRaw('stok_saat_ini <= reorder_point')
            ->where('reorder_point', '>', 0)
            ->get();

        $produkBelowROP = Produk::whereRaw('stok_saat_ini <= reorder_point')
            ->where('reorder_point', '>', 0)
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

        if (empty($itemsBelowROP['bahan_baku']->count()) && empty($itemsBelowROP['produk']->count())) {
            return null;
        }

        $pengadaan = Pengadaan::create([
            'pemasok_id' => $pemasokId ?? 'PMS0000001', // Default pemasok
            'jenis_pengadaan' => 'rop',
            'tanggal_pengadaan' => now(),
            'tanggal_dibutuhkan' => now()->addDays(7), // 1 week lead time
            'status' => 'draft',
            'prioritas' => 'high',
            'alasan_pengadaan' => 'Pengadaan otomatis berdasarkan Reorder Point (ROP)'
        ]);

        // Add bahan baku below ROP
        foreach ($itemsBelowROP['bahan_baku'] as $bahanBaku) {
            $qtyNeeded = $bahanBaku->eoq ?: max(100, $bahanBaku->reorder_point * 2);

            PengadaanDetail::create([
                'pengadaan_id' => $pengadaan->pengadaan_id,
                'item_type' => 'bahan_baku',
                'item_id' => $bahanBaku->bahan_baku_id,
                'nama_item' => $bahanBaku->nama_bahan,
                'satuan' => $bahanBaku->satuan,
                'qty_diminta' => $qtyNeeded,
                'harga_satuan' => $bahanBaku->harga_per_unit,
                'total_harga' => $qtyNeeded * $bahanBaku->harga_per_unit,
                'alasan_kebutuhan' => "Stok saat ini ({$bahanBaku->stok_saat_ini}) di bawah ROP ({$bahanBaku->reorder_point})"
            ]);
        }

        // Add produk below ROP
        foreach ($itemsBelowROP['produk'] as $produk) {
            $qtyNeeded = $produk->eoq ?: max(50, $produk->reorder_point * 2);

            PengadaanDetail::create([
                'pengadaan_id' => $pengadaan->pengadaan_id,
                'item_type' => 'produk',
                'item_id' => $produk->produk_id,
                'nama_item' => $produk->nama_produk,
                'satuan' => $produk->satuan,
                'qty_diminta' => $qtyNeeded,
                'harga_satuan' => $produk->harga_jual * 0.7, // Assume 70% of selling price as procurement cost
                'total_harga' => $qtyNeeded * ($produk->harga_jual * 0.7),
                'alasan_kebutuhan' => "Stok saat ini ({$produk->stok_saat_ini}) di bawah ROP ({$produk->reorder_point})"
            ]);
        }

        $pengadaan->updateTotalBiaya();

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
                        'stok_tersedia' => $bahanBaku->stok_saat_ini
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
                    $bahanBaku->stok_saat_ini += $qtyReceived;
                    $bahanBaku->save();
                } elseif ($detail->item_type === 'produk') {
                    $produk = Produk::find($detail->item_id);
                    $produk->stok_saat_ini += $qtyReceived;
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
            if ($bahanBaku->stok_saat_ini < $qtyNeeded) {
                $shortages[] = [
                    'bahan_baku_id' => $bahanBakuId,
                    'nama_bahan' => $bahanBaku->nama_bahan,
                    'qty_needed' => $qtyNeeded,
                    'qty_available' => $bahanBaku->stok_saat_ini,
                    'qty_shortage' => $qtyNeeded - $bahanBaku->stok_saat_ini
                ];
            }
        }

        return $shortages;
    }
}
