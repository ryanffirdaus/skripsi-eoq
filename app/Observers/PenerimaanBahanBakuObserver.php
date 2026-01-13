<?php

namespace App\Observers;

use App\Models\PenerimaanBahanBaku;
use App\Models\BahanBaku;
use Illuminate\Support\Facades\Log;

class PenerimaanBahanBakuObserver
{
    /**
     * Handle the PenerimaanBahanBaku "created" event.
     * Auto-update harga bahan baku from penerimaan
     */
    public function created(PenerimaanBahanBaku $penerimaan): void
    {
        $this->updateBahanBakuPrices($penerimaan);
    }

    /**
     * Handle the PenerimaanBahanBaku "updated" event.
     * Auto-update harga bahan baku when penerimaan is updated
     */
    public function updated(PenerimaanBahanBaku $penerimaan): void
    {
        $this->updateBahanBakuPrices($penerimaan);
    }

    /**
     * Update harga bahan baku based on penerimaan details
     */
    private function updateBahanBakuPrices(PenerimaanBahanBaku $penerimaan): void
    {
        // Load details if not already loaded
        if (!$penerimaan->relationLoaded('details')) {
            $penerimaan->load('details');
        }

        foreach ($penerimaan->details as $detail) {
            $bahanBaku = BahanBaku::find($detail->bahan_baku_id);
            
            if ($bahanBaku && $detail->harga_satuan > 0) {
                $oldPrice = $bahanBaku->harga_bahan;
                
                $bahanBaku->update([
                    'harga_bahan' => $detail->harga_satuan
                ]);

                Log::info("Auto-updated harga_bahan for {$bahanBaku->nama_bahan}", [
                    'bahan_baku_id' => $bahanBaku->bahan_baku_id,
                    'old_price' => $oldPrice,
                    'new_price' => $detail->harga_satuan,
                    'penerimaan_id' => $penerimaan->penerimaan_id,
                ]);
            }
        }
    }
}
