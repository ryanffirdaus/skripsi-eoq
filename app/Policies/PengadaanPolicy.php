<?php

namespace App\Policies;

use App\Models\Pengadaan;
use App\Models\User;

class PengadaanPolicy
{
    /**
     * Check apakah user bisa edit supplier/pemasok
     * Admin (R01) bisa edit di status apapun
     * Staf Pengadaan (R04) atau Manajer Pengadaan (R09) hanya saat status = 'disetujui_gudang'
     */
    public function editSupplier(User $user, Pengadaan $pengadaan): bool
    {
        // Admin (R01) bisa edit di status apapun
        if ($user->role_id === 'R01') {
            return true;
        }

        // Check role
        $allowedRoles = ['R04', 'R09']; // Staf Pengadaan, Manajer Pengadaan
        if (!in_array($user->role_id, $allowedRoles)) {
            return false;
        }

        // Check status - hanya saat disetujui_gudang (alokasi pemasok)
        if ($pengadaan->status !== 'disetujui_gudang') {
            return false;
        }

        // Check jenis_barang - hanya bahan_baku yang butuh pemasok input
        // (produk flow langsung ke RnD, tidak perlu pemasok input)
        $hasBahanBaku = $pengadaan->detail()
            ->where('jenis_barang', 'bahan_baku')
            ->exists();

        return $hasBahanBaku;
    }

    /**
     * Check apakah user bisa edit harga
     * Admin (R01) bisa edit di status apapun
     * Staf/Manajer Pengadaan dan Manajer Gudang hanya saat pending/disetujui_gudang
     */
    public function editPrice(User $user, Pengadaan $pengadaan): bool
    {
        // Admin (R01) bisa edit di status apapun
        if ($user->role_id === 'R01') {
            return true;
        }

        // Check role
        $allowedRoles = ['R04', 'R07', 'R09']; // Staf Pengadaan, Manajer Gudang, Manajer Pengadaan
        if (!in_array($user->role_id, $allowedRoles)) {
            return false;
        }

        // Check status - hanya saat pending atau disetujui_gudang
        $editableStatuses = ['pending', 'disetujui_gudang'];
        return in_array($pengadaan->status, $editableStatuses);
    }

    /**
     * Check apakah user bisa approve ke status tertentu
     * Admin (R01) bisa approve ke status apapun
     */
    public function approve(User $user, Pengadaan $pengadaan, string $targetStatus): bool
    {
        // Admin (R01) bisa approve ke status apapun
        if ($user->role_id === 'R01') {
            return true;
        }

        // Setiap role hanya bisa approve ke status tertentu
        $approvalMap = [
            'disetujui_gudang' => 'R07', // Manajer Gudang
            'disetujui_pengadaan' => 'R09', // Manajer Pengadaan (only for bahan_baku)
            'disetujui_keuangan' => 'R10', // Manajer Keuangan (only for bahan_baku)
        ];

        // Check role untuk target status
        if (!isset($approvalMap[$targetStatus])) {
            return false;
        }

        if ($user->role_id !== $approvalMap[$targetStatus]) {
            return false;
        }

        // Additional check untuk produk flow
        // Produk tidak boleh sampai ke pengadaan/keuangan, harus ke RnD
        $hasProduct = $pengadaan->detail()
            ->where('jenis_barang', 'produk')
            ->exists();

        if ($hasProduct && in_array($targetStatus, ['disetujui_pengadaan', 'disetujui_keuangan'])) {
            return false;
        }

        return true;
    }

    /**
     * Check apakah pengadaan bisa di-route ke RnD
     * Hanya untuk pengadaan yang berisi produk
     */
    public function canRouteToRnd(User $user, Pengadaan $pengadaan): bool
    {
        // Hanya Manajer Gudang (R07) yang bisa route ke RnD
        if ($user->role_id !== 'R07') {
            return false;
        }

        // Hanya saat status disetujui_gudang
        if ($pengadaan->status !== 'disetujui_gudang') {
            return false;
        }

        // Check apakah ada produk
        return $pengadaan->detail()
            ->where('jenis_barang', 'produk')
            ->exists();
    }

    /**
     * Check apakah pengadaan bisa lanjut ke pengadaan/keuangan
     * Hanya untuk pengadaan yang berisi bahan_baku
     */
    public function canRouteToSupplierAllocation(User $user, Pengadaan $pengadaan): bool
    {
        // Manajer Gudang (R07) yang bisa route
        if ($user->role_id !== 'R07') {
            return false;
        }

        // Hanya saat status pending
        if ($pengadaan->status !== 'pending') {
            return false;
        }

        // Check apakah ada bahan_baku
        return $pengadaan->detail()
            ->where('jenis_barang', 'bahan_baku')
            ->exists();
    }

    /**
     * Check jenis_barang dari pengadaan
     */
    public function getItemTypes(Pengadaan $pengadaan): array
    {
        return $pengadaan->detail
            ->pluck('jenis_barang')
            ->unique()
            ->values()
            ->toArray();
    }
}
