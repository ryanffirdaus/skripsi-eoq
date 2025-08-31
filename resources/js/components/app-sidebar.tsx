import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { Atom, Container, LayoutGrid, Package, ShoppingCart, Truck, Users } from 'lucide-react';
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
        icon: Users,
    },
    {
        title: 'Bahan Baku',
        href: '/bahan-baku',
        icon: Atom,
    },
    {
        title: 'Produk',
        href: '/produk',
        icon: Container,
    },
    {
        title: 'Pelanggan',
        href: '/pelanggan',
        icon: Users,
    },
    {
        title: 'Pesanan',
        href: '/pesanan',
        icon: ShoppingCart,
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
        icon: ShoppingCart,
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
