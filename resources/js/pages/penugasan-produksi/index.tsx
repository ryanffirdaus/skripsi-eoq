import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';

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
    penugasan_id: number;
    pengadaan_detail_id: string;
    pengadaan_detail?: PengadaanDetail;
    user_id: string;
    user?: User;
    created_by?: string | User;
    createdBy?: User;
    created_by_user?: User;
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
    { value: 'assigned', label: 'Ditugaskan' },
    { value: 'in_progress', label: 'Sedang Dikerjakan' },
    { value: 'completed', label: 'Selesai' },
    { value: 'cancelled', label: 'Dibatalkan' },
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
    const mode = filters.mode === 'assigned' ? 'assigned' : 'all';

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
            key: 'user_id',
            label: 'Petugas',
            sortable: false,
            hideable: true,
            defaultVisible: true,
            render: (item: Penugasan) => item.user?.nama_lengkap || '-',
        },
        {
            key: 'created_by',
            label: 'Supervisor',
            sortable: false,
            hideable: true,
            defaultVisible: false,
            render: (item: Penugasan) => {
                // Handle multiple possible field names due to snake_case/camelCase conversion
                const createdByUser = item.createdBy || item.created_by_user || (typeof item.created_by === 'object' ? item.created_by : null);
                return (createdByUser as User)?.nama_lengkap || '-';
            },
        },
        {
            key: 'jumlah_produksi',
            label: 'Jumlah Produksi',
            sortable: true,
            hideable: true,
            defaultVisible: false,
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
            render: (item: Penugasan) => {
                const statusMap: Record<string, string> = {
                    assigned: 'Ditugaskan',
                    in_progress: 'Sedang Dikerjakan',
                    completed: 'Selesai',
                    cancelled: 'Dibatalkan',
                };
                return statusMap[item.status] || item.status;
            },
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
        createEditAction<Penugasan>(
            (item) => `/penugasan-produksi/${item.penugasan_id}/edit`,
            (item) => item.status !== 'completed' && item.status !== 'cancelled',
        ),
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
    const title = mode === 'assigned' ? 'Yang Ditugaskan ke Workers' : isAdmin ? 'Semua Penugasan Produksi' : 'Tugas Saya';

    const modeBreadcrumbs =
        mode === 'assigned'
            ? [
                  {
                      title: 'Penugasan Produksi',
                      href: '/penugasan-produksi',
                  },
                  {
                      title: 'Yang Ditugaskan',
                      href: '/penugasan-produksi?mode=assigned',
                  },
              ]
            : breadcrumbs;

    return (
        <TableTemplate
            title={title}
            breadcrumbs={modeBreadcrumbs}
            data={penugasan}
            columns={columns}
            searchPlaceholder="Cari item produksi..."
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
