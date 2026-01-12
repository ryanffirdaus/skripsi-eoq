<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryCalculationService
{
    /**
     * Kalkulasi semua metrics untuk produk tertentu
     */
    public function calculateProdukMetrics($produkId)
    {
        $produk = Produk::findOrFail($produkId);
        
        // Hitung permintaan
        $demandData = $this->calculateProdukDemand($produkId);
        
        // Hitung waktu tunggu
        $leadTimeData = $this->calculateProdukLeadTime($produkId);
        
        // Hitung biaya
        $biayaPemesanan = $this->calculateProdukOrderingCost($produkId);
        $biayaPenyimpanan = $this->calculateStorageCost($produk->hpp_produk);
        
        // Kalkulasi Safety Stock
        $safetyStock = $this->calculateSafetyStock(
            $demandData['permintaan_harian_rata2'],
            $demandData['permintaan_harian_maksimum'],
            $leadTimeData['waktu_tunggu_rata2'],
            $leadTimeData['waktu_tunggu_maksimum']
        );
        
        // Kalkulasi ROP
        $rop = $this->calculateROP(
            $demandData['permintaan_harian_rata2'],
            $leadTimeData['waktu_tunggu_rata2'],
            $safetyStock
        );
        
        // Kalkulasi EOQ
        $eoq = $this->calculateEOQ(
            $demandData['permintaan_tahunan'],
            $biayaPemesanan,
            $biayaPenyimpanan
        );
        
        return [
            'permintaan_harian_rata2' => $demandData['permintaan_harian_rata2'],
            'permintaan_harian_maksimum' => $demandData['permintaan_harian_maksimum'],
            'permintaan_tahunan' => $demandData['permintaan_tahunan'],
            'waktu_tunggu_rata2' => $leadTimeData['waktu_tunggu_rata2'],
            'waktu_tunggu_maksimum' => $leadTimeData['waktu_tunggu_maksimum'],
            'biaya_pemesanan' => $biayaPemesanan,
            'biaya_penyimpanan' => $biayaPenyimpanan,
            'safety_stock' => $safetyStock,
            'rop' => $rop,
            'eoq' => $eoq,
        ];
    }

    /**
     * Kalkulasi semua metrics untuk bahan baku tertentu
     */
    public function calculateBahanBakuMetrics($bahanBakuId)
    {
        $bahanBaku = BahanBaku::findOrFail($bahanBakuId);
        
        // Hitung permintaan
        $demandData = $this->calculateBahanBakuDemand($bahanBakuId);
        
        // Hitung waktu tunggu
        $leadTimeData = $this->calculateBahanBakuLeadTime($bahanBakuId);
        
        // Hitung biaya
        $biayaPemesanan = $this->calculateBahanBakuOrderingCost($bahanBakuId);
        $biayaPenyimpanan = $this->calculateStorageCost($bahanBaku->harga_bahan);
        
        // Kalkulasi Safety Stock
        $safetyStock = $this->calculateSafetyStock(
            $demandData['permintaan_harian_rata2'],
            $demandData['permintaan_harian_maksimum'],
            $leadTimeData['waktu_tunggu_rata2'],
            $leadTimeData['waktu_tunggu_maksimum']
        );
        
        // Kalkulasi ROP
        $rop = $this->calculateROP(
            $demandData['permintaan_harian_rata2'],
            $leadTimeData['waktu_tunggu_rata2'],
            $safetyStock
        );
        
        // Kalkulasi EOQ
        $eoq = $this->calculateEOQ(
            $demandData['permintaan_tahunan'],
            $biayaPemesanan,
            $biayaPenyimpanan
        );
        
        return [
            'permintaan_harian_rata2' => $demandData['permintaan_harian_rata2'],
            'permintaan_harian_maksimum' => $demandData['permintaan_harian_maksimum'],
            'permintaan_tahunan' => $demandData['permintaan_tahunan'],
            'waktu_tunggu_rata2' => $leadTimeData['waktu_tunggu_rata2'],
            'waktu_tunggu_maksimum' => $leadTimeData['waktu_tunggu_maksimum'],
            'biaya_pemesanan' => $biayaPemesanan,
            'biaya_penyimpanan' => $biayaPenyimpanan,
            'safety_stock' => $safetyStock,
            'rop' => $rop,
            'eoq' => $eoq,
        ];
    }

    /**
     * Hitung permintaan produk dari pesanan_detail
     */
    protected function calculateProdukDemand($produkId)
    {
        $now = Carbon::now();
        $startDate90 = $now->copy()->subDays(90);
        $startDate365 = $now->copy()->subDays(365);

        // Permintaan tahunan (365 hari)
        $permintaanTahunan = DB::table('pesanan_detail')
            ->join('pesanan', 'pesanan_detail.pesanan_id', '=', 'pesanan.pesanan_id')
            ->where('pesanan_detail.produk_id', $produkId)
            ->where('pesanan.created_at', '>=', $startDate365)
            ->whereNull('pesanan_detail.deleted_at')
            ->whereNull('pesanan.deleted_at')
            ->sum('pesanan_detail.jumlah_produk');

        // Permintaan harian dalam 90 hari terakhir (aggregate by day)
        $dailyDemands = DB::table('pesanan_detail')
            ->join('pesanan', 'pesanan_detail.pesanan_id', '=', 'pesanan.pesanan_id')
            ->select(DB::raw('DATE(pesanan.created_at) as date'), DB::raw('SUM(pesanan_detail.jumlah_produk) as total'))
            ->where('pesanan_detail.produk_id', $produkId)
            ->where('pesanan.created_at', '>=', $startDate90)
            ->whereNull('pesanan_detail.deleted_at')
            ->whereNull('pesanan.deleted_at')
            ->groupBy(DB::raw('DATE(pesanan.created_at)'))
            ->get();

        $permintaanHarianRata2 = $dailyDemands->avg('total') ?: 0;
        $permintaanHarianMaksimum = $dailyDemands->max('total') ?: 0;

        return [
            'permintaan_tahunan' => (int) $permintaanTahunan,
            'permintaan_harian_rata2' => round($permintaanHarianRata2, 2),
            'permintaan_harian_maksimum' => (int) $permintaanHarianMaksimum,
        ];
    }

    /**
     * Hitung lead time produksi dari penugasan_produksi
     */
    protected function calculateProdukLeadTime($produkId)
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays(365);

        // Ambil data penugasan yang sudah selesai
        $leadTimes = DB::table('penugasan_produksi')
            ->join('pengadaan_detail', 'penugasan_produksi.pengadaan_detail_id', '=', 'pengadaan_detail.pengadaan_detail_id')
            ->where('pengadaan_detail.jenis_barang', 'produk')
            ->where('pengadaan_detail.barang_id', $produkId)
            ->where('penugasan_produksi.status', 'selesai')
            ->where('penugasan_produksi.created_at', '>=', $startDate)
            ->whereNull('penugasan_produksi.deleted_at')
            ->whereNull('pengadaan_detail.deleted_at')
            ->get()
            ->map(function ($item) {
                $created = Carbon::parse($item->created_at);
                $updated = Carbon::parse($item->updated_at);
                return $updated->diffInDays($created);
            });

        $waktuTungguRata2 = $leadTimes->avg() ?: 7; // Default 7 hari jika tidak ada data
        $waktuTungguMaksimum = $leadTimes->max() ?: 14; // Default 14 hari

        return [
            'waktu_tunggu_rata2' => round($waktuTungguRata2, 2),
            'waktu_tunggu_maksimum' => (int) $waktuTungguMaksimum,
        ];
    }

    /**
     * Hitung biaya pemesanan produk (rata-rata dari pengadaan_detail)
     */
    protected function calculateProdukOrderingCost($produkId)
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays(365);

        $avgCost = DB::table('pengadaan_detail')
            ->join('pengadaan', 'pengadaan_detail.pengadaan_id', '=', 'pengadaan.pengadaan_id')
            ->where('pengadaan_detail.jenis_barang', 'produk')
            ->where('pengadaan_detail.barang_id', $produkId)
            ->where('pengadaan.created_at', '>=', $startDate)
            ->whereNotNull('pengadaan_detail.biaya_pemesanan')
            ->whereNull('pengadaan.deleted_at')
            ->whereNull('pengadaan_detail.deleted_at')
            ->avg('pengadaan_detail.biaya_pemesanan');

        return $avgCost ?: 50000; // Default Rp 50.000 jika tidak ada data
    }

    /**
     * Hitung permintaan bahan baku dari pengadaan_detail
     */
    protected function calculateBahanBakuDemand($bahanBakuId)
    {
        $now = Carbon::now();
        $startDate90 = $now->copy()->subDays(90);
        $startDate365 = $now->copy()->subDays(365);

        // Permintaan tahunan (365 hari)
        $permintaanTahunan = DB::table('pengadaan_detail')
            ->join('pengadaan', 'pengadaan_detail.pengadaan_id', '=', 'pengadaan.pengadaan_id')
            ->where('pengadaan_detail.jenis_barang', 'bahan_baku')
            ->where('pengadaan_detail.barang_id', $bahanBakuId)
            ->where('pengadaan.created_at', '>=', $startDate365)
            ->whereNull('pengadaan_detail.deleted_at')
            ->whereNull('pengadaan.deleted_at')
            ->sum(DB::raw('COALESCE(pengadaan_detail.qty_disetujui, pengadaan_detail.qty_diminta)'));

        // Permintaan harian dalam 90 hari terakhir (aggregate by day)
        $dailyDemands = DB::table('pengadaan_detail')
            ->join('pengadaan', 'pengadaan_detail.pengadaan_id', '=', 'pengadaan.pengadaan_id')
            ->select(DB::raw('DATE(pengadaan.created_at) as date'), DB::raw('SUM(COALESCE(pengadaan_detail.qty_disetujui, pengadaan_detail.qty_diminta)) as total'))
            ->where('pengadaan_detail.jenis_barang', 'bahan_baku')
            ->where('pengadaan_detail.barang_id', $bahanBakuId)
            ->where('pengadaan.created_at', '>=', $startDate90)
            ->whereNull('pengadaan_detail.deleted_at')
            ->whereNull('pengadaan.deleted_at')
            ->groupBy(DB::raw('DATE(pengadaan.created_at)'))
            ->get();

        $permintaanHarianRata2 = $dailyDemands->avg('total') ?: 0;
        $permintaanHarianMaksimum = $dailyDemands->max('total') ?: 0;

        return [
            'permintaan_tahunan' => (int) $permintaanTahunan,
            'permintaan_harian_rata2' => round($permintaanHarianRata2, 2),
            'permintaan_harian_maksimum' => (int) $permintaanHarianMaksimum,
        ];
    }

    /**
     * Hitung lead time bahan baku dari pengadaan
     */
    protected function calculateBahanBakuLeadTime($bahanBakuId)
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays(365);

        // Ambil data pengadaan yang sudah diterima
        $leadTimes = DB::table('pengadaan')
            ->join('pengadaan_detail', 'pengadaan.pengadaan_id', '=', 'pengadaan_detail.pengadaan_id')
            ->where('pengadaan_detail.jenis_barang', 'bahan_baku')
            ->where('pengadaan_detail.barang_id', $bahanBakuId)
            ->where('pengadaan.status', 'diterima')
            ->where('pengadaan.created_at', '>=', $startDate)
            ->whereNull('pengadaan.deleted_at')
            ->whereNull('pengadaan_detail.deleted_at')
            ->get()
            ->map(function ($item) {
                $created = Carbon::parse($item->created_at);
                $updated = Carbon::parse($item->updated_at);
                return $updated->diffInDays($created);
            });

        $waktuTungguRata2 = $leadTimes->avg() ?: 7; // Default 7 hari jika tidak ada data
        $waktuTungguMaksimum = $leadTimes->max() ?: 14; // Default 14 hari

        return [
            'waktu_tunggu_rata2' => round($waktuTungguRata2, 2),
            'waktu_tunggu_maksimum' => (int) $waktuTungguMaksimum,
        ];
    }

    /**
     * Hitung biaya pemesanan bahan baku (rata-rata dari pengadaan_detail)
     */
    protected function calculateBahanBakuOrderingCost($bahanBakuId)
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays(365);

        $avgCost = DB::table('pengadaan_detail')
            ->join('pengadaan', 'pengadaan_detail.pengadaan_id', '=', 'pengadaan.pengadaan_id')
            ->where('pengadaan_detail.jenis_barang', 'bahan_baku')
            ->where('pengadaan_detail.barang_id', $bahanBakuId)
            ->where('pengadaan.created_at', '>=', $startDate)
            ->whereNotNull('pengadaan_detail.biaya_pemesanan')
            ->whereNull('pengadaan.deleted_at')
            ->whereNull('pengadaan_detail.deleted_at')
            ->avg('pengadaan_detail.biaya_pemesanan');

        return $avgCost ?: 50000; // Default Rp 50.000 jika tidak ada data
    }

    /**
     * Hitung biaya penyimpanan dari config
     */
    protected function calculateStorageCost($hargaBarang)
    {
        $method = config('inventory.calculation_method', 'percentage');

        if ($method === 'percentage') {
            $percentage = config('inventory.percentage', 0.20);
            return $hargaBarang * $percentage;
        }

        return config('inventory.fixed_amount', 0);
    }

    /**
     * Kalkulasi EOQ (Economic Order Quantity)
     * Formula: EOQ = √((2 × D × S) / H)
     * D = Permintaan tahunan
     * S = Biaya pemesanan
     * H = Biaya penyimpanan per unit per tahun
     */
    public function calculateEOQ($permintaanTahunan, $biayaPemesanan, $biayaPenyimpanan)
    {
        if ($permintaanTahunan <= 0 || $biayaPemesanan <= 0 || $biayaPenyimpanan <= 0) {
            return 0;
        }

        $eoq = sqrt((2 * $permintaanTahunan * $biayaPemesanan) / $biayaPenyimpanan);
        return round($eoq);
    }

    /**
     * Kalkulasi ROP (Reorder Point)
     * Formula: ROP = (Permintaan harian rata-rata × Waktu tunggu rata-rata) + Safety Stock
     */
    public function calculateROP($permintaanHarianRata2, $waktuTungguRata2, $safetyStock)
    {
        $rop = ($permintaanHarianRata2 * $waktuTungguRata2) + $safetyStock;
        return round($rop);
    }

    /**
     * Kalkulasi Safety Stock menggunakan Z-Score Method
     * Formula: SS = Z × √[(L_avg × σ_demand)² + (D_avg × σ_leadtime)²]
     */
    public function calculateSafetyStock($permintaanHarianRata2, $permintaanHarianMax, $waktuTungguRata2, $waktuTungguMax)
    {
        if ($permintaanHarianRata2 <= 0 || $waktuTungguRata2 <= 0) {
            return 0;
        }

        $zScore = config('inventory.z_score', 1.65);

        // Estimasi standard deviation dari range
        $stdDevDemand = ($permintaanHarianMax - $permintaanHarianRata2) / 1.65;
        $stdDevLeadTime = ($waktuTungguMax - $waktuTungguRata2) / 1.65;

        // Hitung combined variability
        $variance = pow($waktuTungguRata2 * $stdDevDemand, 2) + 
                   pow($permintaanHarianRata2 * $stdDevLeadTime, 2);
        $stdDevTotal = sqrt($variance);

        // Safety Stock = Z × σ_total
        $safetyStock = $zScore * $stdDevTotal;
        return max(0, round($safetyStock));
    }
}
