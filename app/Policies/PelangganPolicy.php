<?php

namespace App\Policies;

use App\Models\Pelanggan;
use App\Models\User;

class PelangganPolicy
{
    /**
     * Staf Penjualan (R05) dapat view/create/update/delete Pelanggan
     * Admin dan role tertentu dapat view semua
     */
    public function viewAny(User $user): bool
    {
        // Admin, Manajer, dan Staf Penjualan bisa view
        return in_array($user->role_id, ['R01', 'R08', 'R09', 'R10', 'R11', 'R05']);
    }

    public function view(User $user, Pelanggan $pelanggan): bool
    {
        return in_array($user->role_id, ['R01', 'R08', 'R09', 'R10', 'R11', 'R05']);
    }

    public function create(User $user): bool
    {
        // Hanya Staf Penjualan dan Admin yang bisa create
        return in_array($user->role_id, ['R01', 'R05', 'R08', 'R09', 'R10', 'R11']);
    }

    public function update(User $user, Pelanggan $pelanggan): bool
    {
        // Hanya Staf Penjualan dan Admin yang bisa update
        return in_array($user->role_id, ['R01', 'R05', 'R08', 'R09', 'R10', 'R11']);
    }

    public function delete(User $user, Pelanggan $pelanggan): bool
    {
        // Hanya Admin yang bisa delete
        return in_array($user->role_id, ['R01', 'R08', 'R09', 'R10', 'R11']);
    }

    public function restore(User $user, Pelanggan $pelanggan): bool
    {
        // Hanya Admin yang bisa restore
        return in_array($user->role_id, ['R01', 'R08', 'R09', 'R10', 'R11']);
    }

    public function forceDelete(User $user, Pelanggan $pelanggan): bool
    {
        // Hanya Admin yang bisa force delete
        return $user->role_id === 'R01';
    }
}
