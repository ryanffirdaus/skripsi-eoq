import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';

// Interface disesuaikan untuk Supplier
interface Supplier extends Record<string, unknown> {
    supplier_id: string;
    nama_supplier: string;
    kontak_person: string;
    email: string;
    telepon: string;
    alamat: string;
    kota: string;
    provinsi: string;
    kode_pos: string;
    status: 'active' | 'inactive';
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

// Interface paginasi disesuaikan untuk Supplier
interface PaginatedSupplier {
    data: Supplier[];
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
    [key: string]: string | number | undefined;
}

interface Props {
    supplier: PaginatedSupplier; // Menggunakan data supplier
    filters: Filters;
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

// Breadcrumbs diperbarui untuk Supplier
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Supplier',
        href: '/supplier',
    },
];

export default function Index({ supplier, filters, flash }: Props) {
    console.log('Supplier Index render', { supplier, filters, flash });

    const columns = useMemo(
        () => [
            {
                key: 'supplier_id',
                label: 'ID Supplier',
                sortable: true,
                defaultVisible: true,
            },
            {
                key: 'nama_supplier',
                label: 'Nama Supplier',
                sortable: true,
                defaultVisible: true,
            },
            {
                key: 'kontak_person',
                label: 'Kontak Person',
                sortable: true,
                defaultVisible: true,
            },
            {
                key: 'email',
                label: 'Email',
                sortable: true,
                defaultVisible: true,
            },
            {
                key: 'telepon',
                label: 'Telepon',
                sortable: true,
                defaultVisible: true,
            },
            {
                key: 'kota',
                label: 'Kota',
                sortable: true,
                defaultVisible: true,
            },
            {
                key: 'status',
                label: 'Status',
                sortable: true,
                defaultVisible: true,
                render: (item: Supplier) => (
                    <Badge variant={item.status === 'active' ? 'default' : 'secondary'}>
                        {item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                    </Badge>
                ),
            },
            {
                key: 'alamat',
                label: 'Alamat',
                sortable: false,
                defaultVisible: false, // Disembunyikan secara default untuk menghemat ruang
                render: (item: Supplier) => (
                    <div className="max-w-xs truncate" title={item.alamat}>
                        {item.alamat}
                    </div>
                ),
            },
        ],
        [],
    );

    // Actions disesuaikan untuk Supplier
    const actions = useMemo(
        () => [
            createEditAction<Supplier>((item) => `/supplier/${item.supplier_id}/edit`),
            createDeleteAction<Supplier>((item) => {
                router.delete(`/supplier/${item.supplier_id}`, {
                    preserveState: false, // Reload halaman untuk menampilkan flash message
                    onError: (errors) => {
                        console.error('Delete failed:', errors);
                    },
                });
            }),
        ],
        [],
    );

    return (
        <TableTemplate<Supplier>
            title="Supplier Management"
            breadcrumbs={breadcrumbs}
            data={supplier}
            columns={columns}
            createUrl="/supplier/create"
            createButtonText="Add Supplier"
            searchPlaceholder="Search by name, contact, email..."
            filters={filters}
            baseUrl="/supplier"
            actions={actions}
            flash={flash}
            deleteDialogTitle="Delete Supplier"
            deleteDialogMessage={(item) => `Are you sure you want to delete supplier "${item.nama_supplier}"? This action cannot be undone.`}
            getItemName={(item) => item.nama_supplier}
        />
    );
}
