import { createViewAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { formatDate } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { useMemo } from 'react';
import { CheckBadgeIcon } from '@heroicons/react/24/outline';

// --- TYPE DEFINITIONS ---
// These types should ideally be in a global types file (e.g., resources/js/types/index.d.ts)
export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export interface FlashMessage {
    message?: string;
    type?: 'success' | 'error' | 'warning' | 'info';
}

interface Penerimaan extends Record<string, unknown> {
    penerimaan_id: string;
    nomor_penerimaan: string;
    nomor_surat_jalan: string;
    tanggal_penerimaan: string;
    status: string;
    pembelian: {
        pembelian_id: string;
    };
    pemasok: {
        nama_pemasok: string;
    };
}

interface Filters {
    search?: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    per_page: number;
    [key: string]: string | number | undefined;
}

interface Props {
    penerimaan: Paginated<Penerimaan>;
    filters: Filters;
    flash?: FlashMessage;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Penerimaan Bahan Baku', href: '/penerimaan-bahan-baku' },
];

export default function Index({ penerimaan, filters, flash }: Props) {

    const columns = useMemo(
        () => [
            {
                key: 'penerimaan_id',
                label: 'ID',
                sortable: true,
            },
            {
                key: 'pemasok',
                label: 'Pemasok',
                sortable: false,
                render: (item: Penerimaan) => item.pemasok?.nama_pemasok || '-',
            },
            {
                key: 'tanggal_penerimaan',
                label: 'Tanggal Diterima',
                sortable: true,
                render: (item: Penerimaan) => formatDate(item.tanggal_penerimaan),
            },
            {
                key: 'pembelian_id',
                label: 'ID Pembelian',
                sortable: false,
                render: (item: Penerimaan) => item.pembelian?.pembelian_id || '-',
            },
        ],
        [],
    );

    const actions = useMemo(() => [createViewAction<Penerimaan>((item) => `/penerimaan-bahan-baku/${item.penerimaan_id}`)], []);

    const filterOptions = useMemo(() => [], []);

    return (
        <TableTemplate<Penerimaan>
            title="Penerimaan Bahan Baku"
            breadcrumbs={breadcrumbs}
            data={penerimaan}
            columns={columns}
            createUrl="/penerimaan-bahan-baku/create"
            createButtonText="Tambah"
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/penerimaan-bahan-baku"
            actions={actions}
            flash={flash}
            idField="penerimaan_id"
        />
    );
}
