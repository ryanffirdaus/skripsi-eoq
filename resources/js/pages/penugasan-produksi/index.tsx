import { createDeleteAction, createEditAction } from '@/components/table/table-actions';
import TableTemplate from '@/components/table/table-template';
import { type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';

interface User extends Record<string, unknown> {
    user_id: string;
    nama_lengkap: string;
}

interface PengadaanDetail extends Record<string, unknown> {
    pengadaan_detail_id: string;
    nama_item: string;
    satuan: string;
}

interface Penugasan extends Record<string, unknown> {
    penugasan_id: number;
    pengadaan_detail_id: string;
    pengadaanDetail?: PengadaanDetail;
    user_id: string;
    user?: User;
    created_by: string;
    createdBy?: User;
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

export default function Index({ penugasan, filters, userRole, flash }: Props) {
    const isAdmin = ['R01', 'R09'].includes(userRole);
    const mode = filters.mode === 'assigned' ? 'assigned' : 'all';

    const baseColumns = [
        {
            key: 'pengadaan_detail_id',
            label: 'Item Produksi',
            sortable: true,
            hideable: true,
            defaultVisible: true,
            render: (item: Penugasan) => item.pengadaanDetail?.nama_item || '-',
        },
        {
            key: 'satuan',
            label: 'Satuan',
            sortable: false,
            hideable: true,
            defaultVisible: true,
            render: (item: Penugasan) => item.pengadaanDetail?.satuan || '-',
        },
    ];

    // Columns untuk mode "all" (semua penugasan) - tampilkan Worker dan Supervisor
    const allModeColumns = isAdmin
        ? [
              {
                  key: 'user_id',
                  label: 'Worker',
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
                  defaultVisible: true,
                  render: (item: Penugasan) => item.createdBy?.nama_lengkap || '-',
              },
          ]
        : [];

    // Columns untuk mode "assigned" (tugas yang ditugaskan ke workers) - tampilkan hanya Supervisor
    const assignedModeColumns =
        isAdmin && mode === 'assigned'
            ? [
                  {
                      key: 'created_by',
                      label: 'Supervisor',
                      sortable: false,
                      hideable: true,
                      defaultVisible: true,
                      render: (item: Penugasan) => item.createdBy?.nama_lengkap || '-',
                  },
              ]
            : [];

    const adminColumns = mode === 'assigned' ? assignedModeColumns : allModeColumns;

    const columns = [
        ...baseColumns,
        ...adminColumns,
        {
            key: 'jumlah_produksi',
            label: 'Jumlah Produksi',
            sortable: true,
            hideable: true,
            defaultVisible: true,
        },
        {
            key: 'deadline',
            label: 'Deadline',
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
            createButtonText="Buat Penugasan"
        />
    );
}
