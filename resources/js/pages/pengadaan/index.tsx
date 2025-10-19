import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { Badge } from '@/components/ui/badge';
import { formatCurrency } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { ShoppingCart, TrendingDown } from 'lucide-react';
import { useMemo } from 'react';

interface Pengadaan extends Record<string, unknown> {
    pengadaan_id: string;
    jenis_pengadaan: string;
    pesanan_id?: string;
    tanggal_pengadaan: string;
    tanggal_delivery?: string;
    total_biaya: number;
    status: string;
    status_label: string;
    nomor_po?: string;
    can_edit: boolean;
    can_cancel: boolean;
    created_at: string;
    updated_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPengadaan {
    data: Pengadaan[];
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
    jenis_pengadaan?: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    per_page: number;
    [key: string]: string | number | undefined;
}

interface Props {
    pengadaan: PaginatedPengadaan;
    filters: Filters;
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengadaan',
        href: '/pengadaan',
    },
];

export default function Index({ pengadaan, filters, flash }: Props) {
    const getStatusBadge = (status: string) => {
        const statusConfig = {
            draft: { variant: 'outline' as const, label: 'Draft' },
            disetujui_gudang: { variant: 'secondary' as const, label: 'Disetujui Gudang' },
            disetujui_pengadaan: { variant: 'default' as const, label: 'Disetujui Pengadaan' },
            disetujui_keuangan: { variant: 'default' as const, label: 'Disetujui Keuangan' },
            diproses: { variant: 'default' as const, label: 'Diproses' },
            diterima: { variant: 'default' as const, label: 'Diterima' },
            dibatalkan: { variant: 'destructive' as const, label: 'Dibatalkan' },
        };

        const config = statusConfig[status as keyof typeof statusConfig] || { variant: 'outline' as const, label: status };

        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    const getJenisBadge = (jenis: string) => {
        const jenisColors = {
            pesanan: 'default',
            rop: 'secondary',
        } as const;

        const jenisIcons = {
            pesanan: <ShoppingCart className="h-3 w-3" />,
            rop: <TrendingDown className="h-3 w-3" />,
        };

        return (
            <Badge variant={jenisColors[jenis as keyof typeof jenisColors] || 'outline'} className="flex items-center gap-1">
                {jenisIcons[jenis as keyof typeof jenisIcons]}
                {jenis === 'pesanan' && 'Pesanan'}
                {jenis === 'rop' && 'ROP'}
            </Badge>
        );
    };

    // Status update sekarang dilakukan di halaman edit

    const columns = useMemo(
        () => [
            {
                key: 'pengadaan_id',
                label: 'ID Pengadaan',
                sortable: true,
                hideable: true,
                defaultVisible: true,
            },
            {
                key: 'jenis_pengadaan',
                label: 'Jenis',
                sortable: false,
                hideable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => {
                    const pengadaan = item as Pengadaan;
                    return getJenisBadge(pengadaan.jenis_pengadaan);
                },
            },
            {
                key: 'total_biaya',
                label: 'Total Biaya',
                sortable: true,
                hideable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => {
                    const pengadaan = item as Pengadaan;
                    return formatCurrency(pengadaan.total_biaya);
                },
            },
            {
                key: 'status',
                label: 'Status',
                sortable: false,
                hideable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => {
                    const pengadaan = item as Pengadaan;
                    return getStatusBadge(pengadaan.status);
                },
            },
            {
                key: 'nomor_po',
                label: 'No. PO',
                sortable: false,
                hideable: true,
                defaultVisible: false,
                render: (item: Record<string, unknown>) => {
                    const pengadaan = item as Pengadaan;
                    return pengadaan.nomor_po || '-';
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
                    { value: 'draft', label: 'Draft' },
                    { value: 'disetujui_gudang', label: 'Disetujui Gudang' },
                    { value: 'disetujui_pengadaan', label: 'Disetujui Pengadaan' },
                    { value: 'disetujui_keuangan', label: 'Disetujui Keuangan' },
                    { value: 'diproses', label: 'Diproses' },
                    { value: 'diterima', label: 'Diterima' },
                    { value: 'dibatalkan', label: 'Dibatalkan' },
                ],
            },
            {
                key: 'jenis_pengadaan',
                label: 'Jenis Pengadaan',
                type: 'select' as const,
                options: [
                    { value: '', label: 'Semua Jenis' },
                    { value: 'pesanan', label: 'Berdasarkan Pesanan' },
                    { value: 'rop', label: 'Berdasarkan ROP' },
                ],
            },
        ],
        [],
    );

    const actions = useMemo(
        () => [
            // createViewAction<Pengadaan>((item) => `/pengadaan/${item.pengadaan_id}`),
            createEditAction<Pengadaan>(
                (item) => `/pengadaan/${item.pengadaan_id}/edit`,
                (item) => item.can_edit,
            ),
            createDeleteAction<Pengadaan>((item) => {
                router.delete(`/pengadaan/${item.pengadaan_id}`, {
                    preserveState: false,
                    onError: (errors: unknown) => {
                        console.error('Delete failed:', errors);
                    },
                });
            }),
        ],
        [],
    );

    return (
        <TableTemplate<Pengadaan>
            title="Pengadaan Management"
            breadcrumbs={breadcrumbs}
            data={pengadaan}
            columns={columns}
            createUrl="/pengadaan/create"
            createButtonText="Buat Pengadaan"
            searchPlaceholder="Cari pengadaan..."
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/pengadaan"
            actions={actions}
            flash={flash}
            idField="pengadaan_id"
        />
    );
}
