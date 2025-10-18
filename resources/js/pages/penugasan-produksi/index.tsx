import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { TableTemplate } from '@/components/templates/table-template';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EyeIcon, PencilIcon, PlusIcon } from '@heroicons/react/24/outline';

interface Penugasan {
    penugasan_id: number;
    pengadaan: {
        kode_pengadaan: string;
        detail: Array<{
            produk: {
                nama_produk: string;
            };
        }>;
    };
    assigned_to_user: {
        name: string;
    };
    assigned_by_user: {
        name: string;
    };
    qty_assigned: number;
    qty_completed: number;
    status: string;
    deadline: string;
    progress_percentage: number;
}

interface PageProps {
    penugasan: {
        data: Penugasan[];
        links: any;
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        status?: string;
    };
}

export default function Index() {
    const { penugasan, filters } = usePage<PageProps>().props;

    const getStatusBadge = (status: string) => {
        const statusConfig: Record<string, { variant: any; label: string }> = {
            assigned: { variant: 'secondary', label: 'Ditugaskan' },
            in_progress: { variant: 'default', label: 'Berjalan' },
            completed: { variant: 'default', label: 'Selesai' },
            cancelled: { variant: 'destructive', label: 'Dibatalkan' },
        };
        const config = statusConfig[status] || { variant: 'secondary', label: status };
        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    const columns = [
        {
            header: 'Kode Pengadaan',
            accessorKey: 'pengadaan.kode_pengadaan',
        },
        {
            header: 'Produk',
            cell: ({ row }: any) => row.original.pengadaan.detail[0]?.produk?.nama_produk || 'N/A',
        },
        {
            header: 'Staf',
            accessorKey: 'assigned_to_user.name',
        },
        {
            header: 'Target',
            accessorKey: 'qty_assigned',
        },
        {
            header: 'Selesai',
            accessorKey: 'qty_completed',
        },
        {
            header: 'Progress',
            cell: ({ row }: any) => `${row.original.progress_percentage}%`,
        },
        {
            header: 'Deadline',
            accessorKey: 'deadline',
        },
        {
            header: 'Status',
            cell: ({ row }: any) => getStatusBadge(row.original.status),
        },
        {
            header: 'Aksi',
            cell: ({ row }: any) => (
                <div className="flex gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.visit(route('penugasan-produksi.show', row.original.penugasan_id))}
                    >
                        <EyeIcon className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => router.visit(route('penugasan-produksi.edit', row.original.penugasan_id))}
                    >
                        <PencilIcon className="h-4 w-4" />
                    </Button>
                </div>
            ),
        },
    ];

    const filterOptions = [
        { value: '', label: 'Semua Status' },
        { value: 'assigned', label: 'Ditugaskan' },
        { value: 'in_progress', label: 'Berjalan' },
        { value: 'completed', label: 'Selesai' },
        { value: 'cancelled', label: 'Dibatalkan' },
    ];

    return (
        <AppLayout title="Penugasan Produksi">
            <TableTemplate
                title="Penugasan Produksi"
                description="Kelola penugasan produksi untuk Staf RnD"
                data={penugasan.data}
                columns={columns}
                searchPlaceholder="Cari produk..."
                filters={filters}
                pagination={{
                    links: penugasan.links,
                    currentPage: penugasan.current_page,
                    lastPage: penugasan.last_page,
                }}
                filterOptions={filterOptions}
                createButton={{
                    label: 'Tambah Penugasan',
                    icon: <PlusIcon className="h-4 w-4" />,
                    href: route('penugasan-produksi.create'),
                }}
            />
        </AppLayout>
    );
}
