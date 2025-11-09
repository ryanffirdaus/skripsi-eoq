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
     *
     * REQUIREMENTS:
     * 1. Admin - CRUD semuanya + CRUD pengguna hanya Admin
     * 2. Staf Gudang & Manajer Gudang - CRUD bahan baku dan produk
     * 3. Staf Penjualan - CRUD pelanggan dan pesanan
     * 4. Staf & Manajer Pengadaan - CRUD pemasok
     * 5. Staf & Manajer Gudang - CRUD pengiriman
     * 6. Staf Gudang - tambah pengadaan, hapus status pending
     * 7. Manajer Gudang - CRUD pending pengadaan, approve pending->disetujui_gudang
     * 8. Staf & Manajer Pengadaan - isi detail pemasok/harga untuk status disetujui_gudang
     * 9. Manajer Pengadaan - approve disetujui_gudang->disetujui_pengadaan
     * 10. Manajer Keuangan - approve disetujui_pengadaan->disetujui_keuangan
     * 11. Staf & Manajer Keuangan - CRUD pembelian
     * 12. Staf Gudang - tambah & lihat detail penerimaan
     * 13. Staf & Manajer Keuangan - CRUD transaksi pembayaran
     */
    private static array $roleBasedRoutes = [
        'R01' => [], // Admin - akses semua
        'R02' => [ // Staf Gudang
            'users' => [], // Tidak akses
            'bahan-baku' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD bahan baku (tambah & edit)
            'produk' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // Hanya view produk
            'pengadaan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // Tambah pengadaan, hapus pending
            'pengiriman' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'],
            'penerimaan-bahan-baku' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // Tambah & lihat detail
            'pelanggan' => [], // Tidak akses
            'pemasok' => [], // Tidak akses
            'pesanan' => [], // Tidak akses
            'pembelian' => [], // Tidak akses
            'transaksi-pembayaran' => [], // Tidak akses
            'penugasan-produksi' => [], // Tidak akses
        ],
        'R03' => [ // Staf RnD
            'penugasan-produksi' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // Bisa lihat dan edit penugasan mereka
        ],
        'R04' => [ // Staf Pengadaan
            'pemasok' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD pemasok
            'pengadaan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // Hanya view & isi detail disetujui_gudang
            'pembelian' => [], // Tidak akses
            'penerimaan-bahan-baku' => [], // Tidak akses
            'users' => [], // Tidak akses
            'bahan-baku' => [], // Tidak akses
            'produk' => [], // Tidak akses
            'pelanggan' => [], // Tidak akses
            'pesanan' => [], // Tidak akses
            'pengiriman' => [], // Tidak akses
            'transaksi-pembayaran' => [], // Tidak akses
            'penugasan-produksi' => [], // Tidak akses
        ],
        'R05' => [ // Staf Penjualan
            'pelanggan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD pelanggan
            'pesanan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD pesanan
            'produk' => [], // Hanya view produk
            'users' => [], // Tidak akses
            'bahan-baku' => [], // Tidak akses
            'pemasok' => [], // Tidak akses
            'pengadaan' => [], // Tidak akses
            'pengiriman' => [], // Tidak akses
            'penerimaan-bahan-baku' => [], // Tidak akses
            'pembelian' => [], // Tidak akses
            'transaksi-pembayaran' => [], // Tidak akses
            'penugasan-produksi' => [], // Tidak akses
        ],
        'R06' => [ // Staf Keuangan
            'pembelian' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD pembelian
            'transaksi-pembayaran' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD transaksi
            'pengadaan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // Hanya view pengadaan
            'users' => [], // Tidak akses
            'bahan-baku' => [], // Tidak akses
            'produk' => [], // Tidak akses
            'pelanggan' => [], // Tidak akses
            'pemasok' => [], // Tidak akses
            'pesanan' => [], // Tidak akses
            'pengiriman' => [], // Tidak akses
            'penerimaan-bahan-baku' => [], // Tidak akses
            'penugasan-produksi' => [], // Tidak akses
        ],
        'R07' => [ // Manajer Gudang
            'bahan-baku' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD bahan baku
            'produk' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD produk
            'pengadaan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD pending pengadaan
            'pengiriman' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD pengiriman
            'penerimaan-bahan-baku' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // Full CRUD
            'users' => [], // Tidak akses
            'pelanggan' => [], // Tidak akses
            'pemasok' => [], // Tidak akses
            'pesanan' => [], // Tidak akses
            'pembelian' => [], // Tidak akses
            'transaksi-pembayaran' => [], // Tidak akses
            'penugasan-produksi' => [], // Tidak akses
        ],
        'R08' => [ // Manajer RnD
            'penugasan-produksi' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD penugasan
            'bahan-baku' => [], // Hanya view
            'produk' => [], // Hanya view
            'pengadaan' => [], // Hanya view
            'users' => [], // Tidak akses
            'pelanggan' => [], // Tidak akses
            'pemasok' => [], // Tidak akses
            'pesanan' => [], // Tidak akses
            'pengiriman' => [], // Tidak akses
            'penerimaan-bahan-baku' => [], // Tidak akses
            'pembelian' => [], // Tidak akses
            'transaksi-pembayaran' => [], // Tidak akses
        ],
        'R09' => [ // Manajer Pengadaan
            'pemasok' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD pemasok
            'pengadaan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // Isi detail & approve status
            'users' => [], // Tidak akses
            'bahan-baku' => [], // Tidak akses
            'produk' => [], // Tidak akses
            'pelanggan' => [], // Tidak akses
            'pesanan' => [], // Tidak akses
            'pengiriman' => [], // Tidak akses
            'penerimaan-bahan-baku' => [], // Tidak akses
            'pembelian' => [], // Tidak akses
            'transaksi-pembayaran' => [], // Tidak akses
            'penugasan-produksi' => [], // Tidak akses
        ],
        'R10' => [ // Manajer Keuangan
            'pembelian' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD pembelian
            'transaksi-pembayaran' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // CRUD transaksi
            'pengadaan' => ['index', 'create', 'store', 'edit', 'update', 'destroy', 'show'], // View & update status to disetujui_keuangan
            'users' => [], // Tidak akses
            'bahan-baku' => [], // Tidak akses
            'produk' => [], // Tidak akses
            'pelanggan' => [], // Tidak akses
            'pemasok' => [], // Tidak akses
            'pesanan' => [], // Tidak akses
            'pengiriman' => [], // Tidak akses
            'penerimaan-bahan-baku' => [], // Tidak akses
            'penugasan-produksi' => [], // Tidak akses
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

        // Users route hanya untuk Admin
        if ($currentRoute === 'users') {
            return $this->deny($request);
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
