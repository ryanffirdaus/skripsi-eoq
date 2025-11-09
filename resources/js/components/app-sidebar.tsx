import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
// Ikon yang diimpor diperbarui
import {
    Box,
    Briefcase,
    Building2,
    Component,
    Contact2,
    CreditCard,
    LayoutGrid,
    Package,
    PackageCheck,
    ShoppingBag,
    ShoppingCart,
    Truck,
    Users,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        roles: ['R01', 'R02', 'R03', 'R04', 'R05', 'R06', 'R07', 'R08', 'R09', 'R10'], // Semua role
    },
    {
        title: 'Pengguna',
        href: '/users',
        icon: Users, // Tetap untuk pengguna internal sistem
        roles: ['R01'], // Hanya Admin
    },
    {
        title: 'Bahan Baku',
        href: '/bahan-baku',
        icon: Component, // Lebih spesifik dari Atom
        roles: ['R01', 'R02', 'R07'], // Admin, Staf Gudang, Manajer Gudang
    },
    {
        title: 'Produk',
        href: '/produk',
        icon: Box, // Lebih spesifik dari Container
        roles: ['R01', 'R02', 'R07'], // Admin, Staf Gudang, Manajer Gudang
    },
    {
        title: 'Pelanggan',
        href: '/pelanggan',
        icon: Contact2, // Jelas membedakan dari Users
        roles: ['R01', 'R05'], // Admin, Staf Penjualan
    },
    {
        title: 'Pemasok',
        href: '/pemasok',
        icon: Building2,
        roles: ['R01', 'R04', 'R09'], // Admin, Staf Pengadaan, Manajer Pengadaan
    },
    {
        title: 'Pesanan',
        href: '/pesanan',
        icon: ShoppingCart, // Order dari pelanggan
        roles: ['R01', 'R05'], // Admin, Staf Penjualan
    },
    {
        title: 'Pengiriman',
        href: '/pengiriman',
        icon: Truck,
        roles: ['R01', 'R02', 'R07'], // Admin, Staf Gudang, Manajer Gudang
    },
    {
        title: 'Pengadaan',
        href: '/pengadaan',
        icon: Package,
        roles: ['R01', 'R02', 'R04', 'R07', 'R09'], // Admin, Staf Gudang, Staf Pengadaan, Manajer Gudang, Manajer Pengadaan
    },
    {
        title: 'Pembelian',
        href: '/pembelian',
        icon: ShoppingBag, // Pembelian ke pemasok
        roles: ['R01', 'R04', 'R09', 'R10'], // Admin, Staf Pengadaan, Manajer Pengadaan, Manajer Keuangan
    },
    {
        title: 'Penerimaan Bahan Baku',
        href: '/penerimaan-bahan-baku',
        icon: PackageCheck,
        roles: ['R01', 'R04', 'R02', 'R07', 'R09'], // Admin, Staf Pengadaan, Staf Gudang, Manajer Gudang, Manajer Pengadaan
    },
    {
        title: 'Transaksi Pembayaran',
        href: '/transaksi-pembayaran',
        icon: CreditCard,
        roles: ['R01', 'R06', 'R10'], // Admin, Staf Keuangan, Manajer Keuangan
    },
];

export function AppSidebar() {
    const page = usePage<{ auth?: { user?: { role_id?: string } } }>();
    const userRole = page.props.auth?.user?.role_id || '';

    const penugasanItems: NavItem[] = [];

    // Untuk Manajer RnD - bisa menugaskan
    if (userRole === 'R08') {
        penugasanItems.push({
            title: 'Penugasan Produksi',
            href: '/penugasan-produksi',
            icon: Briefcase,
            roles: ['R08'], // Manajer RnD
        });
    }

    // Untuk Staf RnD - hanya bisa lihat yang ditugaskan ke mereka
    if (userRole === 'R03') {
        penugasanItems.push({
            title: 'Penugasan',
            href: '/penugasan-produksi?mode=assigned',
            icon: Briefcase,
            roles: ['R03'], // Staf RnD
        });
    }

    // Untuk Admin - lihat semua
    if (userRole === 'R01') {
        penugasanItems.push({
            title: 'Penugasan Produksi',
            href: '/penugasan-produksi',
            icon: Briefcase,
            roles: ['R01'], // Admin
        });
    }

    const allItems = [...mainNavItems, ...penugasanItems];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={allItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
