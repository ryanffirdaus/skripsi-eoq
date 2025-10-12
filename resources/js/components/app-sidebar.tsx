import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
// Ikon yang diimpor diperbarui
import {
    Box,
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
    },
    {
        title: 'Bahan Baku',
        href: '/bahan-baku',
        icon: Component, // Lebih spesifik dari Atom
    },
    {
        title: 'Produk',
        href: '/produk',
        icon: Box, // Lebih spesifik dari Container
    },
    {
        title: 'Pelanggan',
        href: '/pelanggan',
        icon: Contact2, // Jelas membedakan dari Users
    },
    {
        title: 'Pemasok',
        href: '/pemasok',
        icon: Building2,
    },
    {
        title: 'Pesanan',
        href: '/pesanan',
        icon: ShoppingCart, // Order dari pelanggan
    },
    {
        title: 'Pengiriman',
        href: '/pengiriman',
        icon: Truck,
    },
    {
        title: 'Pengadaan',
        href: '/pengadaan',
        icon: Package,
    },
    {
        title: 'Pembelian',
        href: '/pembelian',
        icon: ShoppingBag, // Pembelian ke pemasok
    },
    {
        title: 'Penerimaan Bahan Baku',
        href: '/penerimaan-bahan-baku',
        icon: PackageCheck,
    },
    {
        title: 'Transaksi Pembayaran',
        href: '/transaksi-pembayaran',
        icon: CreditCard,
    },
];

export function AppSidebar() {
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
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
