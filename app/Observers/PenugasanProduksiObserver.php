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
            $produk->saveQuietly(); // Save without triggering events
        }
    }
}
