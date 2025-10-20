import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';

interface Role {
    role_id: string;
    name: string;
}

interface User extends Record<string, unknown> {
    user_id: string;
    nama_lengkap: string;
    email: string;
    role_id?: string;
    role?: Role | null;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
}

interface Filters {
    search?: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    per_page: number;
    role_id?: string;
    [key: string]: string | number | undefined;
}

interface Props {
    users: PaginatedUsers;
    roles: Role[];
    filters: Filters;
    flash?: {
        message?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];

export default function Index({ users, roles, filters, flash }: Props) {
    const columns = [
        {
            key: 'nama_lengkap',
            label: 'Name',
            sortable: true,
            hideable: true,
            defaultVisible: true,
        },
        {
            key: 'email',
            label: 'Email',
            sortable: true,
            hideable: true,
            defaultVisible: true,
        },
        {
            key: 'role',
            label: 'Role',
            sortable: true,
            hideable: true,
            defaultVisible: true,
            render: (user: User) => user.role?.name || '-',
        },
        {
            key: 'user_id',
            label: 'Kode User',
            sortable: true,
            hideable: true,
            defaultVisible: false, // Hidden by default
        },
        {
            key: 'created_at',
            label: 'Created At',
            sortable: true,
            hideable: true,
            defaultVisible: false, // Hidden by default
            render: (user: User) => new Date(user.created_at).toLocaleDateString('id-ID'),
        },
    ];

    const filterOptions = [
        {
            key: 'role_id',
            label: 'Role',
            type: 'select' as const,
            placeholder: 'All Roles',
            options: roles.map((role) => ({
                value: role.role_id,
                label: role.name,
            })),
        },
    ];

    const actions = useMemo(
        () => [
            // createViewAction<User>((item) => `/users/${item.user_id}`),
            createEditAction<User>((item) => `/users/${item.user_id}/edit`),
            createDeleteAction<User>((item) => {
                router.delete(`/users/${item.user_id}`, {
                    preserveState: false,
                    onError: (errors) => {
                        console.error('Delete failed:', errors);
                    },
                });
            }),
        ],
        [],
    );

    return (
        <TableTemplate<User>
            title="Manajemen Pengguna"
            breadcrumbs={breadcrumbs}
            data={users}
            columns={columns}
            createUrl="/users/create"
            createButtonText="Tambah"
            searchPlaceholder="Search by name or email..."
            filters={filters}
            filterOptions={filterOptions}
            actions={actions}
            baseUrl="/users"
            flash={flash}
            deleteDialogTitle="Hapus Pengguna"
        />
    );
}
