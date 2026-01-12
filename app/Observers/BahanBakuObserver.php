<?php

namespace App\Observers;

use App\Models\BahanBaku;
use App\Services\PengadaanService;
use App\Services\InventoryCalculationService;

class BahanBakuObserver
{
    protected $pengadaanService;
    protected $inventoryService;

    public function __construct(PengadaanService $pengadaanService, InventoryCalculationService $inventoryService)
    {
        $this->pengadaanService = $pengadaanService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Handle the BahanBaku "updated" event.
     */
    public function updated(BahanBaku $bahanBaku): void
    {
        // Only check if stock was changed
        if ($bahanBaku->wasChanged('stok_bahan')) {
            // Calculate dynamic ROP
            try {
                $metrics = $this->inventoryService->calculateBahanBakuMetrics($bahanBaku->bahan_baku_id);
                $rop = $metrics['rop'];
                
                // Check if stock is below ROP and ROP > 0
                if ($rop > 0 && $bahanBaku->stok_bahan <= $rop) {
                    // Trigger auto procurement
                    $this->pengadaanService->generateROPProcurement();
                }
            } catch (\Exception $e) {
                // Log error but don't stop execution
                \Log::error('Error calculating ROP for bahan baku: ' . $e->getMessage());
            }
        }
    }
}
