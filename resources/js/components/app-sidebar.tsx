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
    },
    {
        title: 'Users',
        href: '/users',
        icon: Users, // Tetap untuk pengguna internal sistem
        roles: ['R01'], // Hanya Admin
    },
    {
        title: 'Bahan Baku',
        href: '/bahan-baku',
        icon: Component, // Lebih spesifik dari Atom
        roles: ['R01', 'R02', 'R08'], // Admin, Staf Gudang, Manajer Gudang
    },
    {
        title: 'Produk',
        href: '/produk',
        icon: Box, // Lebih spesifik dari Container
        roles: ['R01', 'R02', 'R08'], // Admin, Staf Gudang, Manajer Gudang
    },
    {
        title: 'Pelanggan',
        href: '/pelanggan',
        icon: Contact2, // Jelas membedakan dari Users
        roles: ['R01', 'R05', 'R08', 'R09', 'R10', 'R11'], // Admin, Staf Penjualan, Managers
    },
    {
        title: 'Pemasok',
        href: '/pemasok',
        icon: Building2,
        roles: ['R01', 'R04', 'R10'], // Admin, Staf Pengadaan, Manajer Pengadaan
    },
    {
        title: 'Pesanan',
        href: '/pesanan',
        icon: ShoppingCart, // Order dari pelanggan
        roles: ['R01', 'R05', 'R08', 'R09', 'R10', 'R11'], // Admin, Staf Penjualan, Managers
    },
    {
        title: 'Pengiriman',
        href: '/pengiriman',
        icon: Truck,
        roles: ['R01', 'R02', 'R08'], // Admin, Staf Gudang, Manajer Gudang
    },
    {
        title: 'Pengadaan',
        href: '/pengadaan',
        icon: Package,
        roles: ['R01', 'R04', 'R10'], // Admin, Staf Pengadaan, Manajer Pengadaan
    },
    {
        title: 'Pembelian',
        href: '/pembelian',
        icon: ShoppingBag, // Pembelian ke pemasok
        roles: ['R01', 'R04', 'R10'], // Admin, Staf Pengadaan, Manajer Pengadaan
    },
    {
        title: 'Penerimaan Bahan Baku',
        href: '/penerimaan-bahan-baku',
        icon: PackageCheck,
        roles: ['R01', 'R04', 'R02', 'R10', 'R08'], // Admin, Staf Pengadaan, Staf Gudang, Managers
    },
    {
        title: 'Transaksi Pembayaran',
        href: '/transaksi-pembayaran',
        icon: CreditCard,
        roles: ['R01', 'R07', 'R11'], // Admin, Staf Keuangan, Manajer Keuangan
    },
];

export function AppSidebar() {
    const page = usePage<{ auth?: { user?: { role_id?: string } } }>();
    const userRole = page.props.auth?.user?.role_id || '';
    const isAdmin = ['ROLE001', 'ROLE002'].includes(userRole);

    // Dynamic items berdasarkan role
    const penugasanItems: NavItem[] = isAdmin
        ? [
              {
                  title: 'Penugasan',
                  href: '/penugasan-produksi',
                  icon: Briefcase,
                  roles: ['R01', 'R09'], // Admin, Manajer RnD
              },
              {
                  title: 'Yang Ditugaskan',
                  href: '/penugasan-produksi?mode=assigned',
                  icon: Briefcase,
                  roles: ['R01', 'R09'], // Admin, Manajer RnD
              },
          ]
        : [
              {
                  title: 'Penugasan Produksi',
                  href: '/penugasan-produksi',
                  icon: Briefcase,
                  roles: ['R01', 'R03', 'R09'], // Admin, Staf RnD, Manajer RnD
              },
          ];

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
