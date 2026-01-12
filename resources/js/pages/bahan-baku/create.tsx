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
                        placeholder="Masukkan jumlah  stok"
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

            <div className="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <p className="text-sm text-blue-800">
                    <strong>Catatan:</strong> EOQ, ROP, dan Safety Stock akan dihitung otomatis berdasarkan data transaksi historis.
                </p>
            </div>
        </FormTemplate>
    );
}
