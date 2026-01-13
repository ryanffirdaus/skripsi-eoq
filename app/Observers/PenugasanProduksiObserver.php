<?php

namespace App\Observers;

use App\Models\PenugasanProduksi;
use App\Models\Produk;

class PenugasanProduksiObserver
{
    /**
     * Handle the PenugasanProduksi "updated" event.
     *
     * Ketika status berubah menjadi 'selesai', tambah stok produk
     */
    public function updated(PenugasanProduksi $penugasan): void
    {
        // Check if status changed to selesai
        if ($penugasan->isDirty('status') && $penugasan->status === 'selesai') {
            $this->handleProductionCompletion($penugasan);
        }
    }

    /**
     * Handle product stock update when production is completed
     *
     * Penugasan Produksi hanya untuk PRODUK saja, tidak ada bahan baku
     */
    private function handleProductionCompletion(PenugasanProduksi $penugasan): void
    {
        // Load pengadaan detail
        $pengadaanDetail = $penugasan->pengadaanDetail;

        if (!$pengadaanDetail) {
            return;
        }

        // Pastikan ini adalah penugasan untuk produk
        if ($pengadaanDetail->jenis_barang !== 'produk') {
            return;
        }

        // Get the product
        $produk = Produk::find($pengadaanDetail->barang_id);

        if ($produk) {
            // Add jumlah_produksi to stok_produk
            $produk->stok_produk += $penugasan->jumlah_produksi;
            
            // Auto-update HPP based on current bahan baku prices
            $this->updateProductHPP($produk);
            
            $produk->saveQuietly(); // Save without triggering events
        }
    }
    
    /**
     * Calculate and update product HPP based on current bahan baku prices
     */
    private function updateProductHPP(Produk $produk): void
    {
        // Load bahan baku relationships
        $produk->load('bahanBaku');
        
        $totalHPP = 0;
        
        foreach ($produk->bahanBaku as $bahan) {
            // Get quantity from pivot table (bahan_produksi)
            $jumlahBahan = $bahan->pivot->jumlah_bahan;
            
            // Get current price of bahan baku
            $hargaBahan = $bahan->harga_bahan;
            
            // Calculate cost for this bahan
            $totalHPP += ($jumlahBahan * $hargaBahan);
        }
        
        if ($totalHPP > 0) {
            $oldHPP = $produk->hpp_produk;
            $produk->hpp_produk = $totalHPP;
            
            \Log::info("Auto-updated HPP for {$produk->nama_produk}", [
                'produk_id' => $produk->produk_id,
                'old_hpp' => $oldHPP,
                'new_hpp' => $totalHPP,
                'penugasan_id' => $this->getCurrentPenugasanId(),
            ]);
        }
    }
    
    /**
     * Helper to get current penugasan ID for logging
     */
    private function getCurrentPenugasanId(): ?string
    {
        return null; // Will be set in context if needed
    }
}
