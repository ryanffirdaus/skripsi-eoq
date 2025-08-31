import TableTemplate from '@/components/table/table-template';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';

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
        },
        {
            key: 'email',
            label: 'Email',
            sortable: true,
        },
        {
            key: 'role',
            label: 'Role',
            sortable: true,
            render: (user: User) => user.role?.name || '-',
        },
        {
            key: 'user_id',
            label: 'Kode User',
            sortable: true,
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

    const actions = [
        {
            label: 'Edit',
            variant: 'outline' as const,
            onClick: (item: User) => {
                router.visit(`/users/${item.user_id}/edit`);
            },
        },
        {
            label: 'Delete',
            variant: 'destructive' as const,
            onClick: (item: User) => {
                router.delete(`/users/${item.user_id}`, {
                    preserveState: false,
                    onError: (errors) => {
                        console.error('Delete failed:', errors);
                    },
                });
            },
        },
    ];

    return (
        <TableTemplate<User>
            title="Users Management"
            breadcrumbs={breadcrumbs}
            data={users}
            columns={columns}
            createUrl="/users/create"
            createButtonText="Add User"
            searchPlaceholder="Search by name or email..."
            filters={filters}
            filterOptions={filterOptions}
            actions={actions}
            baseUrl="/users"
            flash={flash}
            deleteDialogTitle="Delete User"
            deleteDialogMessage={(user) => `Are you sure you want to delete user "${user.nama_lengkap}"? This action cannot be undone.`}
            getItemName={(user) => user.nama_lengkap}
        />
    );
}
