import { createDeleteAction, createEditAction, createViewAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatCurrency } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { ShoppingCart, TrendingDown } from 'lucide-react';
import { useCallback, useMemo } from 'react';

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
        const statusColors = {
            draft: 'outline',
            pending: 'secondary',
            procurement_approved: 'default',
            finance_approved: 'default',
            ordered: 'default',
            partial_received: 'default',
            received: 'default',
            cancelled: 'destructive',
        } as const;

        return (
            <Badge variant={statusColors[status as keyof typeof statusColors] || 'outline'}>
                {status === 'draft' && 'Draft'}
                {status === 'pending' && 'Menunggu'}
                {status === 'procurement_approved' && 'Disetujui (Pengadaan)'}
                {status === 'finance_approved' && 'Disetujui (Keuangan)'}
                {status === 'ordered' && 'Dipesan'}
                {status === 'partial_received' && 'Sebagian'}
                {status === 'received' && 'Diterima'}
                {status === 'cancelled' && 'Dibatalkan'}
            </Badge>
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

    const handleStatusUpdate = useCallback(async (pengadaanId: string, newStatus: string, additionalData: Record<string, unknown> = {}) => {
        try {
            const response = await fetch(`/pengadaan/${pengadaanId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    status: newStatus,
                    ...additionalData,
                }),
            });

            if (response.ok) {
                router.reload({ only: ['pengadaan'] });
                alert('Status berhasil diperbarui!');
            } else {
                const errorData = await response.json();
                console.error('Failed to update status:', errorData);
                alert('Gagal memperbarui status: ' + (errorData.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error updating status:', error);
            alert('Terjadi kesalahan saat memperbarui status');
        }
    }, []);

    const renderStatusActions = useCallback(
        (item: Pengadaan) => {
            const canUpdate = ['draft', 'pending', 'procurement_approved', 'finance_approved', 'ordered'].includes(item.status);

            if (!canUpdate) return null;

            return (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline" size="sm">
                            Update Status
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent>
                        <DropdownMenuLabel>Ubah Status</DropdownMenuLabel>
                        <DropdownMenuSeparator />

                        {item.status === 'draft' && (
                            <DropdownMenuItem onClick={() => handleStatusUpdate(item.pengadaan_id, 'pending')}>
                                Kirim untuk Persetujuan
                            </DropdownMenuItem>
                        )}

                        {item.status === 'pending' && (
                            <>
                                <DropdownMenuItem onClick={() => handleStatusUpdate(item.pengadaan_id, 'procurement_approved')}>
                                    Setujui Pengadaan
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => handleStatusUpdate(item.pengadaan_id, 'draft')}>
                                    Kembalikan ke Draft
                                </DropdownMenuItem>
                            </>
                        )}

                        {item.status === 'procurement_approved' && (
                            <DropdownMenuItem
                                onClick={() => {
                                    const nomorPO = prompt('Masukkan Nomor PO:');
                                    if (nomorPO) {
                                        handleStatusUpdate(item.pengadaan_id, 'ordered', { nomor_po: nomorPO });
                                    }
                                }}
                            >
                                Tandai Telah Dipesan
                            </DropdownMenuItem>
                        )}

                        {item.status === 'ordered' && (
                            <>
                                <DropdownMenuItem
                                    onClick={() => {
                                        const tanggalDelivery = prompt('Masukkan Tanggal Delivery (YYYY-MM-DD):');
                                        if (tanggalDelivery) {
                                            handleStatusUpdate(item.pengadaan_id, 'partial_received', { tanggal_delivery: tanggalDelivery });
                                        }
                                    }}
                                >
                                    Terima Sebagian
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => {
                                        const tanggalDelivery = prompt('Masukkan Tanggal Delivery (YYYY-MM-DD):');
                                        if (tanggalDelivery) {
                                            handleStatusUpdate(item.pengadaan_id, 'received', { tanggal_delivery: tanggalDelivery });
                                        }
                                    }}
                                >
                                    Terima Lengkap
                                </DropdownMenuItem>
                            </>
                        )}

                        {['draft', 'pending', 'procurement_approved', 'finance_approved'].includes(item.status) && (
                            <>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem onClick={() => handleStatusUpdate(item.pengadaan_id, 'cancelled')} className="text-red-600">
                                    Batalkan Pengadaan
                                </DropdownMenuItem>
                            </>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
        [handleStatusUpdate],
    );

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
                    return (
                        <div className="flex items-center gap-2">
                            {getStatusBadge(pengadaan.status)}
                            {renderStatusActions(pengadaan)}
                        </div>
                    );
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
        [renderStatusActions],
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
                    { value: 'pending', label: 'Menunggu Persetujuan' },
                    { value: 'procurement_approved', label: 'Disetujui (Pengadaan)' },
                    { value: 'finance_approved', label: 'Disetujui (Keuangan)' },
                    { value: 'ordered', label: 'Dipesan' },
                    { value: 'partial_received', label: 'Diterima Sebagian' },
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
            createViewAction<Pengadaan>((item) => `/pengadaan/${item.pengadaan_id}`),
            createEditAction<Pengadaan>((item) => `/pengadaan/${item.pengadaan_id}/edit`),
            createDeleteAction<Pengadaan>((item) => {
                router.delete(`/pengadaan/${item.pengadaan_id}`, {
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
