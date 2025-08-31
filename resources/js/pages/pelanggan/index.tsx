import { createDeleteAction, createEditAction, createViewAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';

interface Pelanggan extends Record<string, unknown> {
    pelanggan_id: string;
    nama_pelanggan: string;
    email_pelanggan: string;
    nomor_telepon: string;
    alamat_pembayaran: string;
    alamat_pengiriman: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPelanggan {
    data: Pelanggan[];
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
    [key: string]: string | number | undefined;
}

interface Props {
    pelanggan: PaginatedPelanggan;
    filters: Filters;
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pelanggan',
        href: '/pelanggan',
    },
];

export default function Index({ pelanggan, filters, flash }: Props) {
    console.log('Pelanggan Index render', { pelanggan, filters, flash });

    const columns = useMemo(
        () => [
            {
                key: 'pelanggan_id',
                label: 'ID Pelanggan',
                sortable: true,
                hideable: true,
                defaultVisible: true,
            },
            {
                key: 'nama_pelanggan',
                label: 'Nama Pelanggan',
                sortable: true,
                hideable: true,
                defaultVisible: true,
            },
            {
                key: 'email_pelanggan',
                label: 'Email',
                sortable: true,
                hideable: true,
                defaultVisible: true,
            },
            {
                key: 'nomor_telepon',
                label: 'Nomor Telepon',
                sortable: true,
                hideable: true,
                defaultVisible: true,
            },
            {
                key: 'alamat_pembayaran',
                label: 'Alamat Pembayaran',
                sortable: false,
                hideable: true,
                defaultVisible: true,
                render: (item: Pelanggan) => (
                    <div className="max-w-xs truncate" title={item.alamat_pembayaran}>
                        {item.alamat_pembayaran}
                    </div>
                ),
            },
            {
                key: 'alamat_pengiriman',
                label: 'Alamat Pengiriman',
                sortable: false,
                hideable: true,
                defaultVisible: false, // Hidden by default
                render: (item: Pelanggan) => (
                    <div className="max-w-xs truncate" title={item.alamat_pengiriman}>
                        {item.alamat_pengiriman}
                    </div>
                ),
            },
        ],
        [],
    );

    // Actions using action templates - memoized to prevent infinite re-renders
    const actions = useMemo(
        () => [
            createViewAction<Pelanggan>((item) => `/pelanggan/${item.pelanggan_id}`),
            createEditAction<Pelanggan>((item) => `/pelanggan/${item.pelanggan_id}/edit`),
            createDeleteAction<Pelanggan>((item) => {
                router.delete(`/pelanggan/${item.pelanggan_id}`, {
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
        <TableTemplate<Pelanggan>
            title="Pelanggan Management"
            breadcrumbs={breadcrumbs}
            data={pelanggan}
            columns={columns}
            createUrl="/pelanggan/create"
            createButtonText="Add Pelanggan"
            searchPlaceholder="Search by name, email, or phone..."
            filters={filters}
            baseUrl="/pelanggan"
            actions={actions}
            flash={flash}
            deleteDialogTitle="Delete Pelanggan"
            deleteDialogMessage={(item) => `Are you sure you want to delete customer "${item.nama_pelanggan}"? This action cannot be undone.`}
            getItemName={(item) => item.nama_pelanggan}
        />
    );
}
