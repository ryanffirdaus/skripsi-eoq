<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleBasedAccess
{
    /**
     * Define allowed routes and actions per role
     * Format: role_id => ['route' => ['action1', 'action2', ...]]
     * Jika tidak ada aksi spesifik, semua aksi diizinkan untuk route tersebut
     */
    private static array $roleBasedRoutes = [
        'R01' => [], // Admin - akses semua
        'R02' => [ // Staf Gudang
            'bahan-baku' => ['index', 'show'], // Hanya view
            'produk' => ['index', 'show'], // Hanya view
            'pengadaan' => ['index', 'create', 'store', 'edit', 'update', 'show'], // Buat dan edit pengadaan
            'pengiriman' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'penerimaan-bahan-baku' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
        ],
        'R03' => [ // Staf RnD
            'penugasan-produksi' => ['index', 'show', 'edit', 'update'], // Bisa lihat dan edit penugasan mereka
        ],
        'R04' => [ // Staf Pengadaan
            'pengadaan' => ['index', 'show'], // View pengadaan (tidak bisa buat/edit/hapus - hanya Manajer Pengadaan)
            'pembelian' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'pemasok' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'penerimaan-bahan-baku' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
        ],
        'R05' => [ // Staf Penjualan
            'produk' => ['index', 'show'], // Hanya view produk
            'pelanggan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'pesanan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
        ],
        'R06' => [ // Staf Keuangan
            'pengadaan' => ['index', 'show'], // View pengadaan saja
            'pembelian' => ['index', 'show'], // View pembelian saja
            'transaksi-pembayaran' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
        ],
        'R07' => [ // Manajer Gudang
            'bahan-baku' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'produk' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'pengadaan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // Full CRUD
            'pengiriman' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'penerimaan-bahan-baku' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
        ],
        'R08' => [ // Manajer RnD
            'penugasan-produksi' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD penugasan
            'bahan-baku' => ['index', 'show'], // Hanya view
            'produk' => ['index', 'show'], // Hanya view
            'pengadaan' => ['index', 'show'], // Hanya view
        ],
        'R09' => [ // Manajer Pengadaan
            'pengadaan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'pembelian' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'pemasok' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'penerimaan-bahan-baku' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
        ],
        'R10' => [ // Manajer Keuangan
            'pengadaan' => ['index', 'show'], // View dan bisa edit status saja (di controller)
            'pembelian' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'transaksi-pembayaran' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
        ],
    ];

    /**
     * Routes yang boleh diakses semua role (public routes)
     */
    private static array $publicRoutes = [
        'dashboard',
        'profile',
        'settings',
        'logout',
    ];

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika belum login, lanjutkan (handle oleh auth middleware)
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $roleId = $user->role_id;

        // Admin (R01) bisa akses semua route
        if ($roleId === 'R01') {
            return $next($request);
        }

        // Cek current route dan action
        $currentRoute = $this->getCurrentRoute($request);
        $currentAction = $this->getCurrentAction($request);

        // Public routes bisa diakses semua role
        if (in_array($currentRoute, self::$publicRoutes)) {
            return $next($request);
        }

        // Cek apakah role memiliki akses ke route ini
        if (!isset(self::$roleBasedRoutes[$roleId])) {
            // Role tidak terdaftar atau tidak memiliki akses apapun (kecuali public routes)
            return $this->deny($request);
        }

        $allowedRoutes = self::$roleBasedRoutes[$roleId];

        // Jika allowedRoutes kosong, berarti role ini perlu akses terbatas
        if (empty($allowedRoutes)) {
            // Untuk role yang belum dikonfigurasi, deny akses
            return $this->deny($request);
        }

        // Cek apakah current route ada di allowed routes
        if (!isset($allowedRoutes[$currentRoute])) {
            return $this->deny($request);
        }

        // Cek apakah action diizinkan untuk route ini
        $allowedActions = $allowedRoutes[$currentRoute];
        if (!empty($allowedActions) && !in_array($currentAction, $allowedActions)) {
            return $this->deny($request);
        }

        return $next($request);
    }

    /**
     * Get current route prefix (first segment of path)
     */
    private function getCurrentRoute(Request $request): string
    {
        $pathSegments = explode('/', trim($request->path(), '/'));
        return $pathSegments[0] ?? '';
    }

    /**
     * Get current action/method berdasarkan HTTP method dan route
     */
    private function getCurrentAction(Request $request): string
    {
        $method = $request->getMethod();
        $pathSegments = explode('/', trim($request->path(), '/'));

        // GET request
        if ($method === 'GET') {
            // /resource - index
            if (count($pathSegments) === 1) {
                return 'index';
            }
            // /resource/{id} - show
            if (count($pathSegments) === 2) {
                return 'show';
            }
            // /resource/create atau /resource/{id}/edit
            if (count($pathSegments) >= 2) {
                $lastSegment = end($pathSegments);
                if ($lastSegment === 'create') {
                    return 'create';
                }
                if ($lastSegment === 'edit') {
                    return 'edit';
                }
            }
        }

        // POST request - store
        if ($method === 'POST') {
            return 'store';
        }

        // PUT/PATCH request - update
        if (in_array($method, ['PUT', 'PATCH'])) {
            return 'update';
        }

        // DELETE request - destroy
        if ($method === 'DELETE') {
            return 'destroy';
        }

        return 'unknown';
    }

    /**
     * Deny access
     */
    private function deny(Request $request): Response
    {
        // Jika JSON request, return JSON response
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akses ditolak. Role Anda tidak memiliki izin untuk mengakses resource ini.',
                'status' => 'error',
            ], 403);
        }

        // Redirect ke dashboard dengan pesan error
        return redirect('/dashboard')->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
