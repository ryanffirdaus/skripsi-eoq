// Edit.tsx - Bahan Baku
import { FormField, NumberInput, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import React from 'react';

interface BahanBaku {
    bahan_baku_id: string;
    nama_bahan: string;
    lokasi_bahan: string;
    stok_bahan: number;
    satuan_bahan: string;
    harga_bahan: number;
    permintaan_harian_rata2_bahan: number;
    permintaan_harian_maksimum_bahan: number;
    waktu_tunggu_rata2_bahan: number;
    waktu_tunggu_maksimum_bahan: number;
    permintaan_tahunan: number;
    biaya_pemesanan_bahan: number;
    biaya_penyimpanan_bahan: number;
    safety_stock_bahan?: number;
    rop_bahan?: number;
    eoq_bahan?: number;
}

interface Props {
    bahanBaku: BahanBaku;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bahan Baku',
        href: '/bahan-baku',
    },
    {
        title: 'Edit Bahan Baku',
        href: '#',
    },
];

export default function Edit({ bahanBaku }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        nama_bahan: bahanBaku.nama_bahan || '',
        lokasi_bahan: bahanBaku.lokasi_bahan || '',
        stok_bahan: bahanBaku.stok_bahan?.toString() || '',
        satuan_bahan: bahanBaku.satuan_bahan || '',
        harga_bahan: bahanBaku.harga_bahan?.toString() || '',
        permintaan_harian_rata2_bahan: bahanBaku.permintaan_harian_rata2_bahan?.toString() || '',
        permintaan_harian_maksimum_bahan: bahanBaku.permintaan_harian_maksimum_bahan?.toString() || '',
        waktu_tunggu_rata2_bahan: bahanBaku.waktu_tunggu_rata2_bahan?.toString() || '',
        waktu_tunggu_maksimum_bahan: bahanBaku.waktu_tunggu_maksimum_bahan?.toString() || '',
        permintaan_tahunan: bahanBaku.permintaan_tahunan?.toString() || '',
        biaya_pemesanan_bahan: bahanBaku.biaya_pemesanan_bahan?.toString() || '',
        biaya_penyimpanan_bahan: bahanBaku.biaya_penyimpanan_bahan?.toString() || '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/bahan-baku/${bahanBaku.bahan_baku_id}`);
    }

    const demandSection = {
        title: 'Demand Information',
        children: (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField
                    id="permintaan_harian_rata2_bahan"
                    label="Permintaan Harian Rata-rata"
                    error={errors.permintaan_harian_rata2_bahan}
                    required
                >
                    <NumberInput
                        id="permintaan_harian_rata2_bahan"
                        value={data.permintaan_harian_rata2_bahan}
                        onChange={(e) => setData('permintaan_harian_rata2_bahan', e.target.value)}
                        placeholder="Enter average daily demand"
                        error={errors.permintaan_harian_rata2_bahan}
                        min="0"
                        step="0.01"
                    />
                </FormField>

                <FormField
                    id="permintaan_harian_maksimum_bahan"
                    label="Permintaan Harian Maksimum"
                    error={errors.permintaan_harian_maksimum_bahan}
                    required
                >
                    <NumberInput
                        id="permintaan_harian_maksimum_bahan"
                        value={data.permintaan_harian_maksimum_bahan}
                        onChange={(e) => setData('permintaan_harian_maksimum_bahan', e.target.value)}
                        placeholder="Enter maximum daily demand"
                        error={errors.permintaan_harian_maksimum_bahan}
                        min="0"
                        step="0.01"
                    />
                </FormField>

                <FormField id="permintaan_tahunan" label="Permintaan Tahunan" error={errors.permintaan_tahunan} required>
                    <NumberInput
                        id="permintaan_tahunan"
                        value={data.permintaan_tahunan}
                        onChange={(e) => setData('permintaan_tahunan', e.target.value)}
                        placeholder="Enter annual demand"
                        error={errors.permintaan_tahunan}
                        min="0"
                        step="0.01"
                    />
                </FormField>
            </div>
        ),
    };

    const leadTimeSection = {
        title: 'Lead Time Information',
        children: (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="waktu_tunggu_rata2_bahan" label="Waktu Tunggu Rata-rata (hari)" error={errors.waktu_tunggu_rata2_bahan} required>
                    <NumberInput
                        id="waktu_tunggu_rata2_bahan"
                        value={data.waktu_tunggu_rata2_bahan}
                        onChange={(e) => setData('waktu_tunggu_rata2_bahan', e.target.value)}
                        placeholder="Enter average lead time"
                        error={errors.waktu_tunggu_rata2_bahan}
                        min="0"
                        step="0.01"
                    />
                </FormField>

                <FormField id="waktu_tunggu_maksimum_bahan" label="Waktu Tunggu Maksimum (hari)" error={errors.waktu_tunggu_maksimum_bahan} required>
                    <NumberInput
                        id="waktu_tunggu_maksimum_bahan"
                        value={data.waktu_tunggu_maksimum_bahan}
                        onChange={(e) => setData('waktu_tunggu_maksimum_bahan', e.target.value)}
                        placeholder="Enter maximum lead time"
                        error={errors.waktu_tunggu_maksimum_bahan}
                        min="0"
                        step="0.01"
                    />
                </FormField>
            </div>
        ),
    };

    const costSection = {
        title: 'Cost Information',
        children: (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="biaya_pemesanan_bahan" label="Biaya Pemesanan" error={errors.biaya_pemesanan_bahan} required>
                    <NumberInput
                        id="biaya_pemesanan_bahan"
                        value={data.biaya_pemesanan_bahan}
                        onChange={(e) => setData('biaya_pemesanan_bahan', e.target.value)}
                        placeholder="Enter ordering cost"
                        error={errors.biaya_pemesanan_bahan}
                        min="0"
                        step="0.01"
                    />
                </FormField>

                <FormField id="biaya_penyimpanan_bahan" label="Biaya Penyimpanan" error={errors.biaya_penyimpanan_bahan} required>
                    <NumberInput
                        id="biaya_penyimpanan_bahan"
                        value={data.biaya_penyimpanan_bahan}
                        onChange={(e) => setData('biaya_penyimpanan_bahan', e.target.value)}
                        placeholder="Enter storage cost"
                        error={errors.biaya_penyimpanan_bahan}
                        min="0"
                        step="0.01"
                    />
                </FormField>
            </div>
        ),
    };

    return (
        <FormTemplate
            title={`Edit Bahan Baku: ${bahanBaku.nama_bahan}`}
            breadcrumbs={breadcrumbs}
            backUrl="/bahan-baku"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Update Bahan Baku"
            processingText="Updating..."
            sections={[demandSection, leadTimeSection, costSection]}
        >
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <FormField id="nama_bahan" label="Nama Bahan" error={errors.nama_bahan} required>
                    <TextInput
                        id="nama_bahan"
                        type="text"
                        value={data.nama_bahan}
                        onChange={(e) => setData('nama_bahan', e.target.value)}
                        placeholder="Enter material name"
                        error={errors.nama_bahan}
                    />
                </FormField>

                <FormField id="lokasi_bahan" label="Lokasi Bahan" error={errors.lokasi_bahan} required>
                    <TextInput
                        id="lokasi_bahan"
                        type="text"
                        value={data.lokasi_bahan}
                        onChange={(e) => setData('lokasi_bahan', e.target.value)}
                        placeholder="Enter material location"
                        error={errors.lokasi_bahan}
                    />
                </FormField>

                <FormField id="stok_bahan" label="Stok Bahan" error={errors.stok_bahan} required>
                    <NumberInput
                        id="stok_bahan"
                        value={data.stok_bahan}
                        onChange={(e) => setData('stok_bahan', e.target.value)}
                        placeholder="Enter stock quantity"
                        error={errors.stok_bahan}
                        min="0"
                        step="0.01"
                    />
                </FormField>

                <FormField id="satuan_bahan" label="Satuan Bahan" error={errors.satuan_bahan} required>
                    <TextInput
                        id="satuan_bahan"
                        type="text"
                        value={data.satuan_bahan}
                        onChange={(e) => setData('satuan_bahan', e.target.value)}
                        placeholder="Enter unit (kg, pcs, liter, etc.)"
                        error={errors.satuan_bahan}
                    />
                </FormField>

                <FormField id="harga_bahan" label="Harga Bahan" error={errors.harga_bahan} required>
                    <NumberInput
                        id="harga_bahan"
                        value={data.harga_bahan}
                        onChange={(e) => setData('harga_bahan', e.target.value)}
                        placeholder="Enter material price"
                        error={errors.harga_bahan}
                        min="0"
                        step="0.01"
                    />
                </FormField>
            </div>
        </FormTemplate>
    );
}
