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

interface Props {
    pengadaanDetails: PengadaanDetail[];
    workers: User[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Penugasan Produksi',
        href: '/penugasan-produksi',
    },
    {
        title: 'Create',
        href: '/penugasan-produksi/create',
    },
];

export default function Create({ pengadaanDetails, workers }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        pengadaan_detail_id: '',
        user_id: '',
        jumlah_produksi: '',
        deadline: new Date().toISOString().split('T')[0],
        catatan: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/penugasan-produksi');
    }

    // Get max quantity for selected pengadaan detail
    const selectedDetail = pengadaanDetails.find((p) => p.pengadaan_detail_id === data.pengadaan_detail_id);
    const maxQty = selectedDetail?.qty_disetujui ?? selectedDetail?.qty_diminta ?? 0;

    const pengadaanDetailOptions = pengadaanDetails.map((detail) => ({
        value: detail.pengadaan_detail_id,
        label: `${detail.nama_item} (Minta: ${detail.qty_diminta}, Disetujui: ${detail.qty_disetujui} ${detail.satuan})`,
    }));

    const workerOptions = workers.map((worker) => ({
        value: worker.user_id,
        label: worker.nama_lengkap,
    }));

    return (
        <FormTemplate
            title="Buat Penugasan Produksi"
            breadcrumbs={breadcrumbs}
            backUrl="/penugasan-produksi"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Buat Penugasan"
            processingText="Creating..."
        >
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="pengadaan_detail_id" label="Item Produksi" error={errors.pengadaan_detail_id} required>
                    <Select
                        id="pengadaan_detail_id"
                        value={data.pengadaan_detail_id}
                        onChange={(e) => setData('pengadaan_detail_id', e.target.value)}
                        options={pengadaanDetailOptions}
                        placeholder="Pilih Item Produksi"
                        error={errors.pengadaan_detail_id}
                    />
                </FormField>

                <FormField id="user_id" label="Staf" error={errors.user_id} required>
                    <Select
                        id="user_id"
                        value={data.user_id}
                        onChange={(e) => setData('user_id', e.target.value)}
                        options={workerOptions}
                        placeholder="Pilih Staf"
                        error={errors.user_id}
                    />
                </FormField>

                <FormField
                    id="jumlah_produksi"
                    label={`Jumlah Produksi${maxQty > 0 ? ` (Max: ${maxQty})` : ''}`}
                    error={errors.jumlah_produksi}
                    required
                >
                    <TextInput
                        id="jumlah_produksi"
                        type="number"
                        min="1"
                        max={maxQty || undefined}
                        value={data.jumlah_produksi}
                        onChange={(e) => setData('jumlah_produksi', e.target.value)}
                        placeholder="Masukkan jumlah produksi"
                        error={errors.jumlah_produksi}
                    />
                </FormField>

                <FormField id="deadline" label="Deadline" error={errors.deadline} required>
                    <TextInput
                        id="deadline"
                        type="date"
                        value={data.deadline}
                        onChange={(e) => setData('deadline', e.target.value)}
                        error={errors.deadline}
                    />
                </FormField>
            </div>

            <div className="mt-6">
                <FormField id="catatan" label="Catatan" error={errors.catatan}>
                    <TextArea
                        id="catatan"
                        value={data.catatan}
                        onChange={(e) => setData('catatan', e.target.value)}
                        placeholder="Masukkan catatan (opsional)"
                        error={errors.catatan}
                    />
                </FormField>
            </div>
        </FormTemplate>
    );
}
