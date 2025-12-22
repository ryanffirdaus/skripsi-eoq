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
        // Check if status changed to 'dikirim' (shipped)
        if ($pengiriman->wasChanged('status') && $pengiriman->status === 'dikirim') {
            $this->reduceStock($pengiriman);
            
            // Update Pesanan status to 'dikirim'
            if ($pengiriman->pesanan) {
                $pengiriman->pesanan->update(['status' => 'dikirim']);
                Log::info("Updated Pesanan {$pengiriman->pesanan->pesanan_id} status to 'dikirim'");
            }
        }
    }

    /**
     * Reduce stock when pengiriman is shipped
     */
    private function reduceStock(Pengiriman $pengiriman): void
    {
        Log::info("Processing stock reduction for shipped pengiriman: {$pengiriman->pengiriman_id}");

        // Load pesanan and its details with produk relation
        $pengiriman->load(['pesanan.detail.produk']);

        if (!$pengiriman->pesanan) {
            Log::warning("No pesanan found for pengiriman: {$pengiriman->pengiriman_id}");
            return;
        }

        foreach ($pengiriman->pesanan->detail as $detail) {
            if (!$detail->produk) {
                continue;
            }
            
            $produk = $detail->produk;
            $oldStock = $produk->stok_produk;
            $newStock = max(0, $oldStock - $detail->jumlah_produk);

            $produk->update(['stok_produk' => $newStock]);

            Log::info("Reduced Produk stock: {$produk->nama_produk} from {$oldStock} to {$newStock}");
        }
    }

    /**
     * Handle the Pengiriman "created" event.
     */
    public function created(Pengiriman $pengiriman): void
    {
        // Update Pesanan status when pengiriman is created
        if ($pengiriman->pesanan) {
            if ($pengiriman->status === 'dikirim') {
                // If created with status 'dikirim', reduce stock and update pesanan to 'dikirim'
                $this->reduceStock($pengiriman);
                $pengiriman->pesanan->update(['status' => 'dikirim']);
                Log::info("Pengiriman created with status 'dikirim', updated Pesanan {$pengiriman->pesanan->pesanan_id} status to 'dikirim'");
            } else {
                // Otherwise, update pesanan status to 'siap_dikirim'
                $pengiriman->pesanan->update(['status' => 'siap_dikirim']);
                Log::info("Pengiriman created, updated Pesanan {$pengiriman->pesanan->pesanan_id} status to 'siap_dikirim'");
            }
        }
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
