import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { ClockIcon, ClipboardDocumentListIcon, CogIcon, CheckBadgeIcon, XCircleIcon } from '@heroicons/react/24/outline';

interface User extends Record<string, unknown> {
    user_id: string;
    nama_lengkap: string;
}

interface BahanBaku extends Record<string, unknown> {
    bahan_baku_id: string;
    nama_bahan: string;
    satuan_bahan: string;
}

interface Produk extends Record<string, unknown> {
    produk_id: string;
    nama_produk: string;
    satuan_produk: string;
}

interface PengadaanDetail extends Record<string, unknown> {
    pengadaan_detail_id: string;
    jenis_barang: string;
    barang_id: string;
    nama_item: string;
    satuan: string;
    bahan_baku?: BahanBaku;
    produk?: Produk;
}

interface Penugasan extends Record<string, unknown> {
    penugasan_id: string;
    pengadaan_detail_id: string;
    pengadaan_detail?: PengadaanDetail;
    user_id: string;
    user?: User;
    dibuat_oleh?: string | User;
    createdBy?: User;
    dibuat_oleh_user?: User;
    jumlah_produksi: number;
    status: string;
    deadline: string;
    catatan?: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPenugasan {
    data: Penugasan[];
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
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    per_page: number;
    mode?: string;
    [key: string]: string | number | undefined;
}

interface Props {
    penugasan: PaginatedPenugasan;
    filters: Filters;
    userRole: string;
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Penugasan Produksi',
        href: '/penugasan-produksi',
    },
];

const statusOptions = [
    { value: 'ditugaskan', label: 'Ditugaskan' },
    { value: 'proses', label: 'Proses' },
    { value: 'selesai', label: 'Selesai' },
    { value: 'dibatalkan', label: 'Dibatalkan' },
];

// Helper function to format deadline
const formatDeadline = (deadline: string): string => {
    try {
        const date = new Date(deadline);
        if (isNaN(date.getTime())) {
            return deadline;
        }
        // Format: "19 Oct 2025" or use formatDistanceToNow
        const formatter = new Intl.DateTimeFormat('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
        return formatter.format(date);
    } catch {
        return deadline;
    }
};

export default function Index({ penugasan, filters, userRole, flash }: Props) {
    const isAdmin = ['R01', 'R08'].includes(userRole);
    const mode = filters.mode === 'ditugaskan' ? 'ditugaskan' : 'all';

    const getStatusBadge = (status: string) => {
        const statusConfig = {
            ditugaskan: {
                label: 'Ditugaskan',
                icon: ClipboardDocumentListIcon,
                bgColor: 'bg-blue-100',
                textColor: 'text-blue-700',
                borderColor: 'border-blue-300',
            },
            proses: {
                label: 'Proses',
                icon: CogIcon,
                bgColor: 'bg-purple-100',
                textColor: 'text-purple-700',
                borderColor: 'border-purple-300',
            },
            selesai: {
                label: 'Selesai',
                icon: CheckBadgeIcon,
                bgColor: 'bg-green-100',
                textColor: 'text-green-700',
                borderColor: 'border-green-300',
            },
            dibatalkan: {
                label: 'Dibatalkan',
                icon: XCircleIcon,
                bgColor: 'bg-red-100',
                textColor: 'text-red-700',
                borderColor: 'border-red-300',
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
            key: 'pengadaan_detail_id',
            label: 'Produk',
            sortable: true,
            hideable: true,
            defaultVisible: true,
            render: (item: Penugasan) => {
                if (!item.pengadaan_detail) return '-';
                return item.pengadaan_detail.nama_item || '-';
            },
        },
        {
            key: 'satuan',
            label: 'Satuan',
            sortable: false,
            hideable: true,
            defaultVisible: true,
            render: (item: Penugasan) => {
                return item.pengadaan_detail?.satuan || '-';
            },
        },
        {
            key: 'deadline',
            label: 'Deadline',
            sortable: true,
            hideable: true,
            defaultVisible: true,
            render: (item: Penugasan) => formatDeadline(item.deadline),
        },
        {
            key: 'status',
            label: 'Status',
            sortable: true,
            hideable: true,
            defaultVisible: true,
            render: (item: Penugasan) => getStatusBadge(item.status),
        },
        {
            key: 'user_id',
            label: 'Petugas',
            sortable: false,
            hideable: true,
            defaultVisible: true,
            render: (item: Penugasan) => item.user?.nama_lengkap || '-',
        },
    ];

    const filterOptions = [
        {
            key: 'status',
            label: 'Status',
            type: 'select' as const,
            placeholder: 'Semua Status',
            options: [{ value: '', label: 'Semua Status' }, ...statusOptions],
        },
    ];

    const actions = [
        createEditAction<Penugasan>((item) => `/penugasan-produksi/${item.penugasan_id}/edit`),
        ...(isAdmin
            ? [
                  createDeleteAction<Penugasan>((item) => {
                      router.delete(`/penugasan-produksi/${item.penugasan_id}`, {
                          preserveState: false,
                          onError: (errors) => {
                              console.error('Delete failed:', errors);
                          },
                      });
                  }),
              ]
            : []),
    ];

    // Determine title and breadcrumb based on mode
    const title = 'Manajemen Penugasan Produksi';

    const modeBreadcrumbs =
        mode === 'ditugaskan'
            ? [
                  {
                      title: 'Penugasan Produksi',
                      href: '/penugasan-produksi',
                  },
                  {
                      title: 'Yang Ditugaskan',
                      href: '/penugasan-produksi?mode=ditugaskan',
                  },
              ]
            : breadcrumbs;

    return (
        <TableTemplate
            title={title}
            breadcrumbs={modeBreadcrumbs}
            data={penugasan}
            columns={columns}
            filters={filters}
            filterOptions={filterOptions}
            baseUrl="/penugasan-produksi"
            actions={actions}
            flash={flash}
            createUrl={isAdmin ? '/penugasan-produksi/create' : undefined}
            createButtonText="Tambah"
        />
    );
}
