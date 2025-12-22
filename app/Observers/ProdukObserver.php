<?php

namespace App\Observers;

use App\Models\Produk;
use App\Services\PengadaanService;
use Illuminate\Support\Facades\Log;

class ProdukObserver
{
    protected $pengadaanService;

    public function __construct(PengadaanService $pengadaanService)
    {
        $this->pengadaanService = $pengadaanService;
    }

    /**
     * Handle the Produk "updated" event.
     */
    public function updated(Produk $produk): void
    {
        Log::info("ProdukObserver: updated event triggered for {$produk->produk_id}");
        
        // Only check if stock was changed
        if ($produk->wasChanged('stok_produk')) {
            Log::info("ProdukObserver: stok_produk changed from {$produk->getOriginal('stok_produk')} to {$produk->stok_produk}");
            Log::info("ProdukObserver: ROP = {$produk->rop_produk}, Stok = {$produk->stok_produk}");
            
            // Check if stock is below ROP and ROP > 0
            if ($produk->rop_produk > 0 && $produk->stok_produk <= $produk->rop_produk) {
                Log::info("ProdukObserver: Stock below ROP, triggering auto procurement");
                // Trigger auto procurement
                $result = $this->pengadaanService->generateROPProcurement();
                Log::info("ProdukObserver: generateROPProcurement result: " . ($result ? $result->pengadaan_id : 'null'));
            } else {
                Log::info("ProdukObserver: Stock not below ROP, no procurement needed");
            }
        } else {
            Log::info("ProdukObserver: stok_produk was not changed");
        }
    }
}
