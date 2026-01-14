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
                $newPrice = $detail->harga_satuan;
                
                // Update harga
                $bahanBaku->harga_bahan = $newPrice;
                
                // Auto-update holding cost % if price changed significantly (>10%)
                if ($oldPrice > 0 && abs($newPrice - $oldPrice) / $oldPrice > 0.10) {
                    $bahanBaku->biaya_penyimpanan_persen = $this->calculateHoldingCostPercent($bahanBaku);
                    
                    Log::info("Auto-updated biaya_penyimpanan for {$bahanBaku->nama_bahan}", [
                        'bahan_baku_id' => $bahanBaku->bahan_baku_id,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                        'new_holding_cost_pct' => $bahanBaku->biaya_penyimpanan_persen,
                    ]);
                }
                
                $bahanBaku->save();

                Log::info("Auto-updated harga_bahan for {$bahanBaku->nama_bahan}", [
                    'bahan_baku_id' => $bahanBaku->bahan_baku_id,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'penerimaan_id' => $penerimaan->penerimaan_id,
                ]);
            }
        }
    }
    
    /**
     * Calculate appropriate holding cost percentage based on item value
     */
    private function calculateHoldingCostPercent(BahanBaku $bahanBaku): float
    {
        $basePercent = 20.00;
        
        // Adjust based on item value (higher value = better storage = lower %)
        if ($bahanBaku->harga_bahan > 100000) {
            $basePercent = 15.00; // Premium items get better storage
        } elseif ($bahanBaku->harga_bahan < 10000) {
            $basePercent = 25.00; // Low-value items have higher relative storage cost
        }
        
        // Future: Could adjust based on kategori if you add that column
        // match($bahanBaku->kategori) {
        //     'perishable' => $basePercent + 10.00,  // +10% for refrigeration
        //     'fragile' => $basePercent + 5.00,       // +5% for special handling
        //     default => $basePercent,
        // };
        
        return $basePercent;
    }
}
