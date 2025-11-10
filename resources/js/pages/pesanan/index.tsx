import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';

interface Pesanan extends Record<string, unknown> {
    pesanan_id: string;
    pelanggan_id: string;
    nama_pelanggan: string;
    tanggal_pemesanan: string;
    total_harga: number;
    jumlah_produk: number;
    status: 'menunggu' | 'dikonfirmasi' | 'diproses' | 'siap' | 'dikirim' | 'diterima' | 'dibatalkan' | 'selesai';
    created_at: string;
    updated_at: string;
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
    permissions: {
        canCreate?: boolean;
        canEdit?: boolean;
        canDelete?: boolean;
    };
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
    menunggu: 'bg-yellow-100 text-yellow-800',
    dikonfirmasi: 'bg-blue-100 text-blue-800',
    diproses: 'bg-purple-100 text-purple-800',
    siap: 'bg-cyan-100 text-cyan-800',
    dikirim: 'bg-indigo-100 text-indigo-800',
    diterima: 'bg-teal-100 text-teal-800',
    selesai: 'bg-green-100 text-green-800',
    dibatalkan: 'bg-red-100 text-red-800',
};

const statusLabels = {
    menunggu: 'Menunggu',
    dikonfirmasi: 'Dikonfirmasi',
    diproses: 'Diproses',
    siap: 'Siap',
    dikirim: 'Dikirim',
    diterima: 'Diterima',
    selesai: 'Selesai',
    dibatalkan: 'Dibatalkan',
};

export default function Index({ pesanan, filters, permissions, flash }: Props) {
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
                sortable: true,
                hideable: true,
                defaultVisible: true,
                render: (item: Pesanan) => item.nama_pelanggan || '-',
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
                    { value: 'menunggu', label: 'Menunggu' },
                    { value: 'dikonfirmasi', label: 'Dikonfirmasi' },
                    { value: 'diproses', label: 'Diproses' },
                    { value: 'siap', label: 'Siap' },
                    { value: 'dikirim', label: 'Dikirim' },
                    { value: 'diterima', label: 'Diterima' },
                    { value: 'selesai', label: 'Selesai' },
                    { value: 'dibatalkan', label: 'Dibatalkan' },
                ],
            },
        ],
        [],
    );

    // Actions - hanya tampil jika ada permission
    const actions = useMemo(
        () =>
            permissions.canEdit || permissions.canDelete
                ? [
                      ...(permissions.canEdit ? [createEditAction<Pesanan>((item) => `/pesanan/${item.pesanan_id}/edit`)] : []),
                      ...(permissions.canDelete
                          ? [
                                createDeleteAction<Pesanan>((item) => {
                                    router.delete(`/pesanan/${item.pesanan_id}`, {
                                        preserveState: false,
                                        onError: (errors) => {
                                            console.error('Delete failed:', errors);
                                        },
                                    });
                                }),
                            ]
                          : []),
                  ]
                : [],
        [permissions.canEdit, permissions.canDelete],
    );

    return (
        <TableTemplate<Pesanan>
            title="Manajemen Pesanan"
            breadcrumbs={breadcrumbs}
            data={pesanan}
            columns={columns}
            createUrl={permissions.canCreate ? '/pesanan/create' : undefined}
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/pesanan"
            actions={actions}
            flash={flash}
            deleteDialogTitle="Hapus Pesanan"
            deleteDialogMessage={(item) => `Apakah Anda yakin ingin menghapus pesanan dengan ID "${item.pesanan_id}"?`}
        />
    );
}
