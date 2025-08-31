import TableTemplate from '@/components/table/table-template';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Building2, Calendar, Edit, Eye, FileText, Trash2 } from 'lucide-react';
import { useMemo } from 'react';

interface Supplier {
    supplier_id: string;
    nama_supplier: string;
}

interface Pembelian extends Record<string, unknown> {
    pembelian_id: string;
    pengadaan_id?: string;
    nomor_po: string;
    supplier: Supplier;
    tanggal_pembelian: string;
    tanggal_jatuh_tempo?: string;
    total_biaya: number;
    status: string;
    status_label: string;
    metode_pembayaran?: string;
    can_edit: boolean;
    can_cancel: boolean;
    created_at: string;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    pembelian: PaginatedData<Pembelian>;
    suppliers: Supplier[];
    filters: {
        status?: string;
        supplier_id?: string;
        date_from?: string;
        date_to?: string;
        search?: string;
        sort_by: string;
        sort_direction: 'asc' | 'desc';
        per_page: number;
    };
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pembelian', href: '/pembelian' },
];

export default function Index({ pembelian, suppliers, filters, flash }: Props) {
    const getStatusColor = (status: string) => {
        const colors: Record<string, string> = {
            draft: 'bg-gray-100 text-gray-800',
            sent: 'bg-blue-100 text-blue-800',
            confirmed: 'bg-purple-100 text-purple-800',
            received: 'bg-green-100 text-green-800',
            invoiced: 'bg-yellow-100 text-yellow-800',
            paid: 'bg-emerald-100 text-emerald-800',
            cancelled: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const handleDelete = (item: Record<string, unknown>) => {
        const pembelianItem = item as unknown as Pembelian;
        router.delete(`/pembelian/${pembelianItem.pembelian_id}`, {
            preserveScroll: true,
        });
    };

    const columns = useMemo(
        () => [
            {
                key: 'nomor_po',
                label: 'Nomor PO',
                sortable: true,
                render: (item: Record<string, unknown>) => {
                    const pembelianItem = item as unknown as Pembelian;
                    return (
                        <div className="space-y-1">
                            <div className="font-medium text-gray-900">{pembelianItem.nomor_po}</div>
                            {pembelianItem.pengadaan_id && (
                                <div className="text-xs text-gray-500">
                                    <FileText className="mr-1 inline h-3 w-3" />
                                    {pembelianItem.pengadaan_id}
                                </div>
                            )}
                        </div>
                    );
                },
            },
            {
                key: 'supplier',
                label: 'Supplier',
                sortable: false,
                render: (item: Record<string, unknown>) => {
                    const pembelianItem = item as unknown as Pembelian;
                    return (
                        <div className="space-y-1">
                            <div className="flex items-center">
                                <Building2 className="mr-2 h-4 w-4 text-gray-400" />
                                <span className="font-medium">{pembelianItem.supplier.nama_supplier}</span>
                            </div>
                        </div>
                    );
                },
            },
            {
                key: 'tanggal_pembelian',
                label: 'Tanggal',
                sortable: true,
                render: (item: Record<string, unknown>) => {
                    const pembelianItem = item as unknown as Pembelian;
                    return (
                        <div className="space-y-1">
                            <div className="flex items-center text-sm">
                                <Calendar className="mr-2 h-4 w-4 text-gray-400" />
                                <span>{formatDate(pembelianItem.tanggal_pembelian)}</span>
                            </div>
                            {pembelianItem.tanggal_jatuh_tempo && (
                                <div className="text-xs text-gray-500">Jatuh Tempo: {formatDate(pembelianItem.tanggal_jatuh_tempo)}</div>
                            )}
                        </div>
                    );
                },
            },
            {
                key: 'total_biaya',
                label: 'Total Biaya',
                sortable: true,
                render: (item: Record<string, unknown>) => {
                    const pembelianItem = item as unknown as Pembelian;
                    return (
                        <div className="text-right">
                            <div className="font-semibold text-gray-900">{formatCurrency(pembelianItem.total_biaya)}</div>
                            {pembelianItem.metode_pembayaran && (
                                <div className="text-xs text-gray-500 capitalize">{pembelianItem.metode_pembayaran}</div>
                            )}
                        </div>
                    );
                },
            },
            {
                key: 'status',
                label: 'Status',
                sortable: true,
                render: (item: Record<string, unknown>) => {
                    const pembelianItem = item as unknown as Pembelian;
                    return <Badge className={cn('text-xs font-medium', getStatusColor(pembelianItem.status))}>{pembelianItem.status_label}</Badge>;
                },
            },
        ],
        [],
    );

    const actions = useMemo(
        () => [
            {
                label: 'View',
                icon: Eye,
                onClick: (item: Record<string, unknown>) => {
                    const pembelianItem = item as unknown as Pembelian;
                    router.visit(`/pembelian/${pembelianItem.pembelian_id}`);
                },
            },
            {
                label: 'Edit',
                icon: Edit,
                onClick: (item: Record<string, unknown>) => {
                    const pembelianItem = item as unknown as Pembelian;
                    router.visit(`/pembelian/${pembelianItem.pembelian_id}/edit`);
                },
                show: (item: Record<string, unknown>) => {
                    const pembelianItem = item as unknown as Pembelian;
                    return pembelianItem.can_edit;
                },
            },
            {
                label: 'Cancel',
                icon: Trash2,
                onClick: handleDelete,
                show: (item: Record<string, unknown>) => {
                    const pembelianItem = item as unknown as Pembelian;
                    return pembelianItem.can_cancel;
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
                    { value: 'sent', label: 'PO Dikirim' },
                    { value: 'confirmed', label: 'Dikonfirmasi' },
                    { value: 'received', label: 'Diterima' },
                    { value: 'invoiced', label: 'Ditagih' },
                    { value: 'paid', label: 'Dibayar' },
                    { value: 'cancelled', label: 'Dibatalkan' },
                ],
            },
            {
                key: 'supplier_id',
                label: 'Supplier',
                type: 'select' as const,
                options: [
                    { value: '', label: 'Semua Supplier' },
                    ...suppliers.map((supplier) => ({
                        value: supplier.supplier_id,
                        label: supplier.nama_supplier,
                    })),
                ],
            },
            {
                key: 'date_from',
                label: 'Tanggal Dari',
                type: 'text' as const,
                placeholder: 'YYYY-MM-DD',
            },
            {
                key: 'date_to',
                label: 'Tanggal Sampai',
                type: 'text' as const,
                placeholder: 'YYYY-MM-DD',
            },
        ],
        [suppliers],
    );

    return (
        <>
            <Head title="Purchase Order" />
            <TableTemplate<Pembelian>
                title="Purchase Order"
                breadcrumbs={breadcrumbs}
                data={pembelian}
                columns={columns}
                createUrl="/pembelian/create"
                createButtonText="Buat PO Baru"
                searchPlaceholder="Cari nomor PO, supplier..."
                filters={filters}
                filterOptions={filterOptions}
                baseUrl="/pembelian"
                actions={actions}
                flash={flash}
                onDelete={handleDelete}
                deleteDialogTitle="Batalkan Purchase Order"
                deleteDialogMessage={(item) => {
                    const pembelianItem = item as unknown as Pembelian;
                    return `Apakah Anda yakin ingin membatalkan Purchase Order ${pembelianItem.nomor_po}? Tindakan ini tidak dapat dibatalkan.`;
                }}
                getItemName={(item) => {
                    const pembelianItem = item as unknown as Pembelian;
                    return pembelianItem.nomor_po;
                }}
                idField="pembelian_id"
            />
        </>
    );
}
