import { TableTemplate, type TableColumn } from '@/components/table/table-template';
import { createDeleteAction, createEditAction, type ActionItem } from '@/components/table/table-utils';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';

// Interface disesuaikan untuk Pemasok
interface Pemasok extends Record<string, unknown> {
    pemasok_id: string;
    nama_pemasok: string;
    narahubung: string | null;
    email: string | null;
    telepon: string | null;
    alamat: string | null;
    status: 'active' | 'inactive';
    catatan: string | null;
    created_at: string;
    updated_at: string;
}

// Filters interface
interface Filters {
    search?: string;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    per_page?: number;
}

// Interface paginasi disesuaikan untuk Pemasok
interface PaginatedPemasok {
    data: Pemasok[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

// Flash message interface
interface FlashMessage {
    message: string | null;
    type: 'success' | 'error' | 'warning' | 'info';
}

// Props interface
interface Props {
    pemasok: PaginatedPemasok; // Menggunakan data pemasok
    filters: Filters;
    flash: FlashMessage;
}

// Breadcrumbs diperbarui untuk Pemasok
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pemasok',
        href: '/pemasok',
    },
];

export default function Index({ pemasok, filters, flash }: Props) {
    console.log('Pemasok Index render', { pemasok, filters, flash });

    // Define columns for the table
    const columns: TableColumn<Pemasok>[] = [
        {
            key: 'pemasok_id',
            label: 'ID Pemasok',
            sortable: true,
            searchable: false,
        },
        {
            key: 'nama_pemasok',
            label: 'Nama Pemasok',
            sortable: true,
            searchable: true,
        },
        {
            key: 'narahubung',
            label: 'Kontak Person',
            sortable: true,
            searchable: false,
        },
        {
            key: 'email',
            label: 'Email',
            sortable: true,
            searchable: false,
        },
        {
            key: 'telepon',
            label: 'Telepon',
            sortable: true,
            searchable: false,
        },
        {
            key: 'alamat',
            label: 'Alamat',
            sortable: false,
            searchable: false,
        },
        {
            key: 'status',
            label: 'Status',
            sortable: true,
            searchable: false,
            render: (item: Pemasok) => (
                <Badge variant={item.status === 'active' ? 'default' : 'destructive'}>{item.status === 'active' ? 'Aktif' : 'Tidak Aktif'}</Badge>
            ),
        },
        {
            key: 'created_at',
            label: 'Dibuat Pada',
            sortable: true,
            searchable: false,
            render: (item: Pemasok) => <span>{new Date(item.created_at).toLocaleDateString('id-ID')}</span>,
        },
    ];

    // Actions disesuaikan untuk Pemasok
    const actions: ActionItem<Pemasok>[] = [
        // Edit action
        createEditAction<Pemasok>((item) => `/pemasok/${item.pemasok_id}/edit`),
        // Delete action
        createDeleteAction<Pemasok>((item) => {
            router.delete(`/pemasok/${item.pemasok_id}`, {
                preserveScroll: true,
            });
        }),
    ];

    return (
        <TableTemplate<Pemasok>
            title="Manajemen Pemasok"
            description="Kelola data pemasok bahan baku"
            data={pemasok}
            columns={columns}
            createUrl="/pemasok/create"
            createButtonText="Tambah Pemasok"
            actions={actions}
            filters={filters}
            baseUrl="/pemasok"
            breadcrumbs={breadcrumbs}
            deleteDialogTitle="Hapus Pemasok"
            deleteDialogMessage={(item) => `Apakah Anda yakin ingin menghapus pemasok "${item.nama_pemasok}"? Tindakan ini tidak dapat dibatalkan.`}
            getItemName={(item) => item.nama_pemasok}
        />
    );
}
