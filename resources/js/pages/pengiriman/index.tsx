import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';

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

const statusColors = {
    menunggu: 'bg-yellow-100 text-yellow-800',
    dalam_perjalanan: 'bg-blue-100 text-blue-800',
    diterima: 'bg-green-100 text-green-800',
    dikirim: 'bg-indigo-100 text-indigo-800',
    selesai: 'bg-teal-100 text-teal-800',
    dibatalkan: 'bg-red-100 text-red-800',
};

const statusLabels = {
    menunggu: 'Menunggu',
    dalam_perjalanan: 'Dalam Perjalanan',
    diterima: 'Diterima',
    dikirim: 'Dikirim',
    selesai: 'Selesai',
    dibatalkan: 'Dibatalkan',
};

export default function Index({ pengiriman, filters, flash }: Props) {
    const getStatusColor = (status: string) => {
        return statusColors[status as keyof typeof statusColors] || statusColors.menunggu;
    };

    const getStatusLabel = (status: string) => {
        return statusLabels[status as keyof typeof statusLabels] || status;
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
                    return (
                        <span
                            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium ${getStatusColor(pengiriman.status)}`}
                        >
                            {getStatusLabel(pengiriman.status)}
                        </span>
                    );
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
                    { value: 'dalam_perjalanan', label: 'Dalam Perjalanan' },
                    { value: 'diterima', label: 'Diterima' },
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
            createEditAction<Pengiriman>(
                (item) => `/pengiriman/${item.pengiriman_id}/edit`,
                (item) => item.status !== 'selesai' && item.status !== 'dibatalkan',
            ),
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
