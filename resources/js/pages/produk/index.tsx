// Index.tsx - Produk
import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { formatCurrency } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';

interface Produk extends Record<string, unknown> {
    produk_id: string;
    nama_produk: string;
    lokasi_produk: string;
    stok_produk: number;
    satuan_produk: string;
    hpp_produk: number;
    harga_jual: number;
    permintaan_harian_rata2_produk: number;
    permintaan_harian_maksimum_produk: number;
    waktu_tunggu_rata2_produk: number;
    waktu_tunggu_maksimum_produk: number;
    permintaan_tahunan: number;
    biaya_pemesanan_produk: number;
    biaya_penyimpanan_produk: number;
    safety_stock_produk?: number;
    rop_produk?: number;
    eoq_produk?: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedProduk {
    data: Produk[];
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
    lokasi_produk?: string;
    satuan_produk?: string;
    [key: string]: string | number | undefined;
}

interface Props {
    produk: PaginatedProduk;
    filters: Filters;
    uniqueLokasi: string[];
    uniqueSatuan: string[];
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Produk',
        href: '/produk',
    },
];

export default function Index({ produk, filters, uniqueLokasi, uniqueSatuan, flash }: Props) {
    const columns = [
        {
            key: 'produk_id',
            label: 'Kode Produk',
            sortable: true,
            hideable: true,
            defaultVisible: true,
        },
        {
            key: 'nama_produk',
            label: 'Nama Produk',
            sortable: true,
            hideable: true,
            defaultVisible: true,
        },
        {
            key: 'lokasi_produk',
            label: 'Lokasi',
            sortable: true,
            hideable: true,
            defaultVisible: true,
        },
        {
            key: 'stok_produk',
            label: 'Stok',
            sortable: true,
            hideable: true,
            defaultVisible: true,
            render: (item: Produk) => `${item.stok_produk} ${item.satuan_produk}`,
        },
        {
            key: 'hpp_produk',
            label: 'HPP',
            sortable: true,
            hideable: true,
            defaultVisible: true,
            render: (item: Produk) => formatCurrency(item.hpp_produk),
        },
        {
            key: 'satuan_produk',
            label: 'Satuan',
            sortable: true,
            hideable: true,
            defaultVisible: false, // Hidden by default
        },
        {
            key: 'harga_jual',
            label: 'Harga Jual',
            sortable: true,
            hideable: true,
            defaultVisible: false, // Hidden by default
            render: (item: Produk) => formatCurrency(item.harga_jual),
        },
        {
            key: 'safety_stock_produk',
            label: 'Safety Stock',
            sortable: true,
            hideable: true,
            defaultVisible: false, // Hidden by default
            render: (item: Produk) => (item.safety_stock_produk ? `${item.safety_stock_produk.toFixed(2)} ${item.satuan_produk}` : '-'),
        },
        {
            key: 'rop_produk',
            label: 'ROP',
            sortable: true,
            hideable: true,
            defaultVisible: false, // Hidden by default
            render: (item: Produk) => (item.rop_produk ? `${item.rop_produk.toFixed(2)} ${item.satuan_produk}` : '-'),
        },
        {
            key: 'eoq_produk',
            label: 'EOQ',
            sortable: true,
            hideable: true,
            defaultVisible: false, // Hidden by default
            render: (item: Produk) => (item.eoq_produk ? `${item.eoq_produk.toFixed(2)} ${item.satuan_produk}` : '-'),
        },
    ];

    const filterOptions = [
        {
            key: 'lokasi_produk',
            label: 'Lokasi',
            type: 'select' as const,
            placeholder: 'All Locations',
            options: uniqueLokasi.map((lokasi) => ({
                value: lokasi,
                label: lokasi,
            })),
        },
        {
            key: 'satuan_produk',
            label: 'Satuan',
            type: 'select' as const,
            placeholder: 'All Units',
            options: uniqueSatuan.map((satuan) => ({
                value: satuan,
                label: satuan,
            })),
        },
    ];

    // Actions using action templates
    const actions = [
        // createViewAction<Produk>((item) => `/produk/${item.produk_id}`),
        createEditAction<Produk>((item) => `/produk/${item.produk_id}/edit`),
        createDeleteAction<Produk>((item) => {
            router.delete(`/produk/${item.produk_id}`, {
                preserveState: false,
                onError: (errors) => {
                    console.error('Delete failed:', errors);
                },
            });
        }),
    ];

    return (
        <TableTemplate<Produk>
            title="Produk Management"
            breadcrumbs={breadcrumbs}
            data={produk}
            columns={columns}
            createUrl="/produk/create"
            createButtonText="Add Produk"
            searchPlaceholder="Search by product name or location..."
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/produk"
            actions={actions}
            flash={flash}
            deleteDialogTitle="Delete Produk"
            deleteDialogMessage={(item) => `Are you sure you want to delete product "${item.nama_produk}"? This action cannot be undone.`}
            getItemName={(item) => item.nama_produk}
        />
    );
}
