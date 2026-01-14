<?php

namespace App\Observers;

use App\Models\Pengadaan;
use App\Models\BahanBaku;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PengadaanObserver
{
    /**
     * Handle the Pengadaan "updated" event.
     * Auto-update biaya_pemesanan when pengadaan completed
     */
    public function updated(Pengadaan $pengadaan): void
    {
        // Only when status changed to completed
        if ($pengadaan->isDirty('status') && 
            in_array($pengadaan->status, ['diterima', 'selesai'])) {
            
            $this->updateOrderingCosts($pengadaan);
        }
    }
    
    /**
     * Update ordering costs for all items in this pengadaan
     */
    private function updateOrderingCosts(Pengadaan $pengadaan): void
    {
        if (!$pengadaan->relationLoaded('detail')) {
            $pengadaan->load('detail');
        }
        
        foreach ($pengadaan->detail as $detail) {
            if ($detail->item_type === 'bahan_baku' && $detail->item_id) {
                $this->recalculateOrderingCost($detail->item_id, 'bahan_baku');
            } elseif ($detail->item_type === 'produk' && $detail->item_id) {
                $this->recalculateOrderingCost($detail->item_id, 'produk');
            }
        }
    }
    
    /**
     * Recalculate average ordering cost from recent transactions
     */
    private function recalculateOrderingCost(string $itemId, string $itemType): void
    {
        // Get rolling average dari 10 pengadaan terakhir
        $avgCost = DB::table('pengadaan')
            ->join('pengadaan_detail', 'pengadaan.pengadaan_id', '=', 'pengadaan_detail.pengadaan_id')
            ->where('pengadaan_detail.item_id', $itemId)
            ->where('pengadaan_detail.item_type', $itemType)
            ->whereIn('pengadaan.status', ['diterima', 'selesai'])
            ->whereNotNull('pengadaan.biaya_pemesanan')
            ->where('pengadaan.biaya_pemesanan', '>', 0)
            ->orderBy('pengadaan.created_at', 'desc')
            ->limit(10)
            ->avg('pengadaan.biaya_pemesanan');
        
        if ($avgCost && $avgCost > 0) {
            if ($itemType === 'bahan_baku') {
                $item = BahanBaku::find($itemId);
            } else {
                $item = Produk::find($itemId);
            }
            
            if ($item) {
                $oldCost = $item->biaya_pemesanan_per_order;
                
                $item->update([
                    'biaya_pemesanan_per_order' => round($avgCost, 2)
                ]);
                
                $itemName = $itemType === 'bahan_baku' ? $item->nama_bahan : $item->nama_produk;
                
                Log::info("Auto-updated biaya_pemesanan for {$itemName}", [
                    'item_id' => $itemId,
                    'item_type' => $itemType,
                    'old_cost' => $oldCost,
                    'new_cost' => round($avgCost, 2),
                    'from_n_transactions' => 10,
                ]);
            }
        }
    }
}
