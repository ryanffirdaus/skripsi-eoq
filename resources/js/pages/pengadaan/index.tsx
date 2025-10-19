import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { Badge } from '@/components/ui/badge';
import { formatCurrency } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { Ban, CheckCircle, DollarSign, FileText, Package, ShoppingCart, TrendingDown, Users } from 'lucide-react';
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
            draft: {
                variant: 'outline' as const,
                label: 'Draft',
                icon: FileText,
                description: 'Tahap 1: Awal Pembuatan',
                bgColor: 'bg-slate-100',
                textColor: 'text-slate-700',
                borderColor: 'border-slate-300',
            },
            pending_approval_gudang: {
                variant: 'secondary' as const,
                label: 'Menunggu Approval Gudang',
                icon: Users,
                description: 'Tahap 2: Review Gudang',
                bgColor: 'bg-blue-100',
                textColor: 'text-blue-700',
                borderColor: 'border-blue-300',
            },
            pending_supplier_allocation: {
                variant: 'default' as const,
                label: 'Menunggu Alokasi Pemasok',
                icon: ShoppingCart,
                description: 'Tahap 3: Penunjukan Supplier',
                bgColor: 'bg-amber-100',
                textColor: 'text-amber-700',
                borderColor: 'border-amber-300',
            },
            pending_approval_pengadaan: {
                variant: 'default' as const,
                label: 'Menunggu Approval Pengadaan',
                icon: DollarSign,
                description: 'Tahap 4: Approval Final Pengadaan',
                bgColor: 'bg-purple-100',
                textColor: 'text-purple-700',
                borderColor: 'border-purple-300',
            },
            pending_approval_keuangan: {
                variant: 'default' as const,
                label: 'Menunggu Approval Keuangan',
                icon: DollarSign,
                description: 'Tahap 5: Review Budget',
                bgColor: 'bg-indigo-100',
                textColor: 'text-indigo-700',
                borderColor: 'border-indigo-300',
            },
            processed: {
                variant: 'default' as const,
                label: 'Sudah Diproses',
                icon: CheckCircle,
                description: 'Tahap 6: Siap PO',
                bgColor: 'bg-emerald-100',
                textColor: 'text-emerald-700',
                borderColor: 'border-emerald-300',
            },
            received: {
                variant: 'default' as const,
                label: 'Diterima',
                icon: Package,
                description: 'Tahap 7: Selesai',
                bgColor: 'bg-green-100',
                textColor: 'text-green-700',
                borderColor: 'border-green-300',
            },
            cancelled: {
                variant: 'destructive' as const,
                label: 'Dibatalkan',
                icon: Ban,
                description: 'Dibatalkan',
                bgColor: 'bg-red-100',
                textColor: 'text-red-700',
                borderColor: 'border-red-300',
            },
        };

        const config = statusConfig[status as keyof typeof statusConfig] || {
            variant: 'outline' as const,
            label: status,
            icon: FileText,
            description: status,
            bgColor: 'bg-slate-100',
            textColor: 'text-slate-700',
            borderColor: 'border-slate-300',
        };

        const IconComponent = config.icon;

        return (
            <div className="flex items-center gap-2">
                <div
                    className={`flex items-center gap-1.5 rounded-full border px-3 py-1.5 ${config.bgColor} ${config.textColor} ${config.borderColor} text-sm font-medium whitespace-nowrap`}
                >
                    <IconComponent className="h-4 w-4 flex-shrink-0" />
                    <span>{config.label}</span>
                </div>
                <span className="hidden text-xs text-gray-500 md:inline">{config.description}</span>
            </div>
        );
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
                    { value: 'pending_approval_gudang', label: 'Menunggu Approval Gudang' },
                    { value: 'pending_supplier_allocation', label: 'Menunggu Alokasi Pemasok' },
                    { value: 'pending_approval_pengadaan', label: 'Menunggu Approval Pengadaan' },
                    { value: 'pending_approval_keuangan', label: 'Menunggu Approval Keuangan' },
                    { value: 'processed', label: 'Sudah Diproses' },
                    { value: 'received', label: 'Diterima' },
                    { value: 'cancelled', label: 'Dibatalkan' },
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
