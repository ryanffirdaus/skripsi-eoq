<?php

namespace App\Observers;

use App\Models\Produk;
use App\Services\PengadaanService;

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
        // Check if stock is below ROP and ROP > 0
        if ($produk->reorder_point > 0 && $produk->stok_saat_ini <= $produk->reorder_point) {
            // Trigger auto procurement
            $this->pengadaanService->generateROPProcurement();
        }
    }
}
