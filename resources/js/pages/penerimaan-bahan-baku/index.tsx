import { createViewAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { useMemo } from 'react';

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
    const getStatusBadge = (status: string) => {
        const statusMap: Record<string, { variant: 'default' | 'outline'; label: string }> = {
            confirmed: { variant: 'default', label: 'Dikonfirmasi' },
            draft: { variant: 'outline', label: 'Draft' },
        };
        const { variant, label } = statusMap[status] || { variant: 'outline', label: 'Unknown' };
        return <Badge variant={variant}>{label}</Badge>;
    };

    const columns = useMemo(
        () => [
            {
                key: 'nomor_penerimaan',
                label: 'Nomor Penerimaan',
                sortable: true,
            },
            {
                key: 'nomor_surat_jalan',
                label: 'No. Surat Jalan',
                sortable: false,
            },
            {
                key: 'pembelian_id',
                label: 'No. PO Terkait',
                sortable: false,
                render: (item: Penerimaan) => item.pembelian?.pembelian_id || '-',
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
                key: 'status',
                label: 'Status',
                sortable: false,
                render: (item: Penerimaan) => getStatusBadge(item.status),
            },
        ],
        [],
    );

    const actions = useMemo(() => [createViewAction<Penerimaan>((item) => `/penerimaan-bahan-baku/${item.penerimaan_id}`)], []);

    return (
        <TableTemplate<Penerimaan>
            title="Penerimaan Bahan Baku"
            breadcrumbs={breadcrumbs}
            data={penerimaan}
            columns={columns}
            createUrl="/penerimaan-bahan-baku/create"
            createButtonText="Tambah"
            searchPlaceholder="Cari no. penerimaan, SJ, atau PO..."
            filters={filters}
            baseUrl="/penerimaan-bahan-baku"
            actions={actions}
            flash={flash}
            idField="penerimaan_id"
        />
    );
}
