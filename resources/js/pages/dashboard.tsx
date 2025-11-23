import { AreaChart } from '@/components/charts/area-chart';
import { BarChart } from '@/components/charts/bar-chart';
import { LineChart } from '@/components/charts/line-chart';
import { PieChart } from '@/components/charts/pie-chart';
import { ChartCard } from '@/components/dashboard/chart-card';
import { MetricCard } from '@/components/dashboard/metric-card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { BarChart3, DollarSign, Package, ShoppingCart, TrendingUp, Users } from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardData {
    kpis: {
        totalUsers?: number;
        totalProduk?: number;
        ordersToday?: number;
        revenueToday?: number;
        totalInventory?: number;
        lowStockItems?: number;
        shipmentsToday?: number;
        conversionRate?: number;
        activePOs?: number;
        pendingApprovals?: number;
        totalSpendingMonth?: number;
        revenueMonth?: number;
        expensesMonth?: number;
        profitMargin?: number;
        outstandingPayments?: number;
    };
    salesTrend?: any[];
    orderStatusDistribution?: any[];
    procurementSpending?: any[];
    topProducts?: any[];
    stockMovementTrend?: any[];
    inventoryLevelDistribution?: any[];
    fastMovingItems?: any[];
    orderStatusFunnel?: any[];
    topCustomers?: any[];
    productPerformance?: any[];
    procurementSpendingTrend?: any[];
    supplierPerformance?: any[];
    poStatusDistribution?: any[];
    revenueVsExpenses?: any[];
    cashFlowTrend?: any[];
    paymentStatusDistribution?: any[];
}

interface Props {
    stats: any;
    auth: {
        user: {
            role_id: string;
            name: string;
        };
    };
}

