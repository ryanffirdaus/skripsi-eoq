<?php

namespace App\Http\Traits;

use App\Models\Pengadaan;
use Illuminate\Support\Facades\Auth;

/**
 * Trait untuk menangani authorization logic yang kompleks untuk Pengadaan
 * berdasarkan status dan role pengguna
 *
 * Status Pengadaan:
 * - draft: dibuat oleh staf gudang
 * - menunggu_persetujuan_gudang: menunggu approval dari manajer gudang
 * - menunggu_alokasi_pemasok: sudah disetujui gudang, siap untuk isi detail pemasok
 * - menunggu_persetujuan_pengadaan: sudah disetujui pengadaan, menunggu approval keuangan
 * - menunggu_persetujuan_keuangan: sudah disetujui keuangan, siap buat pembelian
 *
 * Role Access Logic:
 * - R02 (Staf Gudang): create draft, delete pending, view all
 * - R07 (Manajer Gudang): CRUD pending, approve pending->menunggu_alokasi_pemasok
 * - R04 (Staf Pengadaan): view menunggu_alokasi_pemasok, edit detail pemasok/harga
 * - R09 (Manajer Pengadaan): edit detail pemasok/harga, approve->menunggu_persetujuan_pengadaan
 * - R10 (Manajer Keuangan): view, approve->menunggu_persetujuan_keuangan
 * - R06 (Staf Keuangan): view only
 */
trait PengadaanAuthorization
{
    /**
     * Check if user can create pengadaan
     * Only Staf Gudang (R02) can create
     */
    public function canCreatePengadaan(): bool
    {
        return Auth::check() && in_array(Auth::user()->role_id, ['R01', 'R02', 'R07']);
    }

    /**
     * Check if user can delete pengadaan
     * - Staf Gudang (R02): delete draft/menunggu_persetujuan_gudang
     * - Manajer Gudang (R07): delete any pending status
     */
    public function canDeletePengadaan(Pengadaan $pengadaan): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $roleId = Auth::user()->role_id;
        $status = $pengadaan->status;

        // Admin bisa delete semua
        if ($roleId === 'R01') {
            return true;
        }

        // Staf Gudang: delete menunggu_persetujuan_gudang OR dibatalkan
        if ($roleId === 'R02') {
            return in_array($status, ['menunggu_persetujuan_gudang', 'dibatalkan']);
        }

        // Manajer Gudang: delete pending OR dibatalkan
        if ($roleId === 'R07') {
            return in_array($status, ['menunggu_persetujuan_gudang', 'dibatalkan']);
        }

        return false;
    }

    /**
     * Check if user can edit pengadaan status
     */
    public function canApprovePengadaan(Pengadaan $pengadaan): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $roleId = Auth::user()->role_id;
        $status = $pengadaan->status;

        // Admin bisa approve semua
        if ($roleId === 'R01') {
            return true;
        }

        // Manajer Gudang: approve menunggu_persetujuan_gudang->menunggu_alokasi_pemasok
        if ($roleId === 'R07' && $status === 'menunggu_persetujuan_gudang') {
            return true;
        }

        // Staf Pengadaan: submit menunggu_alokasi_pemasok -> menunggu_persetujuan_pengadaan (hanya jika sudah isi detail)
        if ($roleId === 'R04' && $status === 'menunggu_alokasi_pemasok') {
            return $this->isPengadaanDetailFilled($pengadaan);
        }

        // Manajer Pengadaan: approve menunggu_persetujuan_pengadaan -> menunggu_persetujuan_keuangan
        if ($roleId === 'R09' && $status === 'menunggu_persetujuan_pengadaan') {
            return true;
        }

        // Manajer Keuangan: approve menunggu_persetujuan_keuangan -> diproses
        if ($roleId === 'R10' && $status === 'menunggu_persetujuan_keuangan') {
            return true;
        }

        return false;
    }

    /**
     * Check if user can edit pengadaan (baik detail maupun status)
     * Combines both detail editing dan status change permissions
     */
    public function canEditPengadaan(Pengadaan $pengadaan): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $roleId = Auth::user()->role_id;

        // Admin bisa edit semua
        if ($roleId === 'R01') {
            return true;
        }

        // R02/R07 (Staf/Manajer Gudang): dapat edit di status menunggu_persetujuan_gudang
        if (in_array($roleId, ['R02', 'R07']) && $pengadaan->status === 'menunggu_persetujuan_gudang') {
            return true;
        }

        // R04 (Staf Pengadaan): dapat edit detail (pemasok/harga) di menunggu_alokasi_pemasok
        if ($roleId === 'R04' && $pengadaan->status === 'menunggu_alokasi_pemasok') {
            return true;
        }

        // R09 (Manajer Pengadaan): dapat edit di status menunggu_persetujuan_pengadaan
        if ($roleId === 'R09' && $pengadaan->status === 'menunggu_persetujuan_pengadaan') {
            return true;
        }

        // R10 (Manajer Keuangan): dapat edit di status menunggu_persetujuan_keuangan
        if ($roleId === 'R10' && $pengadaan->status === 'menunggu_persetujuan_keuangan') {
            return true;
        }

        return false;
    }

    /**
     * Check if user can edit pengadaan detail (pemasok/harga)
     * - Staf & Manajer Pengadaan: edit untuk status menunggu_alokasi_pemasok
     */
    public function canEditPengadaanDetail(Pengadaan $pengadaan): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $roleId = Auth::user()->role_id;
        $status = $pengadaan->status;

        // Admin bisa edit semua
        if ($roleId === 'R01') {
            return true;
        }

        // Staf Pengadaan: edit detail untuk status menunggu_alokasi_pemasok
        // (ini adalah status yang disebut "Menunggu Alokasi Pemasok" di UI)
        if ($roleId === 'R04' && $status === 'menunggu_alokasi_pemasok') {
            return true;
        }

        return false;
    }

    /**
     * Check if pengadaan detail sudah lengkap (pemasok & harga satuan terisi)
     */
    protected function isPengadaanDetailFilled(Pengadaan $pengadaan): bool
    {
        return $pengadaan->detail()
            ->where('jenis_barang', 'bahan_baku') // Hanya cek bahan baku yang butuh pemasok
            ->where(function ($query) {
                $query->whereNull('pemasok_id')
                    ->orWhereNull('harga_satuan');
            })
            ->doesntExist();
    }

    /**
     * Check if user can view pengadaan
     */
    public function canViewPengadaan(Pengadaan $pengadaan): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $roleId = Auth::user()->role_id;

        // Admin, Staf Gudang, Manajer Gudang, Staf Pengadaan, Manajer Pengadaan, Staf Keuangan, Manajer Keuangan
        return in_array($roleId, ['R01', 'R02', 'R04', 'R06', 'R07', 'R09', 'R10']);
    }
}
