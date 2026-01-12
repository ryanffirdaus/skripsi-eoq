<?php

namespace App\Observers;

use App\Models\Produk;
use App\Services\PengadaanService;
use App\Services\InventoryCalculationService;
use Illuminate\Support\Facades\Log;

class ProdukObserver
{
    protected $pengadaanService;
    protected $inventoryService;

    public function __construct(PengadaanService $pengadaanService, InventoryCalculationService $inventoryService)
    {
        $this->pengadaanService = $pengadaanService;
        $this->inventoryService = $inventoryService;
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
            
            // Calculate dynamic ROP
            try {
                $metrics = $this->inventoryService->calculateProdukMetrics($produk->produk_id);
                $rop = $metrics['rop'];
                
                Log::info("ProdukObserver: Dynamic ROP = {$rop}, Stok = {$produk->stok_produk}");
                
                // Check if stock is below ROP and ROP > 0
                if ($rop > 0 && $produk->stok_produk <= $rop) {
                    Log::info("ProdukObserver: Stock below ROP, triggering auto procurement");
                    // Trigger auto procurement
                    $result = $this->pengadaanService->generateROPProcurement();
                    Log::info("ProdukObserver: generateROPProcurement result: " . ($result ? $result->pengadaan_id : 'null'));
                } else {
                    Log::info("ProdukObserver: Stock not below ROP, no procurement needed");
                }
            } catch (\Exception $e) {
                Log::error('Error calculating ROP for produk: ' . $e->getMessage());
            }
        } else {
            Log::info("ProdukObserver: stok_produk was not changed");
        }
    }
}
