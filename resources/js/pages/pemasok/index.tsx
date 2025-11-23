import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { useMemo } from 'react';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/outline';

interface Pemasok extends Record<string, unknown> {
    pemasok_id: string;
    nama_pemasok: string;
    narahubung: string | null;
    email: string | null;
    nomor_telepon: string | null;
    alamat: string | null;
    status: 'active' | 'inactive';
    catatan: string | null;
    created_at: string;
    updated_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPemasok {
    data: Pemasok[];
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
    status?: string;
    [key: string]: string | number | undefined;
}

interface Props {
    pemasok: PaginatedPemasok;
    filters: Filters;
    flash?: {
        message?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pemasok',
        href: '/pemasok',
    },
];

export default function Index({ pemasok, filters, flash }: Props) {
    const getStatusBadge = (status: string) => {
        const statusConfig = {
            active: {
                label: 'Aktif',
                icon: CheckCircleIcon,
                bgColor: 'bg-green-100',
                textColor: 'text-green-700',
                borderColor: 'border-green-300',
            },
            inactive: {
                label: 'Tidak Aktif',
                icon: XCircleIcon,
                bgColor: 'bg-gray-100',
                textColor: 'text-gray-700',
                borderColor: 'border-gray-300',
            },
        };

        const config = statusConfig[status as keyof typeof statusConfig];
        if (!config) return <span className="text-gray-500 text-sm">{status}</span>;

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

    const columns = [
        {
            key: 'pemasok_id',
            label: 'ID Pemasok',
            sortable: true,
            hideable: true,
            defaultVisible: false,
        },
        {
            key: 'nama_pemasok',
            label: 'Nama',
            sortable: true,
            hideable: true,
            defaultVisible: true,
        },
        {
            key: 'email',
            label: 'Email',
            sortable: true,
            hideable: true,
            defaultVisible: true,
        },
        {
            key: 'status',
            label: 'Status',
            sortable: true,
            hideable: true,
            defaultVisible: true,
            render: (pemasok: Pemasok) => getStatusBadge(pemasok.status),
        },
    ];

    const filterOptions = [
        {
            key: 'status',
            label: 'Status',
            type: 'select' as const,
            placeholder: 'Semua Status',
            options: [
                { value: 'active', label: 'Aktif' },
                { value: 'inactive', label: 'Tidak Aktif' },
            ],
        },
    ];

    const actions = useMemo(
        () => [
            createEditAction<Pemasok>(
                (item) => `/pemasok/${item.pemasok_id}/edit`,
                (item) => item.status === 'active', // Only show edit for active
            ),
            createDeleteAction<Pemasok>(
                (item) => {
                    router.delete(`/pemasok/${item.pemasok_id}`, {
                        preserveState: false,
                        onError: (errors) => {
                            console.error('Delete failed:', errors);
                        },
                    });
                },
                (item) => item.status === 'active', // Only show delete for active
            ),
            {
                label: 'Aktifkan Kembali',
                variant: 'default' as const,
                onClick: (item: Pemasok) => {
                    router.post(
                        `/pemasok/${item.pemasok_id}/restore`,
                        {},
                        {
                            preserveState: false,
                        },
                    );
                },
                show: (item: Pemasok) => item.status === 'inactive', // Only show restore for inactive
            },
        ],
        [],
    );

    return (
        <TableTemplate<Pemasok>
            title="Manajemen Pemasok"
            breadcrumbs={breadcrumbs}
            data={pemasok}
            columns={columns}
            createUrl="/pemasok/create"
            createButtonText="Tambah"
            filters={filters}
            filterOptions={filterOptions}
            actions={actions}
            baseUrl="/pemasok"
            flash={flash}
            deleteDialogTitle="Hapus Pemasok"
            deleteDialogMessage={(pemasok) =>
                `Apakah Anda yakin ingin menghapus pemasok "${pemasok.nama_pemasok}"? Tindakan ini tidak dapat dibatalkan.`
            }
        />
    );
}
