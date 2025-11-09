import { SidebarGroup, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage<{ auth?: { user?: { role_id?: string } } }>();
    const currentUrl = page.url || '';
    const userRole = page.props.auth?.user?.role_id || '';

    // Filter items based on user role
    const visibleItems = items.filter((item) => {
        // If no roles specified, show to everyone
        if (!item.roles || item.roles.length === 0) {
            return true;
        }
        // Show if user's role is in the allowed roles
        return item.roles.includes(userRole);
    });

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarMenu>
                {visibleItems.map((item) => {
                    const href = typeof item.href === 'string' ? item.href : item.href?.url || '#';
                    const isActive = currentUrl.startsWith(href);

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton asChild isActive={isActive} tooltip={{ children: item.title }}>
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
