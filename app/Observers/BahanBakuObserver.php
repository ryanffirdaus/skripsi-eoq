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
        // Check if stock is below ROP and ROP > 0
        if ($bahanBaku->reorder_point > 0 && $bahanBaku->stok_saat_ini <= $bahanBaku->reorder_point) {
            // Trigger auto procurement
            // We don't have a specific supplier here, so we let the service handle default or logic
            $this->pengadaanService->generateROPProcurement();
        }
    }
}
