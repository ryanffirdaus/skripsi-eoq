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
        // Only check if stock was changed
        if ($produk->wasChanged('stok_produk')) {
            // Check if stock is below ROP and ROP > 0
            if ($produk->rop_produk > 0 && $produk->stok_produk <= $produk->rop_produk) {
                // Trigger auto procurement
                $this->pengadaanService->generateROPProcurement();
            }
        }
    }
}
