import { createDeleteAction, createEditAction, createViewAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDate } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';

// 1. Interface disesuaikan untuk data Pembelian
interface Pembelian extends Record<string, unknown> {
    pembelian_id: string;
    nomor_po: string;
    pengadaan_id: string;
    pemasok_nama: string;
    tanggal_pembelian: string;
    tanggal_kirim?: string;
    total_biaya: number;
    status: string;
    status_label: string;
    dibuat_oleh: string;
    can_edit: boolean;
    can_cancel: boolean;
    created_at: string;
}

interface Pemasok {
    pemasok_id: string;
    nama_pemasok: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPembelian {
    data: Pembelian[];
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
    pemasok_id?: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    per_page: number;
    [key: string]: string | number | undefined;
}

interface Props {
    pembelian: PaginatedPembelian;
    filters: Filters;
    pemasoks: Pemasok[]; // Menambahkan pemasoks untuk filter
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
        title: 'Pembelian',
        href: '/pembelian',
    },
];

export default function Index({ pembelian, filters, pemasoks, permissions, flash }: Props) {
    // 2. Badge disesuaikan untuk status Pembelian
    const getStatusBadge = (status: string) => {
        const statusColors = {
            draft: 'outline',
            sent: 'secondary',
            confirmed: 'default',
            partially_received: 'default',
            fully_received: 'secondary',
            cancelled: 'destructive',
        } as const;

        return (
            <Badge variant={statusColors[status as keyof typeof statusColors] || 'outline'}>
                {status === 'draft' && 'Draft'}
                {status === 'sent' && 'Terkirim'}
                {status === 'confirmed' && 'Dikonfirmasi'}
                {status === 'partially_received' && 'Diterima Sebagian'}
                {status === 'fully_received' && 'Diterima Lengkap'}
                {status === 'cancelled' && 'Dibatalkan'}
            </Badge>
        );
    };

    // Status update sekarang dilakukan di halaman edit

    // 5. Definisi kolom untuk tabel Pembelian
    const columns = useMemo(
        () => [
            {
                key: 'nomor_po',
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
                key: 'tanggal_pembelian',
                label: 'Tgl. Pembelian',
                sortable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => formatDate((item as Pembelian).tanggal_pembelian),
            },
            {
                key: 'total_biaya',
                label: 'Total Biaya',
                sortable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => formatCurrency((item as Pembelian).total_biaya),
            },
            {
                key: 'status',
                label: 'Status',
                sortable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => {
                    const po = item as Pembelian;
                    return getStatusBadge(po.status);
                },
            },
            {
                key: 'dibuat_oleh',
                label: 'Dibuat Oleh',
                sortable: false,
                hideable: true,
                defaultVisible: false,
            },
        ],
        [],
    );

    // 6. Opsi filter untuk status dan pemasok
    const filterOptions = useMemo(
        () => [
            {
                key: 'status',
                label: 'Status',
                type: 'select' as const,
                options: [
                    { value: '', label: 'Semua Status' },
                    { value: 'draft', label: 'Draft' },
                    { value: 'sent', label: 'Terkirim' },
                    { value: 'confirmed', label: 'Dikonfirmasi' },
                    { value: 'partially_received', label: 'Diterima Sebagian' },
                    { value: 'fully_received', label: 'Diterima Lengkap' },
                    { value: 'cancelled', label: 'Dibatalkan' },
                ],
            },
            {
                key: 'pemasok_id',
                label: 'Pemasok',
                type: 'select' as const,
                options: [
                    { value: '', label: 'Semua Pemasok' },
                    ...(pemasoks && Array.isArray(pemasoks) ? pemasoks.map((s) => ({ value: s.pemasok_id, label: s.nama_pemasok })) : []),
                ],
            },
        ],
        [pemasoks],
    );

    // 7. Aksi untuk setiap baris (view, edit, delete)
    const actions = useMemo(
        () => [
            createViewAction<Pembelian>((item) => `/pembelian/${item.pembelian_id}`),
            ...(permissions?.canEdit
                ? [
                      createEditAction<Pembelian>(
                          (item) => `/pembelian/${item.pembelian_id}/edit`,
                          (item) => item.can_edit,
                      ),
                  ]
                : []),
            ...(permissions?.canDelete
                ? [
                      createDeleteAction<Pembelian>(
                          (item) => {
                              router.delete(`/pembelian/${item.pembelian_id}`);
                          },
                          (item) => item.can_cancel,
                      ),
                  ]
                : []),
        ],
        [permissions?.canEdit, permissions?.canDelete],
    );

    return (
        <TableTemplate<Pembelian>
            title="Manajemen Pembelian"
            breadcrumbs={breadcrumbs}
            data={pembelian}
            columns={columns}
            createUrl={permissions?.canCreate ? '/pembelian/create' : undefined}
            searchPlaceholder="Cari No. PO, pemasok..."
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/pembelian"
            actions={actions}
            flash={flash}
            deleteDialogTitle="Hapus Pembelian"
            deleteDialogMessage={(item) => `Apakah Anda yakin ingin menghapus pembelian dengan No. PO "${item.nomor_po}"?`}
        />
    );
}
