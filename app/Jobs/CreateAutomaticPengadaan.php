<?php

namespace App\Jobs;

use App\Models\BahanBaku;
use App\Models\Pengadaan;
use App\Models\PengadaanDetail;
use App\Models\Supplier;
use App\Models\Produk;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreateAutomaticPengadaan implements ShouldQueue
{
    use Queueable;

    protected $itemType;
    protected $itemId;

    /**
     * Create a new job instance.
     */
    public function __construct($itemType = null, $itemId = null)
    {
        $this->itemType = $itemType;
        $this->itemId = $itemId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting automatic pengadaan creation job', [
            'item_type' => $this->itemType,
            'item_id' => $this->itemId
        ]);

        if ($this->itemType && $this->itemId) {
            // Process specific item
            if ($this->itemType === 'bahan_baku') {
                $bahanBaku = BahanBaku::find($this->itemId);
                if ($bahanBaku) {
                    $this->createPengadaanForBahanBaku($bahanBaku);
                }
            } elseif ($this->itemType === 'produk') {
                $produk = Produk::find($this->itemId);
                if ($produk) {
                    $this->createPengadaanForProduk($produk);
                }
            }
        } else {
            // Process all items (for cron job compatibility)
            $this->checkBahanBakuReorder();
            $this->checkProdukReorder();
        }

        Log::info('Automatic pengadaan creation job completed');
    }

    /**
     * Check bahan baku yang stoknya di bawah ROP + Safety Stock
     */
    private function checkBahanBakuReorder()
    {
        $bahanBakuList = BahanBaku::whereRaw('stok_bahan <= (rop_bahan + safety_stock_bahan)')
            ->where('stok_bahan', '>', 0) // Masih ada stok tapi sudah di bawah threshold
            ->get();

        foreach ($bahanBakuList as $bahanBaku) {
            // Check apakah sudah ada pengadaan pending untuk bahan baku ini
            $existingPengadaan = Pengadaan::whereHas('detail', function ($query) use ($bahanBaku) {
                $query->where('item_type', 'bahan_baku')
                    ->where('item_id', $bahanBaku->bahan_baku_id);
            })
                ->whereIn('status', ['draft', 'pending', 'approved', 'ordered'])
                ->exists();

            if (!$existingPengadaan) {
                $this->createPengadaanForBahanBaku($bahanBaku);
            }
        }
    }

    /**
     * Check produk yang stoknya di bawah ROP + Safety Stock
     */
    private function checkProdukReorder()
    {
        $produkList = Produk::whereRaw('stok_produk <= (rop_produk + safety_stock_produk)')
            ->where('stok_produk', '>', 0) // Masih ada stok tapi sudah di bawah threshold
            ->get();

        foreach ($produkList as $produk) {
            // Check apakah sudah ada pengadaan pending untuk produk ini
            $existingPengadaan = Pengadaan::whereHas('detail', function ($query) use ($produk) {
                $query->where('item_type', 'produk')
                    ->where('item_id', $produk->produk_id);
            })
                ->whereIn('status', ['draft', 'pending', 'approved', 'ordered'])
                ->exists();

            if (!$existingPengadaan) {
                $this->createPengadaanForProduk($produk);
            }
        }
    }

    /**
     * Buat pengadaan otomatis untuk bahan baku
     */
    private function createPengadaanForBahanBaku(BahanBaku $bahanBaku)
    {
        // Ambil supplier default atau yang paling sering digunakan untuk bahan baku ini
        $supplier = $this->getDefaultSupplier();

        if (!$supplier) {
            Log::warning("No supplier available for automatic pengadaan of bahan baku: {$bahanBaku->nama_bahan}");
            return;
        }

        $threshold = $bahanBaku->rop_bahan + $bahanBaku->safety_stock_bahan;

        $pengadaan = Pengadaan::create([
            'supplier_id' => $supplier->supplier_id,
            'jenis_pengadaan' => 'rop',
            'tanggal_pengadaan' => Carbon::today(),
            'tanggal_dibutuhkan' => Carbon::today()->addDays(3), // 3 hari dari sekarang
            'prioritas' => $this->getPrioritas($bahanBaku->stok_bahan, $bahanBaku->rop_bahan, $bahanBaku->safety_stock_bahan),
            'alasan_pengadaan' => "Pengadaan otomatis karena stok {$bahanBaku->nama_bahan} mencapai ROP ({$bahanBaku->stok_bahan} <= {$threshold})",
            'status' => 'pending', // Langsung pending untuk review
        ]);

        // Buat detail pengadaan dengan qty berdasarkan EOQ
        PengadaanDetail::create([
            'pengadaan_id' => $pengadaan->pengadaan_id,
            'item_type' => 'bahan_baku',
            'item_id' => $bahanBaku->bahan_baku_id,
            'nama_item' => $bahanBaku->nama_bahan,
            'satuan' => $bahanBaku->satuan_bahan,
            'qty_diminta' => $bahanBaku->eoq_bahan, // Gunakan EOQ
            'harga_satuan' => $bahanBaku->harga_bahan,
            'total_harga' => $bahanBaku->eoq_bahan * $bahanBaku->harga_bahan,
            'catatan' => 'Pengadaan otomatis berdasarkan EOQ'
        ]);

        $pengadaan->updateTotalBiaya();

        Log::info("Created automatic pengadaan for bahan baku: {$bahanBaku->nama_bahan}, EOQ: {$bahanBaku->eoq_bahan}, Current Stock: {$bahanBaku->stok_bahan}");
    }

    /**
     * Buat pengadaan otomatis untuk produk
     */
    private function createPengadaanForProduk(Produk $produk)
    {
        // Ambil supplier default atau yang paling sering digunakan untuk produk ini
        $supplier = $this->getDefaultSupplier();

        if (!$supplier) {
            Log::warning("No supplier available for automatic pengadaan of produk: {$produk->nama_produk}");
            return;
        }

        $thresholdProduk = $produk->rop_produk + $produk->safety_stock_produk;

        $pengadaan = Pengadaan::create([
            'supplier_id' => $supplier->supplier_id,
            'jenis_pengadaan' => 'rop',
            'tanggal_pengadaan' => Carbon::today(),
            'tanggal_dibutuhkan' => Carbon::today()->addDays(3), // 3 hari dari sekarang
            'prioritas' => $this->getPrioritas($produk->stok_produk, $produk->rop_produk, $produk->safety_stock_produk),
            'alasan_pengadaan' => "Pengadaan otomatis karena stok {$produk->nama_produk} mencapai ROP ({$produk->stok_produk} <= {$thresholdProduk})",
            'status' => 'pending', // Langsung pending untuk review
        ]);

        // Buat detail pengadaan dengan qty berdasarkan EOQ
        PengadaanDetail::create([
            'pengadaan_id' => $pengadaan->pengadaan_id,
            'item_type' => 'produk',
            'item_id' => $produk->produk_id,
            'nama_item' => $produk->nama_produk,
            'satuan' => $produk->satuan_produk,
            'qty_diminta' => $produk->eoq_produk, // Gunakan EOQ
            'harga_satuan' => $produk->hpp_produk,
            'total_harga' => $produk->eoq_produk * $produk->hpp_produk,
            'catatan' => 'Pengadaan otomatis berdasarkan EOQ'
        ]);

        $pengadaan->updateTotalBiaya();

        Log::info("Created automatic pengadaan for produk: {$produk->nama_produk}, EOQ: {$produk->eoq_produk}, Current Stock: {$produk->stok_produk}");
    }

    /**
     * Dapatkan supplier default untuk pengadaan otomatis
     */
    private function getDefaultSupplier()
    {
        // Ambil supplier aktif yang paling sering digunakan, atau yang pertama jika belum ada
        return Supplier::active()
            ->withCount(['pengadaan' => function ($query) {
                $query->whereIn('status', ['received', 'partial_received']);
            }])
            ->orderByDesc('pengadaan_count')
            ->first();
    }

    /**
     * Tentukan prioritas berdasarkan tingkat stok
     */
    private function getPrioritas($currentStock, $rop, $safetyStock)
    {
        $threshold = $rop + $safetyStock;
        $criticalThreshold = $safetyStock;

        if ($currentStock <= $criticalThreshold) {
            return 'urgent'; // Stok di bawah safety stock
        } elseif ($currentStock <= $threshold * 0.5) {
            return 'high'; // Stok sangat rendah
        } else {
            return 'normal'; // Stok rendah tapi masih aman
        }
    }
}
