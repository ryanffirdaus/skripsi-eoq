// Index.tsx - Bahan Baku (Fixed version)
import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { formatCurrency } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';

interface BahanBaku extends Record<string, unknown> {
    bahan_baku_id: string;
    nama_bahan: string;
    lokasi_bahan: string;
    stok_bahan: number;
    satuan_bahan: string;
    harga_bahan: number;
    permintaan_harian_rata2_bahan: number;
    permintaan_harian_maksimum_bahan: number;
    waktu_tunggu_rata2_bahan: number;
    waktu_tunggu_maksimum_bahan: number;
    permintaan_tahunan: number;
    biaya_pemesanan_bahan: number;
    biaya_penyimpanan_bahan: number;
    safety_stock_bahan?: number;
    rop_bahan?: number;
    eoq_bahan?: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedBahanBaku {
    data: BahanBaku[];
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
    lokasi_bahan?: string;
    satuan_bahan?: string;
    [key: string]: string | number | undefined;
}

interface Props {
    bahanBaku: PaginatedBahanBaku;
    filters: Filters;
    uniqueLokasi: string[];
    uniqueSatuan: string[];
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
        title: 'Bahan Baku',
        href: '/bahan-baku',
    },
];

export default function Index({ bahanBaku, filters, uniqueLokasi, uniqueSatuan, permissions, flash }: Props) {
    const columns = [
        {
            key: 'bahan_baku_id',
            label: 'Kode Bahan',
            sortable: true,
            hideable: true,
            defaultVisible: true,
        },
        {
            key: 'nama_bahan',
            label: 'Nama Bahan',
            sortable: true,
            hideable: true,
            defaultVisible: true,
        },
        {
            key: 'lokasi_bahan',
            label: 'Lokasi',
            sortable: true,
            hideable: true,
            defaultVisible: true,
        },
        {
            key: 'stok_bahan',
            label: 'Stok',
            sortable: true,
            hideable: true,
            defaultVisible: true,
            render: (item: BahanBaku) => `${item.stok_bahan} ${item.satuan_bahan}`,
        },
        {
            key: 'harga_bahan',
            label: 'Harga',
            sortable: true,
            hideable: true,
            defaultVisible: true,
            render: (item: BahanBaku) => formatCurrency(item.harga_bahan),
        },
        {
            key: 'satuan_bahan',
            label: 'Satuan',
            sortable: true,
            hideable: true,
            defaultVisible: false, // Hidden by default
        },
        {
            key: 'safety_stock_bahan',
            label: 'Safety Stock',
            sortable: true,
            hideable: true,
            defaultVisible: false, // Hidden by default
            render: (item: BahanBaku) => (item.safety_stock_bahan ? `${item.safety_stock_bahan.toFixed(2)} ${item.satuan_bahan}` : '-'),
        },
        {
            key: 'rop_bahan',
            label: 'ROP',
            sortable: true,
            hideable: true,
            defaultVisible: false, // Hidden by default
            render: (item: BahanBaku) => (item.rop_bahan ? `${item.rop_bahan.toFixed(2)} ${item.satuan_bahan}` : '-'),
        },
        {
            key: 'eoq_bahan',
            label: 'EOQ',
            sortable: true,
            hideable: true,
            defaultVisible: false, // Hidden by default
            render: (item: BahanBaku) => (item.eoq_bahan ? `${item.eoq_bahan.toFixed(2)} ${item.satuan_bahan}` : '-'),
        },
    ];

    const filterOptions = [
        {
            key: 'lokasi_bahan',
            label: 'Lokasi',
            type: 'select' as const,
            placeholder: 'All Locations',
            options: uniqueLokasi.map((lokasi) => ({
                value: lokasi,
                label: lokasi,
            })),
        },
        {
            key: 'satuan_bahan',
            label: 'Satuan',
            type: 'select' as const,
            placeholder: 'All Units',
            options: uniqueSatuan.map((satuan) => ({
                value: satuan,
                label: satuan,
            })),
        },
    ];

    // Actions - hanya tampil jika ada permission
    const actions =
        permissions.canEdit || permissions.canDelete
            ? [
                  ...(permissions.canEdit ? [createEditAction<BahanBaku>((item) => `/bahan-baku/${item.bahan_baku_id}/edit`)] : []),
                  ...(permissions.canDelete
                      ? [
                            createDeleteAction<BahanBaku>((item) => {
                                router.delete(`/bahan-baku/${item.bahan_baku_id}`, {
                                    preserveState: false,
                                    onError: (errors) => {
                                        console.error('Delete failed:', errors);
                                    },
                                });
                            }),
                        ]
                      : []),
              ]
            : [];

    return (
        <TableTemplate<BahanBaku>
            title="Manajemen Bahan Baku"
            breadcrumbs={breadcrumbs}
            data={bahanBaku}
            columns={columns}
            createUrl={permissions.canCreate ? '/bahan-baku/create' : undefined}
            searchPlaceholder="Search by material name or location..."
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/bahan-baku"
            actions={actions}
            flash={flash}
            deleteDialogTitle="Delete Bahan Baku"
        />
    );
}
