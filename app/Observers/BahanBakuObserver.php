<?php

namespace App\Observers;

use App\Jobs\CreateAutomaticPengadaan;
use App\Models\BahanBaku;
use App\Models\Pengadaan;
use Illuminate\Support\Facades\Log;

class BahanBakuObserver
{
    /**
     * Handle the BahanBaku "created" event.
     */
    public function created(BahanBaku $bahanBaku): void
    {
        // Check immediately after creation
        $this->checkReorderPoint($bahanBaku);
    }

    /**
     * Handle the BahanBaku "updated" event.
     */
    public function updated(BahanBaku $bahanBaku): void
    {
        // Check if stock was changed
        if ($bahanBaku->wasChanged('stok_bahan')) {
            $oldStock = $bahanBaku->getOriginal('stok_bahan');
            $newStock = $bahanBaku->stok_bahan;

            Log::info("BahanBaku stock changed: {$bahanBaku->nama_bahan} from {$oldStock} to {$newStock}");

            // Only check reorder point if stock decreased
            if ($newStock < $oldStock) {
                $this->checkReorderPoint($bahanBaku);
            }
        }
    }

    /**
     * Check if reorder point is reached and create automatic pengadaan
     */
    private function checkReorderPoint(BahanBaku $bahanBaku): void
    {
        $threshold = $bahanBaku->rop_bahan + $bahanBaku->safety_stock_bahan;

        // Check if stock is at or below ROP + Safety Stock
        if ($bahanBaku->stok_bahan <= $threshold) {
            // Check if there's already a pending pengadaan for this item
            $existingPengadaan = Pengadaan::whereHas('detail', function ($query) use ($bahanBaku) {
                $query->where('item_type', 'bahan_baku')
                    ->where('item_id', $bahanBaku->bahan_baku_id);
            })
                ->whereIn('status', ['draft', 'pending', 'procurement_approved', 'finance_approved', 'ordered'])
                ->exists();

            if (!$existingPengadaan) {
                Log::info("Triggering automatic pengadaan for BahanBaku: {$bahanBaku->nama_bahan}, Stock: {$bahanBaku->stok_bahan}, Threshold: {$threshold}");

                // Dispatch job with specific item data
                CreateAutomaticPengadaan::dispatch('bahan_baku', $bahanBaku->bahan_baku_id);
            } else {
                Log::info("Skipping automatic pengadaan for BahanBaku: {$bahanBaku->nama_bahan} - already has pending pengadaan");
            }
        }
    }

    /**
     * Handle the BahanBaku "deleted" event.
     */
    public function deleted(BahanBaku $bahanBaku): void
    {
        // No action needed on delete
    }

    /**
     * Handle the BahanBaku "restored" event.
     */
    public function restored(BahanBaku $bahanBaku): void
    {
        // Check reorder point after restoration
        $this->checkReorderPoint($bahanBaku);
    }

    /**
     * Handle the BahanBaku "force deleted" event.
     */
    public function forceDeleted(BahanBaku $bahanBaku): void
    {
        // No action needed on force delete
    }
}
