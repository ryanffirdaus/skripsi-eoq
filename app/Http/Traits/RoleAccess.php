<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Trait untuk memudahkan pengecekan akses berdasarkan role di dalam controller
 *
 * Contoh penggunaan:
 * - $this->hasRole('R01') - check apakah user adalah R01 (Admin)
 * - $this->hasRoles(['R01', 'R02']) - check apakah user salah satu dari roles
 * - $this->isAdmin() - shortcut untuk check R01
 * - $this->unauthorize() - throw exception untuk akses ditolak
 */
trait RoleAccess
{
    /**
     * Check apakah user memiliki role tertentu
     */
    public function hasRole(string $roleId): bool
    {
        return Auth::check() && Auth::user()->role_id === $roleId;
    }

    /**
     * Check apakah user memiliki salah satu dari roles yang diberikan
     */
    public function hasRoles(array $roleIds): bool
    {
        return Auth::check() && in_array(Auth::user()->role_id, $roleIds);
    }

    /**
     * Check apakah user adalah Admin (R01)
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('R01');
    }

    /**
     * Check apakah user adalah Staf Gudang (R02)
     */
    public function isStafGudang(): bool
    {
        return $this->hasRole('R02');
    }

    /**
     * Check apakah user adalah Manajer Gudang (R07)
     */
    public function isManajerGudang(): bool
    {
        return $this->hasRole('R07');
    }

    /**
     * Check apakah user adalah Staf Gudang atau Manajer Gudang
     */
    public function isGudangRelated(): bool
    {
        return $this->hasRoles(['R02', 'R07']);
    }

    /**
     * Check apakah user adalah Staf Pengadaan (R04)
     */
    public function isStafPengadaan(): bool
    {
        return $this->hasRole('R04');
    }

    /**
     * Check apakah user adalah Staf Penjualan (R05)
     */
    public function isStafPenjualan(): bool
    {
        return $this->hasRole('R05');
    }

    /**
     * Get current user's role
     */
    public function getCurrentRole(): ?string
    {
        return Auth::check() ? Auth::user()->role_id : null;
    }

    /**
     * Get current user's role name
     */
    public function getCurrentRoleName(): ?string
    {
        return Auth::check() ? Auth::user()->role->name ?? null : null;
    }

    /**
     * Throw unauthorized exception
     */
    public function unauthorize(): void
    {
        abort(403, 'Unauthorized action.');
    }
}
