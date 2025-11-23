<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\Pelanggan;
use App\Models\Pembelian;
use App\Models\Pengadaan;
use App\Models\Pengiriman;
use App\Models\Pesanan;
use App\Models\PesananDetail;
use App\Models\Produk;
use App\Models\TransaksiPembayaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'pesananPending' => Pesanan::where('status', 'menunggu')->count(),
            'totalPengiriman' => Pengiriman::count(),
            'pengirimanPending' => Pengiriman::where('status', 'menunggu')->count(),
            'totalProduk' => Produk::count(),
            'totalUsers' => User::count(),
            'totalPengadaan' => Pengadaan::count(),
            'pengadaanPending' => Pengadaan::where('status', 'draft')->count(),
        ];

        return Inertia::render('dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * Get dashboard data for specific role
     */
    public function getDashboardData(Request $request, $role)
    {
        try {
            $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->format('Y-m-d'));

            $method = 'get' . ucfirst(str_replace('-', '', $role)) . 'Data';
            
            if (method_exists($this, $method)) {
                return response()->json($this->$method($dateFrom, $dateTo));
            }

            return response()->json(['error' => 'Invalid role'], 404);
        } catch (\Exception $e) {
            \Log::error('Dashboard API Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load dashboard data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getAdminData($dateFrom, $dateTo)
    {
        return [
            'kpis' => [
                'totalUsers' => User::count(),
                'totalProduk' => Produk::count(),
                'ordersToday' => Pesanan::whereDate('created_at', today())->count(),
                'revenueToday' => Pesanan::whereDate('created_at', today())->sum('total_harga'),
            ],
            'salesTrend' => $this->getMonthlySalesTrend(12),
            'orderStatusDistribution' => $this->getOrderStatusDistribution(),
            'procurementSpending' => $this->getMonthlyProcurementSpending(6),
            'topProducts' => $this->getTopSellingProducts(5),
        ];
    }

    private function getStafgudangData($dateFrom, $dateTo)
    {
        return [
            'kpis' => [
                'totalInventory' => BahanBaku::count(),
                'lowStockItems' => BahanBaku::whereColumn('stok_bahan', '<=', 'rop_bahan')->count(),
                'shipmentsToday' => Pengiriman::whereDate('created_at', today())->count(),
            ],
            'stockMovementTrend' => $this->getStockMovementTrend($dateFrom, $dateTo),
            'inventoryLevelDistribution' => $this->getInventoryLevelDistribution(),
            'fastMovingItems' => $this->getFastMovingItems(10),
        ];
    }

    private function getStafpenjualanData($dateFrom, $dateTo)
    {
        return [
            'kpis' => [
                'ordersToday' => Pesanan::whereDate('created_at', today())->count(),
                'revenueToday' => Pesanan::whereDate('created_at', today())->sum('total_harga'),
                'conversionRate' => $this->calculateConversionRate(),
            ],
            'salesTrend' => $this->getDailySalesTrend($dateFrom, $dateTo),
            'orderStatusFunnel' => $this->getOrderStatusFunnel(),
            'topCustomers' => $this->getTopCustomers(10),
            'productPerformance' => $this->getProductPerformance(),
        ];
    }

    private function getStafpengadaanData($dateFrom, $dateTo)
    {
        return [
            'kpis' => [
                'activePOs' => Pembelian::whereIn('status', ['menunggu', 'dipesan', 'dikirim'])->count(),
                'pendingApprovals' => Pengadaan::where('status', 'menunggu_persetujuan_gudang')->count(),
                'totalSpendingMonth' => Pembelian::whereMonth('created_at', now()->month)->sum('total_biaya'),
            ],
            'procurementSpendingTrend' => $this->getMonthlyProcurementSpending(12),
            'supplierPerformance' => $this->getSupplierPerformance(),
            'poStatusDistribution' => $this->getPOStatusDistribution(),
        ];
    }

    private function getStafkeuanganData($dateFrom, $dateTo)
    {
        return [
            'kpis' => [
                'revenueMonth' => Pesanan::whereMonth('created_at', now()->month)->sum('total_harga'),
                'expensesMonth' => Pembelian::whereMonth('created_at', now()->month)->sum('total_biaya'),
                'profitMargin' => $this->calculateProfitMargin(),
                'outstandingPayments' => TransaksiPembayaran::where('jenis_pembayaran', '!=', 'pelunasan')->sum('total_pembayaran'),
            ],
            'revenueVsExpenses' => $this->getRevenueVsExpenses(12),
            'cashFlowTrend' => $this->getCashFlowTrend(6),
            'paymentStatusDistribution' => $this->getPaymentStatusDistribution(),
        ];
    }

    private function getManajergudangData($dateFrom, $dateTo)
    {
        return $this->getStafgudangData($dateFrom, $dateTo);
    }

    private function getManajerpengadaanData($dateFrom, $dateTo)
    {
        return $this->getStafpengadaanData($dateFrom, $dateTo);
    }

    private function getManajerkeuanganData($dateFrom, $dateTo)
    {
        return $this->getStafkeuanganData($dateFrom, $dateTo);
    }

    // Helper methods for data aggregation

    private function getMonthlySalesTrend($months = 12)
    {
        return Pesanan::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(total_harga) as total_revenue')
        )
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'orders' => $item->order_count,
                    'revenue' => (float) $item->total_revenue,
                ];
            });
    }

    private function getOrderStatusDistribution()
    {
        return Pesanan::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->status),
                    'value' => $item->count,
                ];
            });
    }

    private function getMonthlyProcurementSpending($months = 6)
    {
        return Pembelian::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('SUM(total_biaya) as spending')
        )
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'spending' => (float) $item->spending,
                ];
            });
    }

    private function getTopSellingProducts($limit = 5)
    {
        return PesananDetail::select('produk_id', DB::raw('SUM(jumlah_produk) as total_sold'))
            ->with('produk:produk_id,nama_produk')
            ->groupBy('produk_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->produk->nama_produk ?? 'Unknown',
                    'value' => $item->total_sold,
                ];
            });
    }

    private function getStockMovementTrend($dateFrom, $dateTo)
    {
        return BahanBaku::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as items_added')
        )
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'movement' => $item->items_added,
                ];
            });
    }

    private function getInventoryLevelDistribution()
    {
        return [
            ['name' => 'Stok Aman', 'value' => BahanBaku::whereColumn('stok_bahan', '>', 'rop_bahan')->count()],
            ['name' => 'Stok Rendah', 'value' => BahanBaku::whereColumn('stok_bahan', '<=', 'rop_bahan')->whereColumn('stok_bahan', '>', 'safety_stock_bahan')->count()],
            ['name' => 'Stok Kritis', 'value' => BahanBaku::whereColumn('stok_bahan', '<=', 'safety_stock_bahan')->count()],
        ];
    }

    private function getFastMovingItems($limit = 10)
    {
        return BahanBaku::select('nama_bahan', 'stok_bahan')
            ->orderByDesc('stok_bahan')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->nama_bahan,
                    'value' => $item->stok_bahan,
                ];
            });
    }

    private function calculateConversionRate()
    {
        $total = Pesanan::count();
        $completed = Pesanan::where('status', 'selesai')->count();
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    private function getDailySalesTrend($dateFrom, $dateTo)
    {
        return Pesanan::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as orders'),
            DB::raw('SUM(total_harga) as revenue')
        )
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'orders' => $item->orders,
                    'revenue' => (float) $item->revenue,
                ];
            });
    }

    private function getOrderStatusFunnel()
    {
        $statuses = ['menunggu', 'dikonfirmasi', 'diproses', 'siap', 'dikirim', 'diterima', 'selesai'];
        return collect($statuses)->map(function ($status) {
            return [
                'name' => ucfirst($status),
                'value' => Pesanan::where('status', $status)->count(),
            ];
        });
    }

    private function getTopCustomers($limit = 10)
    {
        return Pesanan::select('pelanggan_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total_harga) as total_spent'))
            ->with('pelanggan:pelanggan_id,nama_pelanggan')
            ->groupBy('pelanggan_id')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->pelanggan->nama_pelanggan ?? 'Unknown',
                    'orders' => $item->order_count,
                    'value' => (float) $item->total_spent,
                ];
            });
    }

    private function getProductPerformance()
    {
        return PesananDetail::select('produk_id', DB::raw('SUM(jumlah_produk * harga_satuan) as revenue'))
            ->with('produk:produk_id,nama_produk')
            ->groupBy('produk_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->produk->nama_produk ?? 'Unknown',
                    'value' => (float) $item->revenue,
                ];
            });
    }

    private function getSupplierPerformance()
    {
        return Pembelian::select('pemasok_id', DB::raw('COUNT(*) as po_count'), DB::raw('SUM(total_biaya) as total_value'))
            ->with('pemasok:pemasok_id,nama_pemasok')
            ->groupBy('pemasok_id')
            ->orderByDesc('total_value')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->pemasok->nama_pemasok ?? 'Unknown',
                    'pos' => $item->po_count,
                    'value' => (float) $item->total_value,
                ];
            });
    }

    private function getPOStatusDistribution()
    {
        return Pembelian::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->status),
                    'value' => $item->count,
                ];
            });
    }

    private function calculateProfitMargin()
    {
        $revenue = Pesanan::whereMonth('created_at', now()->month)->sum('total_harga');
        $expenses = Pembelian::whereMonth('created_at', now()->month)->sum('total_biaya');
        return $revenue > 0 ? round((($revenue - $expenses) / $revenue) * 100, 2) : 0;
    }

    private function getRevenueVsExpenses($months = 12)
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $data[] = [
                'month' => $month,
                'revenue' => (float) Pesanan::where('created_at', 'like', "$month%")->sum('total_harga'),
                'expenses' => (float) Pembelian::where('created_at', 'like', "$month%")->sum('total_biaya'),
            ];
        }
        return $data;
    }

    private function getCashFlowTrend($months = 6)
    {
        return $this->getRevenueVsExpenses($months);
    }

    private function getPaymentStatusDistribution()
    {
        return TransaksiPembayaran::select('jenis_pembayaran', DB::raw('SUM(total_pembayaran) as total'))
            ->groupBy('jenis_pembayaran')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->jenis_pembayaran),
                    'value' => (float) $item->total,
                ];
            });
    }
}
