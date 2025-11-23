import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { formatDate } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';
import { BanknotesIcon, CreditCardIcon, DocumentTextIcon, CheckCircleIcon } from '@heroicons/react/24/outline';

// 1. Interface untuk data TransaksiPembayaran
interface TransaksiPembayaran extends Record<string, unknown> {
    transaksi_pembayaran_id: string;
    pembelian_id: string;
    pemasok_nama: string;
    tanggal_pembayaran: string;
    total_pembayaran: number;
    bukti_pembayaran: string;
    catatan?: string;
    created_at: string;
}

interface Pembelian {
    pembelian_id: string;
    pemasok_nama: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedTransaksiPembayaran {
    data: TransaksiPembayaran[];
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
    tanggal_dari?: string;
    tanggal_sampai?: string;
    pembelian_id?: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    per_page: number;
    [key: string]: string | number | undefined;
}

interface Props {
    transaksiPembayaran: PaginatedTransaksiPembayaran;
    filters: Filters;
    pembelians: Pembelian[];
    permissions?: {
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
        title: 'Transaksi Pembayaran',
        href: '/transaksi-pembayaran',
    },
];

export default function Index({ transaksiPembayaran, filters, pembelians, permissions, flash }: Props) {
    const getPaymentTypeBadge = (jenisPembayaran: string) => {
        const paymentConfig = {
            tunai: {
                label: 'Tunai',
                icon: BanknotesIcon,
                bgColor: 'bg-green-100',
                textColor: 'text-green-700',
                borderColor: 'border-green-300',
            },
            transfer: {
                label: 'Transfer',
                icon: CreditCardIcon,
                bgColor: 'bg-blue-100',
                textColor: 'text-blue-700',
                borderColor: 'border-blue-300',
            },
            dp: {
                label: 'Termin (DP)',
                icon: DocumentTextIcon,
                bgColor: 'bg-yellow-100',
                textColor: 'text-yellow-700',
                borderColor: 'border-yellow-300',
            },
            pelunasan: {
                label: 'Termin (Pelunasan)',
                icon: CheckCircleIcon,
                bgColor: 'bg-purple-100',
                textColor: 'text-purple-700',
                borderColor: 'border-purple-300',
            },
        };

        const config = paymentConfig[jenisPembayaran as keyof typeof paymentConfig];
        if (!config) return <span className="text-gray-500 text-sm">{jenisPembayaran}</span>;

        const IconComponent = config.icon;

        return (
            <div
                className={`flex items-center gap-1.5 rounded-full border px-3 py-1.5 ${config.bgColor} ${config.textColor} ${config.borderColor} text-sm font-medium whitespace-nowrap shadow-sm hover:scale-105 transition-transform duration-200`}
                title={config.label}
            >
                <IconComponent className="h-4 w-4 flex-shrink-0" />
                <span>{config.label}</span>
            </div>
        );
    };

    // 2. Definisi kolom untuk tabel TransaksiPembayaran
    const columns = useMemo(
        () => [
            {
                key: 'transaksi_pembayaran_id',
                label: 'ID',
                sortable: true,
                defaultVisible: true,
            },
            {
                key: 'pemasok_nama',
                label: 'Pemasok',
                sortable: false,
                defaultVisible: true,
            },
            {
                key: 'tanggal_pembayaran',
                label: 'Tgl. Pembayaran',
                sortable: true,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => formatDate((item as TransaksiPembayaran).tanggal_pembayaran),
            },
            {
                key: 'jenis_pembayaran',
                label: 'Jenis Pembayaran',
                sortable: false,
                defaultVisible: true,
                render: (item: Record<string, unknown>) => getPaymentTypeBadge((item as TransaksiPembayaran).jenis_pembayaran as string),
            },
        ],
        [],
    );

    // 3. Opsi filter untuk pembelian
    const filterOptions = useMemo(
        () => [
            {
                key: 'pembelian_id',
                label: 'Purchase Order',
                type: 'select' as const,
                options: [
                    { value: '', label: 'Semua PO' },
                    ...pembelians.map((p) => ({
                        value: p.pembelian_id,
                        label: `${p.pembelian_id} - ${p.pemasok_nama}`,
                    })),
                ],
            },
        ],
        [pembelians],
    );

    // 4. Aksi untuk setiap baris (edit, delete)
    const actions = useMemo(
        () => [
            ...(permissions?.canEdit
                ? [createEditAction<TransaksiPembayaran>((item) => `/transaksi-pembayaran/${item.transaksi_pembayaran_id}/edit`)]
                : []),
            ...(permissions?.canDelete
                ? [
                      createDeleteAction<TransaksiPembayaran>((item) => {
                          router.delete(`/transaksi-pembayaran/${item.transaksi_pembayaran_id}`);
                      }),
                  ]
                : []),
        ],
        [permissions?.canEdit, permissions?.canDelete],
    );

    return (
        <TableTemplate<TransaksiPembayaran>
            title="Manajemen Pembayaran"
            breadcrumbs={breadcrumbs}
            data={transaksiPembayaran}
            columns={columns}
            createUrl={permissions?.canCreate ? '/transaksi-pembayaran/create' : undefined}
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/transaksi-pembayaran"
            actions={actions}
            flash={flash}
            idField="transaksi_pembayaran_id"
            deleteDialogTitle="Hapus Transaksi Pembayaran"
            deleteDialogMessage={(item) => `Apakah Anda yakin ingin menghapus transaksi pembayaran dengan ID ${item.transaksi_pembayaran_id}?`}
        />
    );
}
