import { createDeleteAction, createEditAction, createViewAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { formatCurrency, formatDate } from '@/lib/formatters';
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
    pending: 'bg-yellow-100 text-yellow-800',
    dikirim: 'bg-blue-100 text-blue-800',
    selesai: 'bg-green-100 text-green-800',
    dibatalkan: 'bg-red-100 text-red-800',
};

const statusLabels = {
    pending: 'Pending',
    dikirim: 'Dikirim',
    selesai: 'Diterima',
    dibatalkan: 'Dibatalkan',
};

export default function Index({ pengiriman, filters, flash }: Props) {
    const getStatusColor = (status: string) => {
        return statusColors[status as keyof typeof statusColors] || statusColors.pending;
    };

    const getStatusLabel = (status: string) => {
        return statusLabels[status as keyof typeof statusLabels] || status;
    };

    const columns = useMemo(
        () => [
            {
                key: 'pengiriman_id',
                label: 'ID Pengiriman',
                sortable: true,
                hideable: true,
                defaultVisible: true,
            },
            {
                key: 'nomor_resi',
                label: 'Nomor Resi',
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
                key: 'pelanggan',
                label: 'Pelanggan',
                sortable: false,
                hideable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => {
                    const pengiriman = item as Pengiriman;
                    return (
                        <div>
                            <div className="font-medium">{pengiriman.pesanan?.pelanggan?.nama || 'N/A'}</div>
                            <div className="text-sm text-gray-500">
                                {pengiriman.pesanan ? `Pesanan: ${pengiriman.pesanan.pesanan_id}` : 'Data tidak tersedia'}
                            </div>
                        </div>
                    );
                },
            },
            {
                key: 'biaya_pengiriman',
                label: 'Biaya',
                sortable: true,
                hideable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => {
                    const pengiriman = item as Pengiriman;
                    return formatCurrency(pengiriman.biaya_pengiriman);
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
            {
                key: 'tanggal_kirim',
                label: 'Tanggal Kirim',
                sortable: true,
                hideable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => {
                    const pengiriman = item as Pengiriman;
                    return pengiriman.tanggal_kirim
                        ? formatDate(pengiriman.tanggal_kirim, {
                              year: 'numeric',
                              month: 'short',
                              day: 'numeric',
                          })
                        : '-';
                },
            },
            {
                key: 'estimasi_hari',
                label: 'Estimasi',
                sortable: true,
                hideable: true,
                defaultVisible: false,
                render: (item: Record<string, unknown>) => {
                    const pengiriman = item as Pengiriman;
                    return `${pengiriman.estimasi_hari} hari`;
                },
            },
            {
                key: 'tanggal_diterima',
                label: 'Tanggal Diterima',
                sortable: true,
                hideable: true,
                defaultVisible: false,
                render: (item: Record<string, unknown>) => {
                    const pengiriman = item as Pengiriman;
                    return pengiriman.tanggal_diterima
                        ? formatDate(pengiriman.tanggal_diterima, {
                              year: 'numeric',
                              month: 'short',
                              day: 'numeric',
                          })
                        : '-';
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
                    { value: 'pending', label: 'Pending' },
                    { value: 'dikirim', label: 'Dikirim' },
                    { value: 'selesai', label: 'Diterima' },
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
            createViewAction<Pengiriman>((item) => `/pengiriman/${item.pengiriman_id}`),
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
            title="Pengiriman Management"
            breadcrumbs={breadcrumbs}
            data={pengiriman}
            columns={columns}
            createUrl="/pengiriman/create"
            createButtonText="Tambah Pengiriman"
            searchPlaceholder="Cari pengiriman..."
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/pengiriman"
            actions={actions}
            flash={flash}
            idField="pengiriman_id"
        />
    );
}
