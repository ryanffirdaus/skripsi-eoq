import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';
import { 
    ClockIcon, 
    CheckCircleIcon, 
    ShoppingCartIcon, 
    BeakerIcon,
    CogIcon,
    TruckIcon,
    CheckBadgeIcon,
    XCircleIcon,
    ArchiveBoxIcon
} from '@heroicons/react/24/outline';

interface Pesanan extends Record<string, unknown> {
    pesanan_id: string;
    pelanggan_id: string;
    nama_pelanggan: string;
    tanggal_pemesanan: string;
    total_harga: number;
    jumlah_produk: number;
    status: 'menunggu' | 'dikonfirmasi' | 'menunggu_pengadaan' | 'siap_produksi' | 'sedang_produksi' | 'siap_dikirim' | 'dikirim' | 'selesai' | 'dibatalkan';
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

export default function Index({ pesanan, filters, permissions, flash }: Props) {
    const getStatusBadge = (status: string) => {
        const statusConfig = {
            menunggu: {
                label: 'Menunggu',
                icon: ClockIcon,
                bgColor: 'bg-yellow-100',
                textColor: 'text-yellow-700',
                borderColor: 'border-yellow-300',
            },
            dikonfirmasi: {
                label: 'Dikonfirmasi',
                icon: CheckCircleIcon,
                bgColor: 'bg-blue-100',
                textColor: 'text-blue-700',
                borderColor: 'border-blue-300',
            },
            menunggu_pengadaan: {
                label: 'Menunggu Pengadaan',
                icon: ShoppingCartIcon,
                bgColor: 'bg-orange-100',
                textColor: 'text-orange-700',
                borderColor: 'border-orange-300',
            },
            siap_produksi: {
                label: 'Siap Produksi',
                icon: BeakerIcon,
                bgColor: 'bg-cyan-100',
                textColor: 'text-cyan-700',
                borderColor: 'border-cyan-300',
            },
            sedang_produksi: {
                label: 'Sedang Produksi',
                icon: CogIcon,
                bgColor: 'bg-purple-100',
                textColor: 'text-purple-700',
                borderColor: 'border-purple-300',
            },
            siap_dikirim: {
                label: 'Siap Dikirim',
                icon: ArchiveBoxIcon,
                bgColor: 'bg-indigo-100',
                textColor: 'text-indigo-700',
                borderColor: 'border-indigo-300',
            },
            dikirim: {
                label: 'Dikirim',
                icon: TruckIcon,
                bgColor: 'bg-teal-100',
                textColor: 'text-teal-700',
                borderColor: 'border-teal-300',
            },
            selesai: {
                label: 'Selesai',
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
                render: (item: Pesanan) => getStatusBadge(item.status),
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
                    { value: 'menunggu_pengadaan', label: 'Menunggu Pengadaan' },
                    { value: 'siap_produksi', label: 'Siap Produksi' },
                    { value: 'sedang_produksi', label: 'Sedang Produksi' },
                    { value: 'siap_dikirim', label: 'Siap Dikirim' },
                    { value: 'dikirim', label: 'Dikirim' },
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
