<?php

namespace App\Observers;

use App\Models\BahanBaku;
use App\Services\PengadaanService;

class BahanBakuObserver
{
    protected $pengadaanService;

    public function __construct(PengadaanService $pengadaanService)
    {
        $this->pengadaanService = $pengadaanService;
    }

    /**
     * Handle the BahanBaku "updated" event.
     */
    public function updated(BahanBaku $bahanBaku): void
    {
        // Only check if stock was changed
        if ($bahanBaku->wasChanged('stok_bahan')) {
            // Check if stock is below ROP and ROP > 0
            if ($bahanBaku->rop_bahan > 0 && $bahanBaku->stok_bahan <= $bahanBaku->rop_bahan) {
                // Trigger auto procurement
                $this->pengadaanService->generateROPProcurement();
            }
        }
    }
}
