import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { formatCurrency } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';

interface Produk {
    produk_id: string;
    nama_produk: string;
    satuan_produk: string;
}

interface Pelanggan {
    pelanggan_id: string;
    nama_pelanggan: string;
}

interface Pesanan extends Record<string, unknown> {
    pesanan_id: string;
    pelanggan_id: string;
    tanggal_pemesanan: string;
    total_harga: number;
    status: 'pending' | 'diproses' | 'dikirim' | 'selesai' | 'dibatalkan';
    pelanggan: Pelanggan;
    produk: Produk[];
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPesanan {
    data: Pesanan[];
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
    status?: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    per_page: number;
    [key: string]: string | number | undefined;
}

interface Props {
    pesanan: PaginatedPesanan;
    filters: Filters;
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pesanan',
        href: '/pesanan',
    },
];

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    diproses: 'bg-blue-100 text-blue-800',
    dikirim: 'bg-purple-100 text-purple-800',
    selesai: 'bg-green-100 text-green-800',
    dibatalkan: 'bg-red-100 text-red-800',
};

const statusLabels = {
    pending: 'Pending',
    diproses: 'Diproses',
    dikirim: 'Dikirim',
    diterima: 'Diterima',
    selesai: 'Selesai',
    dibatalkan: 'Dibatalkan',
};

export default function Index({ pesanan, filters, flash }: Props) {
    const columns = useMemo(
        () => [
            {
                key: 'pesanan_id',
                label: 'ID Pesanan',
                sortable: true,
                hideable: true,
                defaultVisible: true,
            },
            {
                key: 'pelanggan',
                label: 'Pelanggan',
                sortable: false,
                hideable: true,
                defaultVisible: true,
                render: (item: Pesanan) => item.pelanggan?.nama_pelanggan || '-',
            },
            {
                key: 'tanggal_pemesanan',
                label: 'Tanggal Pemesanan',
                sortable: true,
                hideable: true,
                defaultVisible: true,
                render: (item: Pesanan) => new Date(item.tanggal_pemesanan).toLocaleDateString('id-ID'),
            },
            {
                key: 'total_harga',
                label: 'Total Harga',
                sortable: true,
                hideable: true,
                defaultVisible: true,
                render: (item: Pesanan) => formatCurrency(item.total_harga),
            },
            {
                key: 'status',
                label: 'Status',
                sortable: true,
                hideable: true,
                defaultVisible: true,
                render: (item: Pesanan) => (
                    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColors[item.status]}`}>
                        {statusLabels[item.status]}
                    </span>
                ),
            },
            {
                key: 'produk_count',
                label: 'Jumlah Produk',
                sortable: false,
                hideable: true,
                defaultVisible: true,
                render: (item: Pesanan) => `${item.produk?.length || 0} item`,
            },
            {
                key: 'created_at',
                label: 'Dibuat',
                sortable: true,
                hideable: true,
                defaultVisible: false,
                render: (item: Pesanan) => new Date(item.created_at).toLocaleDateString('id-ID'),
            },
        ],
        [],
    );

    const filterOptions = useMemo(
        () => [
            {
                key: 'status',
                label: 'Status',
                type: 'select' as const,
                placeholder: 'Semua Status',
                options: [
                    { value: 'pending', label: 'Pending' },
                    { value: 'diproses', label: 'Diproses' },
                    { value: 'dikirim', label: 'Dikirim' },
                    { value: 'selesai', label: 'Selesai' },
                    { value: 'dibatalkan', label: 'Dibatalkan' },
                ],
            },
        ],
        [],
    );

    const actions = useMemo(
        () => [
            // createViewAction<Pesanan>((item) => `/pesanan/${item.pesanan_id}`),
            createEditAction<Pesanan>((item) => `/pesanan/${item.pesanan_id}/edit`),
            createDeleteAction<Pesanan>((item) => {
                router.delete(`/pesanan/${item.pesanan_id}`, {
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
        <TableTemplate<Pesanan>
            title="Pesanan Management"
            breadcrumbs={breadcrumbs}
            data={pesanan}
            columns={columns}
            createUrl="/pesanan/create"
            createButtonText="Tambah Pesanan"
            searchPlaceholder="Cari pesanan..."
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/pesanan"
            actions={actions}
            flash={flash}
            idField="pesanan_id"
        />
    );
}
