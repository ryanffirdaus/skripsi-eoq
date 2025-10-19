<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\Pesanan;
use App\Models\Pengiriman;
use App\Models\Pengadaan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Show the dashboard for the authenticated user
     */
    public function index()
    {
        $user = Auth::user();

        // Get basic stats
        $stats = [
            'totalBahanBaku' => BahanBaku::count(),
            'lowStockItems' => BahanBaku::whereColumn('stok_bahan', '<', 'safety_stock_bahan')->count(),
            'totalPesanan' => Pesanan::count(),
            'pesananPending' => Pesanan::where('status', 'pending')->count(),
            'totalPengiriman' => Pengiriman::count(),
            'pengirimanPending' => Pengiriman::where('status', 'pending')->count(),
            'totalProduk' => Produk::count(),
            'totalUsers' => User::count(),
            'totalPengadaan' => Pengadaan::count(),
            'pengadaanPending' => Pengadaan::where('status', 'pending')->count(),
        ];

        return Inertia::render('dashboard', [
            'stats' => $stats,
        ]);
    }
}
