import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';
import { 
    DocumentTextIcon, 
    ClockIcon, 
    ShoppingCartIcon, 
    TruckIcon, 
    CheckCircleIcon, 
    CheckBadgeIcon, 
    XCircleIcon 
} from '@heroicons/react/24/outline';

// 1. Interface disesuaikan untuk data Pembelian
interface Pembelian extends Record<string, unknown> {
    pembelian_id: string;
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
    const getStatusBadge = (status: string) => {
        const statusConfig = {
            draft: {
                label: 'Draft',
                icon: DocumentTextIcon,
                bgColor: 'bg-gray-100',
                textColor: 'text-gray-700',
                borderColor: 'border-gray-300',
            },
            menunggu: {
                label: 'Menunggu',
                icon: ClockIcon,
                bgColor: 'bg-yellow-100',
                textColor: 'text-yellow-700',
                borderColor: 'border-yellow-300',
            },
            dipesan: {
                label: 'Dipesan',
                icon: ShoppingCartIcon,
                bgColor: 'bg-blue-100',
                textColor: 'text-blue-700',
                borderColor: 'border-blue-300',
            },
            dikirim: {
                label: 'Dikirim',
                icon: TruckIcon,
                bgColor: 'bg-indigo-100',
                textColor: 'text-indigo-700',
                borderColor: 'border-indigo-300',
            },
            dikonfirmasi: {
                label: 'Dikonfirmasi',
                icon: CheckCircleIcon,
                bgColor: 'bg-purple-100',
                textColor: 'text-purple-700',
                borderColor: 'border-purple-300',
            },
            diterima: {
                label: 'Diterima',
                icon: CheckBadgeIcon,
                bgColor: 'bg-green-100',
                textColor: 'text-green-700',
                borderColor: 'border-green-300',
            },
            dibatalkan: {
                label: 'Dibatalkan',
                icon: XCircleIcon,
                bgColor: 'bg-red-100',
                textColor: 'text-red-700',
                borderColor: 'border-red-300',
            },
        };

        const config = statusConfig[status as keyof typeof statusConfig];
        if (!config) return <span className="text-gray-500 text-sm">{status}</span>;

        const IconComponent = config.icon;

        return (
            <div
                className={`flex items-center gap-1.5 rounded-full border px-3 py-1.5 ${config.bgColor} ${config.textColor} ${config.borderColor} text-sm font-medium whitespace-nowrap shadow-sm hover:scale-105 transition-transform duration-200`}
                title={config.label}
            >
                <IconComponent className="h-4 w-4 flex-shrink-0" />
                <span>{config.label}</span>
            </div>
        );
    };

    // Status update sekarang dilakukan di halaman edit

    // 5. Definisi kolom untuk tabel Pembelian
    const columns = useMemo(
        () => [
            {
                key: 'pembelian_id',
                label: 'ID',
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
                label: 'Tanggal Pembelian',
                sortable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => formatDate((item as Pembelian).tanggal_pembelian),
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
                    { value: 'menunggu', label: 'Menunggu' },
                    { value: 'dipesan', label: 'Dipesan' },
                    { value: 'dikirim', label: 'Dikirim' },
                    { value: 'dikonfirmasi', label: 'Dikonfirmasi' },
                    { value: 'diterima', label: 'Diterima' },
                    { value: 'dibatalkan', label: 'Dibatalkan' },
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
            // createViewAction<Pembelian>((item) => `/pembelian/${item.pembelian_id}`),
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
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/pembelian"
            actions={actions}
            flash={flash}
            deleteDialogTitle="Hapus Pembelian"
            deleteDialogMessage={(item) => `Apakah Anda yakin ingin menghapus pembelian dengan No. PO "${item.pembelian_id}"?`}
        />
    );
}
