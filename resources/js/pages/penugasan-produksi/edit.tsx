import { FormField, Select, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import React from 'react';

interface User {
    user_id: string;
    nama_lengkap: string;
}

interface PengadaanDetail {
    pengadaan_detail_id: string;
    nama_item: string;
    qty_diminta: number;
    qty_disetujui: number;
    satuan: string;
}

interface Penugasan {
    penugasan_id: number;
    pengadaan_detail_id: string;
    pengadaan_detail?: PengadaanDetail;
    user_id: string;
    user?: User;
    jumlah_produksi: number;
    deadline: string;
    status: string;
    catatan?: string;
}

interface SupervisorFormData {
    user_id: string;
    jumlah_produksi: string;
    deadline: string;
    status: string;
    catatan: string;
}

interface WorkerFormData {
    status: string;
}

interface Props {
    penugasan: Penugasan;
    workers: User[] | null;
    isWorker: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Penugasan Produksi',
        href: '/penugasan-produksi',
    },
    {
        title: 'Edit',
        href: '#',
    },
];

const statusOptions = [
    { value: 'assigned', label: 'Ditugaskan' },
    { value: 'in_progress', label: 'Sedang Dikerjakan' },
    { value: 'completed', label: 'Selesai' },
    { value: 'cancelled', label: 'Dibatalkan' },
];

export default function Edit({ penugasan, workers, isWorker }: Props) {
    // Combined form data with all possible fields
    type FormData = SupervisorFormData & Partial<WorkerFormData>;

    const { data, setData, put, processing, errors } = useForm<FormData>({
        user_id: penugasan.user_id || '',
        jumlah_produksi: penugasan.jumlah_produksi.toString(),
        deadline: penugasan.deadline.split('T')[0],
        status: penugasan.status,
        catatan: penugasan.catatan || '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/penugasan-produksi/${penugasan.penugasan_id}`);
    }

    const maxQty = penugasan.pengadaan_detail?.qty_disetujui ?? penugasan.pengadaan_detail?.qty_diminta ?? 0;

    const workerOptions = workers
        ? workers.map((worker) => ({
              value: worker.user_id,
              label: worker.nama_lengkap,
          }))
        : [];

    const errorRecord = errors as Record<string, string>;

    return (
        <FormTemplate
            title={`Edit Penugasan: ${penugasan.pengadaan_detail?.nama_item}`}
            breadcrumbs={breadcrumbs}
            backUrl="/penugasan-produksi"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Update Penugasan"
            processingText="Updating..."
        >
            {/* Item Produksi Info (read-only) */}
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Item Produksi</label>
                    <div className="rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        {penugasan.pengadaan_detail?.nama_item || '-'}
                    </div>
                </div>

                <div>
                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Satuan</label>
                    <div className="rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        {penugasan.pengadaan_detail?.satuan || '-'}
                    </div>
                </div>

                {!isWorker && (
                    <FormField id="user_id" label="Staf" error={errorRecord.user_id} required>
                        <Select
                            id="user_id"
                            value={data.user_id || ''}
                            onChange={(e) => setData('user_id', e.target.value)}
                            options={workerOptions}
                            placeholder="Pilih Staf"
                            error={errorRecord.user_id}
                        />
                    </FormField>
                )}

                {isWorker && (
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Worker</label>
                        <div className="rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            {penugasan.user?.nama_lengkap || '-'}
                        </div>
                    </div>
                )}

                {!isWorker && (
                    <FormField
                        id="jumlah_produksi"
                        label={`Jumlah Produksi${maxQty > 0 ? ` (Max: ${maxQty})` : ''}`}
                        error={errorRecord.jumlah_produksi}
                        required
                    >
                        <TextInput
                            id="jumlah_produksi"
                            type="number"
                            min="1"
                            max={maxQty || undefined}
                            value={data.jumlah_produksi || ''}
                            onChange={(e) => setData('jumlah_produksi', e.target.value)}
                            placeholder="Masukkan jumlah produksi"
                            error={errorRecord.jumlah_produksi}
                        />
                    </FormField>
                )}

                {!isWorker && (
                    <FormField id="deadline" label="Deadline" error={errorRecord.deadline} required>
                        <TextInput
                            id="deadline"
                            type="date"
                            value={data.deadline || ''}
                            onChange={(e) => setData('deadline', e.target.value)}
                            error={errorRecord.deadline}
                        />
                    </FormField>
                )}

                <FormField id="status" label="Status" error={errorRecord.status} required>
                    <Select
                        id="status"
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
                        options={statusOptions}
                        placeholder="Pilih Status"
                        error={errorRecord.status}
                    />
                </FormField>
            </div>

            {!isWorker && (
                <div className="mt-6">
                    <FormField id="catatan" label="Catatan" error={errorRecord.catatan}>
                        <TextArea
                            id="catatan"
                            value={data.catatan || ''}
                            onChange={(e) => setData('catatan', e.target.value)}
                            placeholder="Masukkan catatan (opsional)"
                            error={errorRecord.catatan}
                        />
                    </FormField>
                </div>
            )}
        </FormTemplate>
    );
}
