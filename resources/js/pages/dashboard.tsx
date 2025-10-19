import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { AlertCircle, BarChart3, Package, TrendingUp, Truck, Users } from 'lucide-react';

interface DashboardStats {
    totalBahanBaku?: number;
    lowStockItems?: number;
    totalPesanan?: number;
    pesananPending?: number;
    totalPengiriman?: number;
    pengirimanPending?: number;
    totalProduk?: number;
    totalUsers?: number;
    totalPengadaan?: number;
    pengadaanPending?: number;
}

interface Props {
    stats: DashboardStats;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface AuthUser {
    id: string;
    name: string;
    email: string;
    role_id: string;
}

export default function Dashboard({ stats }: Props) {
    const { auth } = usePage().props as unknown as { auth: { user: AuthUser } };
    const userRole = auth?.user?.role_id || '';

    const getWelcomeMessage = () => {
        const roleMessages: Record<string, string> = {
            R01: 'Selamat datang di Dashboard Admin',
            R02: 'Selamat datang di Dashboard Staf Gudang',
            R03: 'Selamat datang di Dashboard Staf RnD',
            R04: 'Selamat datang di Dashboard Staf Pengadaan',
            R05: 'Selamat datang di Dashboard Staf Penjualan',
            R06: 'Selamat datang di Dashboard Staf Keuangan',
            R07: 'Selamat datang di Dashboard Manajer Gudang',
            R08: 'Selamat datang di Dashboard Manajer RnD',
            R09: 'Selamat datang di Dashboard Manajer Pengadaan',
            R10: 'Selamat datang di Dashboard Manajer Keuangan',
        };
        return roleMessages[userRole] || 'Selamat datang';
    };

    const StatCard = ({ icon: Icon, label, value, color }: { icon: React.ReactNode; label: string; value: number; color: string }) => (
        <Card className="overflow-hidden">
            <CardContent className="p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-500 dark:text-gray-400">{label}</p>
                        <p className={`mt-1 text-2xl font-bold ${color}`}>{value}</p>
                    </div>
                    <div className={`rounded-lg p-3 ${color.replace('text-', 'bg-').replace('-600', '-100')}`}>{Icon}</div>
                </div>
            </CardContent>
        </Card>
    );

    // Admin Dashboard
    const renderAdminDashboard = () => (
        <div className="space-y-6">
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    icon={<Package className="h-6 w-6 text-blue-600" />}
                    label="Total Bahan Baku"
                    value={stats.totalBahanBaku || 0}
                    color="text-blue-600"
                />
                <StatCard
                    icon={<Truck className="h-6 w-6 text-green-600" />}
                    label="Total Pengiriman"
                    value={stats.totalPengiriman || 0}
                    color="text-green-600"
                />
                <StatCard
                    icon={<TrendingUp className="h-6 w-6 text-purple-600" />}
                    label="Total Pesanan"
                    value={stats.totalPesanan || 0}
                    color="text-purple-600"
                />
                <StatCard
                    icon={<Users className="h-6 w-6 text-orange-600" />}
                    label="Total User"
                    value={stats.totalUsers || 0}
                    color="text-orange-600"
                />
            </div>

            {/* Quick Access Cards */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <AlertCircle className="h-5 w-5 text-red-600" />
                            Status Kritis
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="flex items-center justify-between border-b pb-2">
                            <span className="text-sm">Bahan Baku Stok Rendah</span>
                            <span className="font-bold text-red-600">{stats.lowStockItems || 0}</span>
                        </div>
                        <div className="flex items-center justify-between border-b pb-2">
                            <span className="text-sm">Pengadaan Pending</span>
                            <span className="font-bold text-orange-600">{stats.pengadaanPending || 0}</span>
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-sm">Pengiriman Pending</span>
                            <span className="font-bold text-yellow-600">{stats.pengirimanPending || 0}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <BarChart3 className="h-5 w-5" />
                            Informasi Sistem
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>Selamat datang di sistem manajemen EOQ.</p>
                        <p className="text-gray-600 dark:text-gray-400">Anda memiliki akses penuh ke semua menu administrasi sistem.</p>
                        <p className="mt-4 text-xs text-gray-500">Dashboard akan menampilkan data real-time untuk monitoring sistem.</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    );

    // Staf Gudang Dashboard
    const renderStafGudangDashboard = () => (
        <div className="space-y-6">
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <StatCard
                    icon={<Package className="h-6 w-6 text-blue-600" />}
                    label="Total Bahan Baku"
                    value={stats.totalBahanBaku || 0}
                    color="text-blue-600"
                />
                <StatCard
                    icon={<AlertCircle className="h-6 w-6 text-red-600" />}
                    label="Stok Rendah"
                    value={stats.lowStockItems || 0}
                    color="text-red-600"
                />
                <StatCard
                    icon={<Truck className="h-6 w-6 text-green-600" />}
                    label="Pengiriman Hari Ini"
                    value={stats.pengirimanPending || 0}
                    color="text-green-600"
                />
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Aktivitas Gudang</CardTitle>
                        <CardDescription>Tugas yang perlu dilakukan</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-950">
                            <p className="font-medium text-red-900 dark:text-red-100">⚠️ Bahan Baku Stok Rendah</p>
                            <p className="mt-1 text-sm text-red-700 dark:text-red-200">{stats.lowStockItems || 0} item perlu di-reorder</p>
                        </div>
                        <div className="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950">
                            <p className="font-medium text-green-900 dark:text-green-100">✓ Total Bahan Baku</p>
                            <p className="mt-1 text-sm text-green-700 dark:text-green-200">{stats.totalBahanBaku || 0} item di gudang</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Panduan Kerja</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>📦 Kelola stok bahan baku</p>
                        <p>🚚 Pantau pengiriman masuk</p>
                        <p>📝 Catat penerimaan bahan</p>
                        <p>⚠️ Lapor stok yang rendah</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    );

    // Staf Penjualan Dashboard
    const renderStafPenjualanDashboard = () => (
        <div className="space-y-6">
            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                <StatCard
                    icon={<TrendingUp className="h-6 w-6 text-blue-600" />}
                    label="Total Pesanan"
                    value={stats.totalPesanan || 0}
                    color="text-blue-600"
                />
                <StatCard
                    icon={<AlertCircle className="h-6 w-6 text-orange-600" />}
                    label="Pesanan Pending"
                    value={stats.pesananPending || 0}
                    color="text-orange-600"
                />
                <StatCard
                    icon={<Package className="h-6 w-6 text-green-600" />}
                    label="Total Produk"
                    value={stats.totalProduk || 0}
                    color="text-green-600"
                />
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Status Penjualan</CardTitle>
                        <CardDescription>Data penjualan hari ini</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-950">
                            <p className="font-medium text-blue-900 dark:text-blue-100">📊 Pesanan Baru</p>
                            <p className="mt-1 text-sm text-blue-700 dark:text-blue-200">{stats.pesananPending || 0} pesanan menunggu proses</p>
                        </div>
                        <div className="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950">
                            <p className="font-medium text-green-900 dark:text-green-100">✓ Produk Tersedia</p>
                            <p className="mt-1 text-sm text-green-700 dark:text-green-200">{stats.totalProduk || 0} item produk aktif</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Menu Akses</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>📋 Buat pesanan baru</p>
                        <p>👁️ Lihat detail produk</p>
                        <p>📦 Kelola pengiriman</p>
                        <p>📞 Hubungi pelanggan</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    );

    // Staf Pengadaan Dashboard
    const renderStafPengadaanDashboard = () => (
        <div className="space-y-6">
            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                <StatCard
                    icon={<Truck className="h-6 w-6 text-blue-600" />}
                    label="Total Pengadaan"
                    value={stats.totalPengadaan || 0}
                    color="text-blue-600"
                />
                <StatCard
                    icon={<AlertCircle className="h-6 w-6 text-red-600" />}
                    label="Pengadaan Pending"
                    value={stats.pengadaanPending || 0}
                    color="text-red-600"
                />
                <StatCard
                    icon={<Package className="h-6 w-6 text-green-600" />}
                    label="Total Bahan Baku"
                    value={stats.totalBahanBaku || 0}
                    color="text-green-600"
                />
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Status Pengadaan</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="rounded-lg border border-orange-200 bg-orange-50 p-3 dark:border-orange-800 dark:bg-orange-950">
                            <p className="font-medium text-orange-900 dark:text-orange-100">⏳ Menunggu Approval</p>
                            <p className="mt-1 text-sm text-orange-700 dark:text-orange-200">{stats.pengadaanPending || 0} pengadaan</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Kegiatan Pengadaan</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>📝 Buat PO baru</p>
                        <p>✓ Tracking pengiriman</p>
                        <p>💰 Monitor biaya</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    );

    // Manajer Dashboard
    const renderManajerDashboard = () => (
        <div className="space-y-6">
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    icon={<Package className="h-6 w-6 text-blue-600" />}
                    label="Bahan Baku"
                    value={stats.totalBahanBaku || 0}
                    color="text-blue-600"
                />
                <StatCard
                    icon={<TrendingUp className="h-6 w-6 text-purple-600" />}
                    label="Pesanan"
                    value={stats.totalPesanan || 0}
                    color="text-purple-600"
                />
                <StatCard
                    icon={<Truck className="h-6 w-6 text-green-600" />}
                    label="Pengiriman"
                    value={stats.totalPengiriman || 0}
                    color="text-green-600"
                />
                <StatCard
                    icon={<AlertCircle className="h-6 w-6 text-red-600" />}
                    label="Kritis"
                    value={(stats.lowStockItems || 0) + (stats.pengadaanPending || 0)}
                    color="text-red-600"
                />
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Ringkasan Status</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="flex items-center justify-between border-b pb-2">
                            <span>Stok Rendah</span>
                            <span className="font-bold text-red-600">{stats.lowStockItems || 0}</span>
                        </div>
                        <div className="flex items-center justify-between border-b pb-2">
                            <span>Pengadaan Pending</span>
                            <span className="font-bold text-orange-600">{stats.pengadaanPending || 0}</span>
                        </div>
                        <div className="flex items-center justify-between">
                            <span>Pengiriman Pending</span>
                            <span className="font-bold text-yellow-600">{stats.pengirimanPending || 0}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Laporan Managerial</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>📊 Analisis performa operasional</p>
                        <p>👥 Kelola tim dan resource</p>
                        <p>📈 Monitor KPI</p>
                        <p>📋 Generate laporan</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    );

    // Select dashboard based on role
    const renderDashboard = () => {
        if (userRole.startsWith('R0') && userRole.endsWith('1')) {
            // Admin (R01)
            return renderAdminDashboard();
        } else if (userRole === 'R02') {
            // Staf Gudang
            return renderStafGudangDashboard();
        } else if (userRole === 'R05') {
            // Staf Penjualan
            return renderStafPenjualanDashboard();
        } else if (userRole === 'R04') {
            // Staf Pengadaan
            return renderStafPengadaanDashboard();
        } else if (userRole.match(/R0[789]/)) {
            // Manajer (R07, R08, R09, R10)
            return renderManajerDashboard();
        } else {
            // Default untuk role lain (RnD, Keuangan, dll)
            return renderManajerDashboard();
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="space-y-6">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">{getWelcomeMessage()}</h1>
                        <p className="mt-1 text-gray-500 dark:text-gray-400">Pantau dan kelola sistem dari dashboard Anda</p>
                    </div>

                    {renderDashboard()}
                </div>
            </div>
        </AppLayout>
    );
}
