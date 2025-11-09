// Create.tsx - Bahan Baku
import { FormField, NumberInput, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bahan Baku',
        href: '/bahan-baku',
    },
    {
        title: 'Tambah Bahan Baku',
        href: '#',
    },
];

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        nama_bahan: '',
        lokasi_bahan: '',
        stok_bahan: '',
        satuan_bahan: '',
        harga_bahan: '',
        permintaan_harian_rata2_bahan: '',
        permintaan_harian_maksimum_bahan: '',
        waktu_tunggu_rata2_bahan: '',
        waktu_tunggu_maksimum_bahan: '',
        permintaan_tahunan: '',
        biaya_pemesanan_bahan: '',
        biaya_penyimpanan_bahan: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/bahan-baku');
    }

    return (
        <FormTemplate title="Tambah Bahan Baku" breadcrumbs={breadcrumbs} backUrl="/bahan-baku" onSubmit={handleSubmit} processing={processing}>
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <FormField id="nama_bahan" label="Nama Bahan" error={errors.nama_bahan} required>
                    <TextInput
                        id="nama_bahan"
                        type="text"
                        value={data.nama_bahan}
                        onChange={(e) => setData('nama_bahan', e.target.value)}
                        placeholder="Masukkan nama bahan"
                        error={errors.nama_bahan}
                    />
                </FormField>

                <FormField id="lokasi_bahan" label="Lokasi Bahan" error={errors.lokasi_bahan} required>
                    <TextInput
                        id="lokasi_bahan"
                        type="text"
                        value={data.lokasi_bahan}
                        onChange={(e) => setData('lokasi_bahan', e.target.value)}
                        placeholder="Masukkan lokasi bahan"
                        error={errors.lokasi_bahan}
                    />
                </FormField>

                <FormField id="stok_bahan" label="Stok Bahan" error={errors.stok_bahan}>
                    <NumberInput
                        id="stok_bahan"
                        value={data.stok_bahan}
                        onChange={(e) => setData('stok_bahan', e.target.value)}
                        placeholder="Masukkan jumlah stok"
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
                        placeholder="Masukkan satuan (kg, pcs, liter, dll.)"
                        error={errors.satuan_bahan}
                    />
                </FormField>

                <FormField id="harga_bahan" label="Harga Bahan" error={errors.harga_bahan} required>
                    <NumberInput
                        id="harga_bahan"
                        value={data.harga_bahan}
                        onChange={(e) => setData('harga_bahan', e.target.value)}
                        placeholder="Masukkan harga bahan"
                        error={errors.harga_bahan}
                        min="0"
                        step="0.01"
                    />
                </FormField>
            </div>

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
                        placeholder="Masukkan permintaan harian rata-rata"
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
                        placeholder="Masukkan permintaan harian maksimum"
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
                        placeholder="Masukkan permintaan tahunan"
                        error={errors.permintaan_tahunan}
                        min="0"
                        step="0.01"
                    />
                </FormField>
            </div>

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="waktu_tunggu_rata2_bahan" label="Waktu Tunggu Rata-rata (hari)" error={errors.waktu_tunggu_rata2_bahan} required>
                    <NumberInput
                        id="waktu_tunggu_rata2_bahan"
                        value={data.waktu_tunggu_rata2_bahan}
                        onChange={(e) => setData('waktu_tunggu_rata2_bahan', e.target.value)}
                        placeholder="Masukkan waktu tunggu rata-rata"
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
                        placeholder="Masukkan waktu tunggu maksimum"
                        error={errors.waktu_tunggu_maksimum_bahan}
                        min="0"
                        step="0.01"
                    />
                </FormField>
            </div>

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="biaya_pemesanan_bahan" label="Biaya Pemesanan" error={errors.biaya_pemesanan_bahan} required>
                    <NumberInput
                        id="biaya_pemesanan_bahan"
                        value={data.biaya_pemesanan_bahan}
                        onChange={(e) => setData('biaya_pemesanan_bahan', e.target.value)}
                        placeholder="Masukkan biaya pemesanan"
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
                        placeholder="Masukkan biaya penyimpanan"
                        error={errors.biaya_penyimpanan_bahan}
                        min="0"
                        step="0.01"
                    />
                </FormField>
            </div>
        </FormTemplate>
    );
}
