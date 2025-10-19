<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleBasedAccess
{
    /**
     * Define allowed routes per role
     */
    private static array $roleBasedRoutes = [
        'R05' => [ // Staf Penjualan
            'pelanggan',
            'pesanan',
        ],
        'R04' => [ // Staf Pengadaan
            'pengadaan',
            'pembelian',
            'pemasok',
            'penerimaan-bahan-baku',
        ],
        'R02' => [ // Staf Gudang
            'bahan-baku',
            'pengiriman',
        ],
    ];

    /**
     * Deny routes yang tidak boleh diakses per role
     */
    private static array $deniedRoutes = [
        'R05' => [ // Staf Penjualan tidak bisa akses:
            'bahan-baku',
            'produk',
            'pemasok',
            'pengiriman',
            'pengadaan',
            'pembelian',
            'penerimaan-bahan-baku',
            'transaksi-pembayaran',
            'penugasan-produksi',
            'users',
        ],
        'R04' => [ // Staf Pengadaan tidak bisa akses:
            'bahan-baku',
            'produk',
            'pelanggan',
            'pesanan',
            'pengiriman',
            'penugasan-produksi',
            'users',
        ],
        'R02' => [ // Staf Gudang tidak bisa akses:
            'pelanggan',
            'pemasok',
            'pesanan',
            'pengadaan',
            'pembelian',
            'transaksi-pembayaran',
            'penugasan-produksi',
            'users',
        ],
    ];

    /**
     * Routes yang boleh diakses semua role (public routes)
     */
    private static array $publicRoutes = [
        'dashboard',
    ];

    public function handle(Request $request): Response
    {
        // Jika belum login, lanjutkan (handle oleh auth middleware)
        if (!Auth::check()) {
            return $this->next($request);
        }

        $user = Auth::user();
        $roleId = $user->role_id;

        // Admin (R01) dan manager bisa akses semua route
        if (in_array($roleId, ['R01', 'R08', 'R09', 'R10', 'R11'])) {
            return $this->next($request);
        }

        // Cek current route
        $currentRoute = $this->getCurrentRoute($request);

        // Public routes bisa diakses semua role
        if (in_array($currentRoute, self::$publicRoutes)) {
            return $this->next($request);
        }

        // Cek denied routes untuk role ini
        if (isset(self::$deniedRoutes[$roleId])) {
            foreach (self::$deniedRoutes[$roleId] as $deniedRoute) {
                if ($this->routeMatches($currentRoute, $deniedRoute)) {
                    return $this->deny();
                }
            }
        }

        return $this->next($request);
    }

    /**
     * Get current route prefix
     */
    private function getCurrentRoute(Request $request): string
    {
        $pathSegments = explode('/', trim($request->path(), '/'));
        return $pathSegments[0] ?? '';
    }

    /**
     * Check if current route matches denied/allowed route
     */
    private function routeMatches(string $currentRoute, string $pattern): bool
    {
        // Exact match
        if ($currentRoute === $pattern) {
            return true;
        }

        // Prefix match (e.g., 'penerimaan-bahan-baku' matches 'penerimaan')
        if (str_starts_with($pattern, $currentRoute)) {
            return true;
        }

        return false;
    }

    /**
     * Deny access and redirect
     */
    private function deny(): Response
    {
        return response()->json([
            'message' => 'Unauthorized access. Your role does not have permission to access this resource.',
        ], 403);
    }

    /**
     * Continue to next middleware
     */
    private function next(Request $request): Response
    {
        // This is a placeholder - in real implementation, you'd call the actual next middleware
        // For now, we're just returning a continue signal
        return response('continue', 200);
    }
}
