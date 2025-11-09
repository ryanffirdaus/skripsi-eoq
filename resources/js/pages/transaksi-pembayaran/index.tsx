import { createDeleteAction, createEditAction, createViewAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { formatCurrency, formatDate } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';

// 1. Interface untuk data TransaksiPembayaran
interface TransaksiPembayaran extends Record<string, unknown> {
    transaksi_pembayaran_id: string;
    pembelian_id: string;
    pemasok_nama: string;
    tanggal_pembayaran: string;
    total_pembayaran: number;
    bukti_pembayaran: string;
    catatan?: string;
    created_at: string;
}

interface Pembelian {
    pembelian_id: string;
    pemasok_nama: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedTransaksiPembayaran {
    data: TransaksiPembayaran[];
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
    tanggal_dari?: string;
    tanggal_sampai?: string;
    pembelian_id?: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    per_page: number;
    [key: string]: string | number | undefined;
}

interface Props {
    transaksiPembayaran: PaginatedTransaksiPembayaran;
    filters: Filters;
    pembelians: Pembelian[];
    permissions?: {
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
        title: 'Transaksi Pembayaran',
        href: '/transaksi-pembayaran',
    },
];

export default function Index({ transaksiPembayaran, filters, pembelians, permissions, flash }: Props) {
    // 2. Definisi kolom untuk tabel TransaksiPembayaran
    const columns = useMemo(
        () => [
            {
                key: 'transaksi_pembayaran_id',
                label: 'ID Transaksi',
                sortable: true,
                defaultVisible: true,
            },
            {
                key: 'pembelian_id',
                label: 'No. PO',
                sortable: true,
                defaultVisible: true,
            },
            {
                key: 'pemasok_nama',
                label: 'Pemasok',
                sortable: false,
                defaultVisible: true,
            },
            {
                key: 'tanggal_pembayaran',
                label: 'Tgl. Pembayaran',
                sortable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => formatDate((item as TransaksiPembayaran).tanggal_pembayaran),
            },
            {
                key: 'total_pembayaran',
                label: 'Total Pembayaran',
                sortable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => formatCurrency((item as TransaksiPembayaran).total_pembayaran),
            },
            {
                key: 'created_at',
                label: 'Dicatat Pada',
                sortable: true,
                hideable: true,
                defaultVisible: false,
            },
        ],
        [],
    );

    // 3. Opsi filter untuk pembelian
    const filterOptions = useMemo(
        () => [
            {
                key: 'pembelian_id',
                label: 'Purchase Order',
                type: 'select' as const,
                options: [
                    { value: '', label: 'Semua PO' },
                    ...pembelians.map((p) => ({
                        value: p.pembelian_id,
                        label: `${p.pembelian_id} - ${p.pemasok_nama}`,
                    })),
                ],
            },
        ],
        [pembelians],
    );

    // 4. Aksi untuk setiap baris (view, edit, delete)
    const actions = useMemo(
        () => [
            createViewAction<TransaksiPembayaran>((item) => `/transaksi-pembayaran/${item.transaksi_pembayaran_id}`),
            ...(permissions?.canEdit
                ? [createEditAction<TransaksiPembayaran>((item) => `/transaksi-pembayaran/${item.transaksi_pembayaran_id}/edit`)]
                : []),
            ...(permissions?.canDelete
                ? [
                      createDeleteAction<TransaksiPembayaran>((item) => {
                          router.delete(`/transaksi-pembayaran/${item.transaksi_pembayaran_id}`);
                      }),
                  ]
                : []),
        ],
        [permissions?.canEdit, permissions?.canDelete],
    );

    return (
        <TableTemplate<TransaksiPembayaran>
            title="Manajemen Transaksi Pembayaran"
            breadcrumbs={breadcrumbs}
            data={transaksiPembayaran}
            columns={columns}
            createUrl={permissions?.canCreate ? '/transaksi-pembayaran/create' : undefined}
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/transaksi-pembayaran"
            actions={actions}
            flash={flash}
            idField="transaksi_pembayaran_id"
            deleteDialogTitle="Hapus Transaksi Pembayaran"
            deleteDialogMessage={(item) => `Apakah Anda yakin ingin menghapus transaksi pembayaran dengan ID ${item.transaksi_pembayaran_id}?`}
        />
    );
}
