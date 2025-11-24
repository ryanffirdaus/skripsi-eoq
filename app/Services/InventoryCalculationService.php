<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\Produk;
use App\Models\PesananDetail;
use App\Models\PenugasanProduksi;
use App\Models\Pembelian;
use App\Models\Pengadaan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryCalculationService
{
    /**
     * Update metrics for all items
     */
    public function updateAllMetrics()
    {
        $this->updateBahanBakuMetrics();
        $this->updateProdukMetrics();
    }

    /**
     * Update metrics for Bahan Baku
     */
    public function updateBahanBakuMetrics()
    {
        $bahanBakuList = BahanBaku::all();

        foreach ($bahanBakuList as $bahan) {
            // 1. Calculate Demand Stats (Usage in Production)
            $demandStats = $this->calculateBahanBakuDemandStats($bahan->bahan_baku_id);
            
            // 2. Calculate Lead Time Stats (From Procurement/Purchase)
            $leadTimeStats = $this->calculateLeadTimeStats($bahan->bahan_baku_id, 'bahan_baku');

            // Update stats in DB
            $bahan->permintaan_harian_rata2_bahan = $demandStats['avg_daily'];
            $bahan->permintaan_harian_maksimum_bahan = $demandStats['max_daily'];
            $bahan->permintaan_tahunan = $demandStats['annual'];
            
            $bahan->waktu_tunggu_rata2_bahan = $leadTimeStats['avg_lead_time'];
            $bahan->waktu_tunggu_maksimum_bahan = $leadTimeStats['max_lead_time'];

            // 3. Calculate SS, ROP, EOQ using Z-Score Method (95% Service Level)
            // Safety Stock = Z × σ × √(Lead Time)
            // Z = 1.65 for 95% service level
            // σ (standard deviation) estimated from range: (max - avg) / 1.65
            
            $zScore = 1.65; // 95% service level
            
            // Estimate standard deviations from the range
            $stdDevDemand = $demandStats['max_daily'] > $demandStats['avg_daily'] 
                ? ($demandStats['max_daily'] - $demandStats['avg_daily']) / 1.65 
                : 0;
            $stdDevLeadTime = $leadTimeStats['max_lead_time'] > $leadTimeStats['avg_lead_time']
                ? ($leadTimeStats['max_lead_time'] - $leadTimeStats['avg_lead_time']) / 1.65
                : 0;
            
            // Calculate combined variability during lead time
            // Formula: √[(L_avg × σ_demand)² + (D_avg × σ_leadtime)²]
            $variance = pow($leadTimeStats['avg_lead_time'] * $stdDevDemand, 2) + 
                       pow($demandStats['avg_daily'] * $stdDevLeadTime, 2);
            $stdDevTotal = sqrt($variance);
            
            // Safety Stock = Z × σ_total
            $ss = $zScore * $stdDevTotal;
            $bahan->safety_stock_bahan = max(0, round($ss));

            // ROP = (Avg Daily Usage * Avg Lead Time) + Safety Stock
            $rop = ($demandStats['avg_daily'] * $leadTimeStats['avg_lead_time']) + $bahan->safety_stock_bahan;
            $bahan->rop_bahan = max(0, $rop);

            // EOQ = sqrt(2 * D * S / H)
            // D = Annual Demand
            // S = Ordering Cost (Biaya Pemesanan)
            // H = Holding Cost (Biaya Penyimpanan)
            if ($bahan->biaya_penyimpanan_bahan > 0) {
                $eoq = sqrt((2 * $bahan->permintaan_tahunan * $bahan->biaya_pemesanan_bahan) / $bahan->biaya_penyimpanan_bahan);
                $bahan->eoq_bahan = ceil($eoq);
            }

            $bahan->saveQuietly(); // Avoid triggering observers loop
        }
    }

    /**
     * Update metrics for Produk
     */
    public function updateProdukMetrics()
    {
        $produkList = Produk::all();

        foreach ($produkList as $produk) {
            // 1. Calculate Demand Stats (Sales in Pesanan)
            $demandStats = $this->calculateProdukDemandStats($produk->produk_id);
            
            // 2. Calculate Lead Time Stats (From Procurement/Purchase if applicable, or Production Time)
            // For products, lead time might be production time or procurement time if outsourced.
            // Assuming procurement for now as per schema having procurement fields.
            $leadTimeStats = $this->calculateLeadTimeStats($produk->produk_id, 'produk');

            // Update stats in DB
            $produk->permintaan_harian_rata2_produk = $demandStats['avg_daily'];
            $produk->permintaan_harian_maksimum_produk = $demandStats['max_daily'];
            $produk->permintaan_tahunan = $demandStats['annual'];
            
            $produk->waktu_tunggu_rata2_produk = $leadTimeStats['avg_lead_time'];
            $produk->waktu_tunggu_maksimum_produk = $leadTimeStats['max_lead_time'];

            // 3. Calculate SS, ROP, EOQ using Z-Score Method (95% Service Level)
            // Safety Stock = Z × σ × √(Lead Time)
            // Z = 1.65 for 95% service level
            // σ (standard deviation) estimated from range: (max - avg) / 1.65
            
            $zScore = 1.65; // 95% service level
            
            // Estimate standard deviations from the range
            $stdDevDemand = $demandStats['max_daily'] > $demandStats['avg_daily'] 
                ? ($demandStats['max_daily'] - $demandStats['avg_daily']) / 1.65 
                : 0;
            $stdDevLeadTime = $leadTimeStats['max_lead_time'] > $leadTimeStats['avg_lead_time']
                ? ($leadTimeStats['max_lead_time'] - $leadTimeStats['avg_lead_time']) / 1.65
                : 0;
            
            // Calculate combined variability during lead time
            // Formula: √[(L_avg × σ_demand)² + (D_avg × σ_leadtime)²]
            $variance = pow($leadTimeStats['avg_lead_time'] * $stdDevDemand, 2) + 
                       pow($demandStats['avg_daily'] * $stdDevLeadTime, 2);
            $stdDevTotal = sqrt($variance);
            
            // Safety Stock = Z × σ_total
            $ss = $zScore * $stdDevTotal;
            $produk->safety_stock_produk = max(0, round($ss));

            $rop = ($demandStats['avg_daily'] * $leadTimeStats['avg_lead_time']) + $produk->safety_stock_produk;
            $produk->rop_produk = max(0, $rop);

            if ($produk->biaya_penyimpanan_produk > 0) {
                $eoq = sqrt((2 * $produk->permintaan_tahunan * $produk->biaya_pemesanan_produk) / $produk->biaya_penyimpanan_produk);
                $produk->eoq_produk = ceil($eoq);
            }

            $produk->saveQuietly();
        }
    }

    private function calculateBahanBakuDemandStats($bahanBakuId)
    {
        // Look back 90 days
        $startDate = Carbon::now()->subDays(90);
        
        // Get daily usage from PenugasanProduksi (via PengadaanDetail -> BahanBaku? No, Penugasan is for production)
        // Actually, BahanBaku usage is recorded when PenugasanProduksi is completed?
        // Or maybe we should look at `PenggunaanBahanBaku` if it exists?
        // Let's assume usage is derived from `PenugasanProduksi` which produces `Produk`.
        // We need to map `Produk` produced -> `BahanBaku` used.
        
        // Alternative: Use `PenerimaanBahanBaku` as "Supply" and assume "Demand" is what leaves.
        // But better to use actual consumption.
        // Let's check if there is a direct usage table. If not, calculate from `Pesanan` of products that use this material.
        
        // Simplified approach: Calculate based on `Pesanan` (Sales) of products using this material.
        // 1. Find products using this material
        // 2. Sum daily sales of those products * qty needed per product
        
        $usageData = DB::table('pesanan_detail')
            ->join('pesanan', 'pesanan.pesanan_id', '=', 'pesanan_detail.pesanan_id')
            ->join('bahan_produksi', 'bahan_produksi.produk_id', '=', 'pesanan_detail.produk_id')
            ->where('bahan_produksi.bahan_baku_id', $bahanBakuId)
            ->where('pesanan.tanggal_pemesanan', '>=', $startDate)
            ->where('pesanan.status', '!=', 'dibatalkan')
            ->select(
                DB::raw('DATE(pesanan.tanggal_pemesanan) as date'),
                DB::raw('SUM(pesanan_detail.jumlah_produk * bahan_produksi.jumlah_bahan_baku) as total_usage')
            )
            ->groupBy('date')
            ->get();

        return $this->processDemandData($usageData);
    }

    private function calculateProdukDemandStats($produkId)
    {
        $startDate = Carbon::now()->subDays(90);

        $salesData = DB::table('pesanan_detail')
            ->join('pesanan', 'pesanan.pesanan_id', '=', 'pesanan_detail.pesanan_id')
            ->where('pesanan_detail.produk_id', $produkId)
            ->where('pesanan.tanggal_pemesanan', '>=', $startDate)
            ->where('pesanan.status', '!=', 'dibatalkan')
            ->select(
                DB::raw('DATE(pesanan.tanggal_pemesanan) as date'),
                DB::raw('SUM(pesanan_detail.jumlah_produk) as total_usage')
            )
            ->groupBy('date')
            ->get();

        return $this->processDemandData($salesData);
    }

    private function processDemandData($data)
    {
        if ($data->isEmpty()) {
            return [
                'avg_daily' => 0,
                'max_daily' => 0,
                'annual' => 0
            ];
        }

        $avgDaily = $data->avg('total_usage');
        $maxDaily = $data->max('total_usage');
        $annual = $avgDaily * 365;

        return [
            'avg_daily' => $avgDaily,
            'max_daily' => $maxDaily,
            'annual' => $annual
        ];
    }

    private function calculateLeadTimeStats($itemId, $type)
    {
        // Calculate lead time: Time from Pengadaan creation to PenerimaanBahanBaku creation
        // Flow: Pengadaan -> Pembelian -> Penerimaan
        
        $startDate = Carbon::now()->subDays(180); // Look back 6 months

        $leadTimes = DB::table('penerimaan_bahan_baku')
            ->join('pembelian_detail', 'pembelian_detail.pembelian_detail_id', '=', 'penerimaan_bahan_baku.pembelian_detail_id')
            ->join('pengadaan_detail', 'pengadaan_detail.pengadaan_detail_id', '=', 'pembelian_detail.pengadaan_detail_id')
            ->join('pengadaan', 'pengadaan.pengadaan_id', '=', 'pengadaan_detail.pengadaan_id')
            ->where('pengadaan_detail.barang_id', $itemId)
            ->where('pengadaan_detail.jenis_barang', $type)
            ->where('penerimaan_bahan_baku.created_at', '>=', $startDate)
            ->select(DB::raw('DATEDIFF(penerimaan_bahan_baku.created_at, pengadaan.created_at) as lead_time'))
            ->get();

        if ($leadTimes->isEmpty()) {
            // Default values if no history (e.g., 7 days avg, 14 days max)
            return [
                'avg_lead_time' => 7,
                'max_lead_time' => 14
            ];
        }

        return [
            'avg_lead_time' => $leadTimes->avg('lead_time'),
            'max_lead_time' => $leadTimes->max('lead_time')
        ];
    }
}
