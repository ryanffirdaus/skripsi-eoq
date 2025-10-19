<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PenugasanProduksi;

class PenugasanProduksiPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Semua user bisa lihat list (tapi filtered by role di controller)
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PenugasanProduksi $penugasan): bool
    {
        // Staf RnD (R03): hanya bisa lihat tugas mereka sendiri
        if ($user->role_id === 'R03') {
            return $penugasan->user_id === $user->user_id;
        }

        // Admin dan Manajer RnD: bisa lihat semua
        if (in_array($user->role_id, ['R01', 'R09'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admin dan Manajer RnD bisa membuat penugasan
        return in_array($user->role_id, ['R01', 'R09']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PenugasanProduksi $penugasan): bool
    {
        // Tidak bisa update penugasan yang sudah final (completed, cancelled)
        if (in_array($penugasan->status, ['completed', 'cancelled'])) {
            return false;
        }

        // Staf RnD (R03): hanya bisa update tugas mereka sendiri
        if ($user->role_id === 'R03') {
            return $penugasan->user_id === $user->user_id;
        }

        // Admin dan Manajer RnD: bisa update semua
        return in_array($user->role_id, ['R01', 'R09']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PenugasanProduksi $penugasan): bool
    {
        // Tidak bisa delete penugasan yang sudah completed
        if ($penugasan->status === 'completed') {
            return false;
        }

        // Hanya admin dan manajer RnD yang bisa delete
        return in_array($user->role_id, ['R01', 'R09']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PenugasanProduksi $penugasan): bool
    {
        // Hanya admin dan manajer RnD yang bisa restore
        return in_array($user->role_id, ['R01', 'R09']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PenugasanProduksi $penugasan): bool
    {
        // Hanya admin yang bisa force delete
        return $user->role_id === 'R01';
    }

    /**
     * Determine whether the user can update status.
     *
     * Staf RnD: hanya bisa update status tugas mereka
     * Admin/Manajer RnD: bisa update status semua penugasan
     */
    public function updateStatus(User $user, PenugasanProduksi $penugasan): bool
    {
        // Tidak bisa update status jika sudah final
        if (in_array($penugasan->status, ['completed', 'cancelled'])) {
            return false;
        }

        // Staf RnD: hanya tugas mereka
        if ($user->role_id === 'R03') {
            return $penugasan->user_id === $user->user_id;
        }

        // Admin dan Manajer RnD: semua tugas
        return in_array($user->role_id, ['R01', 'R09']);
    }
}
