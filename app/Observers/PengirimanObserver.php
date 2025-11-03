<?php

namespace App\Observers;

use App\Models\Pengiriman;
use App\Models\BahanBaku;
use App\Models\Produk;
use Illuminate\Support\Facades\Log;

class PengirimanObserver
{
    /**
     * Handle the Pengiriman "updated" event.
     */
    public function updated(Pengiriman $pengiriman): void
    {
        // Check if status changed to 'diterima' (delivered)
        if ($pengiriman->wasChanged('status') && $pengiriman->status === 'diterima') {
            $this->reduceStock($pengiriman);
        }
    }

    /**
     * Reduce stock when pengiriman is delivered
     */
    private function reduceStock(Pengiriman $pengiriman): void
    {
        Log::info("Processing stock reduction for delivered pengiriman: {$pengiriman->pengiriman_id}");

        // Load pesanan and its details
        $pengiriman->load(['pesanan.produk']);

        if (!$pengiriman->pesanan) {
            Log::warning("No pesanan found for pengiriman: {$pengiriman->pengiriman_id}");
            return;
        }

        foreach ($pengiriman->pesanan->produk as $produk) {
            $oldStock = $produk->stok_produk;
            $newStock = max(0, $oldStock - $produk->pivot->jumlah_produk);

            $produk->update(['stok_produk' => $newStock]);

            Log::info("Reduced Produk stock: {$produk->nama_produk} from {$oldStock} to {$newStock}");
        }
    }

    /**
     * Handle the Pengiriman "created" event.
     */
    public function created(Pengiriman $pengiriman): void
    {
        // No action needed on creation
    }

    /**
     * Handle the Pengiriman "deleted" event.
     */
    public function deleted(Pengiriman $pengiriman): void
    {
        // No action needed on deletion
    }

    /**
     * Handle the Pengiriman "restored" event.
     */
    public function restored(Pengiriman $pengiriman): void
    {
        // No action needed on restoration
    }

    /**
     * Handle the Pengiriman "force deleted" event.
     */
    public function forceDeleted(Pengiriman $pengiriman): void
    {
        // No action needed on force deletion
    }
}