export default function EnhancedDashboard({ stats, auth }: Props) {
    const [dashboardData, setDashboardData] = useState<DashboardData | null>(null);
    const [loading, setLoading] = useState(true);
    const userRole = auth?.user?.role_id || '';

    // Map role_id to API endpoint
    const getRoleEndpoint = (): string => {
        const roleMap: Record<string, string> = {
            R01: 'admin',
            R02: 'staf-gudang',
            R05: 'staf-penjualan',
            R04: 'staf-pengadaan',
            R06: 'staf-keuangan',
            R07: 'manajer-gudang',
            R09: 'manajer-pengadaan',
            R10: 'manajer-keuangan',
        };
        return roleMap[userRole] || 'admin';
    };

    useEffect(() => {
        const fetchDashboardData = async () => {
            try {
                const response = await axios.get(`/api/dashboard/${getRoleEndpoint()}`);
                setDashboardData(response.data);
            } catch (error) {
                console.error('Error fetching dashboard data:', error);
                // Use empty data as fallback
                setDashboardData({
                    kpis: {},
                    salesTrend: [],
                    orderStatusDistribution: [],
                    procurementSpending: [],
                    topProducts: [],
                    stockMovementTrend: [],
                    inventoryLevelDistribution: [],
                    fastMovingItems: [],
                });
            } finally {
                setLoading(false);
            }
        };

        fetchDashboardData();
    }, [userRole]);

    if (loading || !dashboardData) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard" />
                <div className="flex h-64 items-center justify-center">
                    <div className="text-center">
                        <div className="mb-2 inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-600 border-r-transparent"></div>
                        <p className="text-sm text-gray-500">Memuat data dashboard...</p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    // Admin Dashboard
    if (userRole === 'R01') {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard Admin" />
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="space-y-6">
                    <div className="mt-6">
                        <h1 className="text-3xl font-bold">Dashboard Admin</h1>
                        <p className="mt-1 text-gray-500 dark:text-gray-400">Pantau sistem dan kelola operasional</p>
                    </div>

                    {/* KPIs */}
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <MetricCard title="Total Pengguna" value={dashboardData.kpis.totalUsers || 0} icon={<Users className="h-6 w-6 text-blue-600" />} color="text-blue-600" />
                        <MetricCard title="Total Produk" value={dashboardData.kpis.totalProduk || 0} icon={<Package className="h-6 w-6 text-green-600" />} color="text-green-600" />
                        <MetricCard title="Pesanan Hari Ini" value={dashboardData.kpis.ordersToday || 0} icon={<ShoppingCart className="h-6 w-6 text-purple-600" />} color="text-purple-600" />
                        <MetricCard
                            title="Pendapatan Hari Ini"
                            value={`Rp ${((dashboardData.kpis.revenueToday || 0) / 1000).toFixed(0)}K`}
                            icon={<DollarSign className="h-6 w-6 text-orange-600" />}
                            color="text-orange-600"
                        />
                    </div>

                    {/* Charts */}
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <ChartCard title="Tren Penjualan Bulanan" description="Pesanan dan pendapatan 12 bulan terakhir">
                            <LineChart data={dashboardData.salesTrend || []} xKey="month" yKey={['orders', 'revenue']} colors={['#3b82f6', '#10b981']} height={250} />
                        </ChartCard>

                        <ChartCard title="Distribusi Status Pesanan" description="Status pesanan saat ini">
                            <PieChart data={dashboardData.orderStatusDistribution || []} nameKey="name" valueKey="value" height={250} />
                        </ChartCard>

                        <ChartCard title="Pengeluaran Pengadaan" description="Spending bulanan 6 bulan terakhir">
                            <BarChart data={dashboardData.procurementSpending || []} xKey="month" yKey="spending" colors={['#f59e0b']} height={250} />
                        </ChartCard>

                        <ChartCard title="Top 5 Produk Terlaris" description="Produk dengan penjualan tertinggi">
                            <BarChart data={dashboardData.topProducts || []} xKey="name" yKey="value" layout="vertical" colors={['#8b5cf6']} height={250} />
                        </ChartCard>
                    </div>
                </div>
                </div>
            </AppLayout>
        );
    }

    // Staf Gudang Dashboard
    if (userRole === 'R02') {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard Staf Gudang" />
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="space-y-6">
                    <div>
                        <h1 className="text-3xl font-bold">Dashboard Staf Gudang</h1>
                        <p className="mt-1 text-gray-500 dark:text-gray-400">Kelola inventori dan pengiriman</p>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <MetricCard title="Total Inventori" value={dashboardData.kpis.totalInventory || 0} icon={<Package className="h-6 w-6 text-blue-600" />} color="text-blue-600" />
                        <MetricCard title="Stok Rendah" value={dashboardData.kpis.lowStockItems || 0} icon={<TrendingUp className="h-6 w-6 text-red-600" />} color="text-red-600" trend="down" />
                        <MetricCard title="Pengiriman Hari Ini" value={dashboardData.kpis.shipmentsToday || 0} icon={<BarChart3 className="h-6 w-6 text-green-600" />} color="text-green-600" />
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <ChartCard title="Pergerakan Stok" description="Tren penambahan stok">
                            <AreaChart data={dashboardData.stockMovementTrend || []} xKey="date" yKey="movement" color="#3b82f6" height={250} />
                        </ChartCard>

                        <ChartCard title="Distribusi Level Inventori" description="Kategori stok saat ini">
                            <PieChart data={dashboardData.inventoryLevelDistribution || []} nameKey="name" valueKey="value" colors={['#10b981', '#f59e0b', '#ef4444']} height={250} />
                        </ChartCard>

                        <ChartCard title="Fast-Moving Items" description="Top 10 bahan paling banyak digunakan">
                            <BarChart data={dashboardData.fastMovingItems?.slice(0, 10) || []} xKey="name" yKey="value" layout="vertical" colors={['#6366f1']} height={300} />
                        </ChartCard>
                    </div>
                </div>
                </div>
            </AppLayout>
        );
    }

    // Staf Penjualan Dashboard
    if (userRole === 'R05') {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard Staf Penjualan" />
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="space-y-6">
                    <div>
                        <h1 className="text-3xl font-bold">Dashboard Staf Penjualan</h1>
                        <p className="mt-1 text-gray-500 dark:text-gray-400">Analisis penjualan dan performa produk</p>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <MetricCard title="Pesanan Hari Ini" value={dashboardData.kpis.ordersToday || 0} icon={<ShoppingCart className="h-6 w-6 text-blue-600" />} color="text-blue-600" />
                        <MetricCard
                            title="Pendapatan Hari Ini"
                            value={`Rp ${((dashboardData.kpis.revenueToday || 0) / 1000).toFixed(0)}K`}
                            icon={<DollarSign className="h-6 w-6 text-green-600" />}
                            color="text-green-600"
                        />
                        <MetricCard title="Conversion Rate" value={`${dashboardData.kpis.conversionRate || 0}%`} icon={<TrendingUp className="h-6 w-6 text-purple-600" />} color="text-purple-600" />
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <ChartCard title="Tren Penjualan Harian" description="Pesanan dan pendapatan harian">
                            <LineChart data={dashboardData.salesTrend || []} xKey="date" yKey={['orders', 'revenue']} colors={['#3b82f6', '#10b981']} height={250} />
                        </ChartCard>

                        <ChartCard title="Status Pesanan (Funnel)" description="Distribusi status pesanan">
                            <BarChart data={dashboardData.orderStatusFunnel || []} xKey="name" yKey="value" colors={['#6366f1']} height={250} />
                        </ChartCard>

                        <ChartCard title="Top 10 Pelanggan" description="Berdasarkan total pembelian">
                            <BarChart data={dashboardData.topCustomers?.slice(0, 10) || []} xKey="name" yKey="value" layout="vertical" colors={['#f59e0b']} height={300} />
                        </ChartCard>

                        <ChartCard title="Performa Produk" description="Produk dengan revenue tertinggi">
                            <PieChart data={dashboardData.productPerformance || []} nameKey="name" valueKey="value" height={250} />
                        </ChartCard>
                    </div>
                </div>
                </div>
            </AppLayout>
        );
    }

    // Staf Pengadaan Dashboard
    if (userRole === 'R04') {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard Staf Pengadaan" />
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="space-y-6">
                    <div>
                        <h1 className="text-3xl font-bold">Dashboard Staf Pengadaan</h1>
                        <p className="mt-1 text-gray-500 dark:text-gray-400">Monitor pengadaan dan supplier</p>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <MetricCard title="PO Aktif" value={dashboardData.kpis.activePOs || 0} icon={<Package className="h-6 w-6 text-blue-600" />} color="text-blue-600" />
                        <MetricCard title="Pending Approval" value={dashboardData.kpis.pendingApprovals || 0} icon={<TrendingUp className="h-6 w-6 text-red-600" />} color="text-red-600" />
                        <MetricCard
                            title="Spending Bulan Ini"
                            value={`Rp ${((dashboardData.kpis.totalSpendingMonth || 0) / 1000000).toFixed(1)}M`}
                            icon={<DollarSign className="h-6 w-6 text-green-600" />}
                            color="text-green-600"
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <ChartCard title="Tren Spending Pengadaan" description="Pengeluaran bulanan">
                            <AreaChart data={dashboardData.procurementSpendingTrend || []} xKey="month" yKey="spending" color="#f59e0b" height={250} />
                        </ChartCard>

                        <ChartCard title="Performa Supplier" description="Top 5 supplier berdasarkan nilai PO">
                            <BarChart data={dashboardData.supplierPerformance || []} xKey="name" yKey="value" layout="vertical" colors={['#8b5cf6']} height={250} />
                        </ChartCard>

                        <ChartCard title="Distribusi Status PO" description="Status purchase order saat ini">
                            <PieChart data={dashboardData.poStatusDistribution || []} nameKey="name" valueKey="value" height={250} />
                        </ChartCard>
                    </div>
                </div>
                </div>
            </AppLayout>
        );
    }

    // Staf Keuangan Dashboard
    if (userRole === 'R06') {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard Staf Keuangan" />
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="space-y-6">
                    <div>
                        <h1 className="text-3xl font-bold">Dashboard Staf Keuangan</h1>
                        <p className="mt-1 text-gray-500 dark:text-gray-400">Analisis keuangan dan cash flow</p>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <MetricCard
                            title="Pendapatan Bulan Ini"
                            value={`Rp ${((dashboardData.kpis.revenueMonth || 0) / 1000000).toFixed(1)}M`}
                            icon={<TrendingUp className="h-6 w-6 text-green-600" />}
                            color="text-green-600"
                        />
                        <MetricCard
                            title="Pengeluaran Bulan Ini"
                            value={`Rp ${((dashboardData.kpis.expensesMonth || 0) / 1000000).toFixed(1)}M`}
                            icon={<BarChart3 className="h-6 w-6 text-red-600" />}
                            color="text-red-600"
                        />
                        <MetricCard title="Profit Margin" value={`${dashboardData.kpis.profitMargin || 0}%`} icon={<DollarSign className="h-6 w-6 text-blue-600" />} color="text-blue-600" />
                        <MetricCard
                            title="Pembayaran Outstanding"
                            value={`Rp ${((dashboardData.kpis.outstandingPayments || 0) / 1000000).toFixed(1)}M`}
                            icon={<ShoppingCart className="h-6 w-6 text-orange-600" />}
                            color="text-orange-600"
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div className="lg:col-span-2">
                            <ChartCard title="Pendapatan vs Pengeluaran" description="Perbandingan 12 bulan terakhir">
                                <LineChart data={dashboardData.revenueVsExpenses || []} xKey="month" yKey={['revenue', 'expenses']} colors={['#10b981', '#ef4444']} height={250} />
                            </ChartCard>
                        </div>

                        <ChartCard title="Cash Flow Trend" description="Arus kas 6 bulan terakhir">
                            <AreaChart data={dashboardData.cashFlowTrend || []} xKey="month" yKey="revenue" color="#3b82f6" height={250} />
                        </ChartCard>

                        <ChartCard title="Distribusi Jenis Pembayaran" description="Total por jenis pembayaran">
                            <PieChart data={dashboardData.paymentStatusDistribution || []} nameKey="name" valueKey="value" height={250} />
                        </ChartCard>
                    </div>
                </div>
                </div>
            </AppLayout>
        );
    }

    // Manajer dashboards (use same as their staff counterparts)
    if (userRole === 'R07') {
        // Manajer Gudang - same as Staf Gudang
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard Manajer Gudang" />
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="space-y-6">
                    <div>
                        <h1 className="text-3xl font-bold">Dashboard Manajer Gudang</h1>
                        <p className="mt-1 text-gray-500 dark:text-gray-400">Kelola inventori dan pengiriman</p>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <MetricCard title="Total Inventori" value={dashboardData.kpis.totalInventory || 0} icon={<Package className="h-6 w-6 text-blue-600" />} color="text-blue-600" />
                        <MetricCard title="Stok Rendah" value={dashboardData.kpis.lowStockItems || 0} icon={<TrendingUp className="h-6 w-6 text-red-600" />} color="text-red-600" trend="down" />
                        <MetricCard title="Pengiriman Hari Ini" value={dashboardData.kpis.shipmentsToday || 0} icon={<BarChart3 className="h-6 w-6 text-green-600" />} color="text-green-600" />
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <ChartCard title="Pergerakan Stok" description="Tren penambahan stok">
                            <AreaChart data={dashboardData.stockMovementTrend || []} xKey="date" yKey="movement" color="#3b82f6" height={250} />
                        </ChartCard>

                        <ChartCard title="Distribusi Level Inventori" description="Kategori stok saat ini">
                            <PieChart data={dashboardData.inventoryLevelDistribution || []} nameKey="name" valueKey="value" colors={['#10b981', '#f59e0b', '#ef4444']} height={250} />
                        </ChartCard>

                        <ChartCard title="Fast-Moving Items" description="Top 10 bahan paling banyak digunakan">
                            <BarChart data={dashboardData.fastMovingItems?.slice(0, 10) || []} xKey="name" yKey="value" layout="vertical" colors={['#6366f1']} height={300} />
                        </ChartCard>
                    </div>
                </div>
                </div>
            </AppLayout>
        );
    }

    // Default fallback
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold">Dashboard</h1>
                    <p className="mt-1 text-gray-500 dark:text-gray-400">Selamat datang, {auth.user.name}</p>
                </div>
            </div>
        </AppLayout>
    );
}
