<?php

namespace App\Observers;

use App\Jobs\CreateAutomaticPengadaan;
use App\Models\Produk;
use App\Models\Pengadaan;
use Illuminate\Support\Facades\Log;

class ProdukObserver
{
    /**
     * Handle the Produk "created" event.
     */
    public function created(Produk $produk): void
    {
        // Check immediately after creation
        $this->checkReorderPoint($produk);
    }

    /**
     * Handle the Produk "updated" event.
     */
    public function updated(Produk $produk): void
    {
        // Check if stock was changed
        if ($produk->wasChanged('stok_produk')) {
            $oldStock = $produk->getOriginal('stok_produk');
            $newStock = $produk->stok_produk;

            Log::info("Produk stock changed: {$produk->nama_produk} from {$oldStock} to {$newStock}");

            // Only check reorder point if stock decreased
            if ($newStock < $oldStock) {
                $this->checkReorderPoint($produk);
            }
        }
    }

    /**
     * Check if reorder point is reached and create automatic pengadaan
     */
    private function checkReorderPoint(Produk $produk): void
    {
        $threshold = $produk->rop_produk + $produk->safety_stock_produk;

        // Check if stock is at or below ROP + Safety Stock
        if ($produk->stok_produk <= $threshold) {
            // Check if there's already a pending pengadaan for this item
            $existingPengadaan = Pengadaan::whereHas('detail', function ($query) use ($produk) {
                $query->where('item_type', 'produk')
                    ->where('item_id', $produk->produk_id);
            })
                ->whereIn('status', ['draft', 'pending', 'procurement_approved', 'finance_approved', 'ordered'])
                ->exists();

            if (!$existingPengadaan) {
                Log::info("Triggering automatic pengadaan for Produk: {$produk->nama_produk}, Stock: {$produk->stok_produk}, Threshold: {$threshold}");

                // Dispatch job with specific item data
                CreateAutomaticPengadaan::dispatch('produk', $produk->produk_id);
            } else {
                Log::info("Skipping automatic pengadaan for Produk: {$produk->nama_produk} - already has pending pengadaan");
            }
        }
    }

    /**
     * Handle the Produk "deleted" event.
     */
    public function deleted(Produk $produk): void
    {
        // No action needed on delete
    }

    /**
     * Handle the Produk "restored" event.
     */
    public function restored(Produk $produk): void
    {
        // Check reorder point after restoration
        $this->checkReorderPoint($produk);
    }

    /**
     * Handle the Produk "force deleted" event.
     */
    public function forceDeleted(Produk $produk): void
    {
        // No action needed on force delete
    }
}
