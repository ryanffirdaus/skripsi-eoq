import { useMemo } from 'react';
import { usePage } from '@inertiajs/react';

interface Permissions {
    canCreate?: boolean;
    canEdit?: boolean;
    canDelete?: boolean;
}

/**
 * Hook untuk mengecek permission di frontend
 *
 * Usage:
 * const { canEdit, canDelete } = usePermissions();
 *
 * atau dari props component Inertia:
 * const { canEdit, canDelete } = usePermissions(pageProps.permissions);
 */
export function usePermissions(propPermissions?: Permissions) {
    const page = usePage();

    const permissions = useMemo(() => {
        // Jika ada permissions dari props, gunakan itu
        if (propPermissions) {
            return propPermissions;
        }

        // Coba ambil dari page props
        const pageProps = page.props as Record<string, unknown>;
        if (pageProps?.permissions) {
            return pageProps.permissions as Permissions;
        }

        // Default: tidak ada permission
        return {
            canCreate: false,
            canEdit: false,
            canDelete: false,
        };
    }, [propPermissions, page.props]);

    return permissions;
}

/**
 * Hook untuk mendapatkan info user dan role
 */
export function useCurrentUser() {
    const page = usePage();

    const userData = useMemo(() => {
        const pageProps = page.props as Record<string, unknown>;
        const auth = pageProps?.auth as Record<string, unknown>;
        return auth?.user as Record<string, unknown> || null;
    }, [page.props]);

    const role = useMemo(() => {
        if (!userData) return null;
        return (userData.role as Record<string, string>)?.name || null;
    }, [userData]);

    return {
        user: userData,
        role,
        roleId: userData?.role_id as string,
    };
}

/**
 * Hook untuk check apakah user punya role tertentu
 */
export function useHasRole(...roleIds: string[]) {
    const { roleId } = useCurrentUser();
    return roleIds.includes(roleId || '');
}

/**
 * Hook untuk check apakah user adalah salah satu dari Staf/Manajer Gudang
 */
export function useIsGudangRelated() {
    return useHasRole('R02', 'R07');
}

/**
 * Hook untuk check apakah user adalah Admin
 */
export function useIsAdmin() {
    return useHasRole('R01');
}

/**
 * Hook untuk check apakah user adalah Staf Penjualan
 */
export function useIsStafPenjualan() {
    return useHasRole('R05');
}

export default usePermissions;
