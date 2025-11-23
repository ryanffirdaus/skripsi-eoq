import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';
import { ClockIcon, TruckIcon, CheckBadgeIcon, XCircleIcon } from '@heroicons/react/24/outline';

interface Pesanan {
    pesanan_id: string;
    total_harga: number;
    pelanggan?: {
        nama: string;
    };
}

interface Pengiriman extends Record<string, unknown> {
    pengiriman_id: string;
    pesanan_id: string;
    nomor_resi: string;
    kurir: string;
    biaya_pengiriman: number;
    estimasi_hari: number;
    status: string;
    status_label: string;
    tanggal_kirim?: string;
    tanggal_diterima?: string;
    pesanan?: Pesanan;
    created_at?: string;
    updated_at?: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPengiriman {
    data: Pengiriman[];
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
    kurir?: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    per_page: number;
    [key: string]: string | number | undefined;
}

interface Props {
    pengiriman: PaginatedPengiriman;
    filters: Filters;
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengiriman',
        href: '/pengiriman',
    },
];

export default function Index({ pengiriman, filters, flash }: Props) {
    const getStatusBadge = (status: string) => {
        const statusConfig = {
            menunggu: {
                label: 'Menunggu',
                icon: ClockIcon,
                bgColor: 'bg-yellow-100',
                textColor: 'text-yellow-700',
                borderColor: 'border-yellow-300',
            },
            dikirim: {
                label: 'Dikirim',
                icon: TruckIcon,
                bgColor: 'bg-indigo-100',
                textColor: 'text-indigo-700',
                borderColor: 'border-indigo-300',
            },
            selesai: {
                label: 'Selesai',
                icon: CheckBadgeIcon,
                bgColor: 'bg-teal-100',
                textColor: 'text-teal-700',
                borderColor: 'border-teal-300',
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
                key: 'pengiriman_id',
                label: 'ID',
                sortable: true,
                hideable: true,
                defaultVisible: true,
            },
            {
                key: 'pesanan_id',
                label: 'ID Pesanan',
                sortable: true,
                hideable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => {
                    const pengiriman = item as Pengiriman;
                    return (
                        <div>
                            <div className="font-medium">{pengiriman.pesanan_id}</div>
                            {pengiriman.pesanan?.pelanggan?.nama && <div className="text-sm text-gray-500">{pengiriman.pesanan.pelanggan.nama}</div>}
                        </div>
                    );
                },
            },
            {
                key: 'kurir',
                label: 'Kurir',
                sortable: true,
                hideable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => {
                    const pengiriman = item as Pengiriman;
                    return (
                        <div>
                            <div className="font-medium">{pengiriman.kurir}</div>
                        </div>
                    );
                },
            },
            {
                key: 'status',
                label: 'Status',
                sortable: true,
                hideable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => {
                    const pengiriman = item as Pengiriman;
                    return getStatusBadge(pengiriman.status);
                },
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
                options: [
                    { value: '', label: 'Semua Status' },
                    { value: 'menunggu', label: 'Menunggu' },
                    { value: 'dikirim', label: 'Dikirim' },
                    { value: 'selesai', label: 'Selesai' },
                    { value: 'dibatalkan', label: 'Dibatalkan' },
                ],
            },
            {
                key: 'kurir',
                label: 'Kurir',
                type: 'select' as const,
                options: [
                    { value: '', label: 'Semua Kurir' },
                    { value: 'JNE', label: 'JNE' },
                    { value: 'J&T', label: 'J&T' },
                    { value: 'TIKI', label: 'TIKI' },
                    { value: 'POS Indonesia', label: 'POS Indonesia' },
                    { value: 'SiCepat', label: 'SiCepat' },
                    { value: 'AnterAja', label: 'AnterAja' },
                    { value: 'Gojek', label: 'Gojek' },
                ],
            },
        ],
        [],
    );

    const actions = useMemo(
        () => [
            createEditAction<Pengiriman>((item) => `/pengiriman/${item.pengiriman_id}/edit`),
            createDeleteAction<Pengiriman>((item) => {
                router.delete(`/pengiriman/${item.pengiriman_id}`, {
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
        <TableTemplate<Pengiriman>
            title="Manajemen Pengiriman"
            breadcrumbs={breadcrumbs}
            data={pengiriman}
            columns={columns}
            createUrl="/pengiriman/create"
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/pengiriman"
            actions={actions}
            flash={flash}
            deleteDialogTitle="Hapus Pengiriman"
            deleteDialogMessage={(item) => `Apakah Anda yakin ingin menghapus pengiriman dengan ID "${item.pengiriman_id}"?`}
        />
    );
}
