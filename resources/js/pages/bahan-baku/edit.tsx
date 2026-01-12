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
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/bahan-baku/${bahanBaku.bahan_baku_id}`);
    }

    return (
        <FormTemplate
            title={`Edit Bahan Baku: ${bahanBaku.nama_bahan}`}
            breadcrumbs={breadcrumbs}
            backUrl="/bahan-baku"
            onSubmit={handleSubmit}
            processing={processing}
        >
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

                <FormField id="stok_bahan" label="Stok Bahan" error={errors.stok_bahan} required>
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

            <div className="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <p className="text-sm text-blue-800">
                    <strong>Catatan:</strong> EOQ, ROP, dan Safety Stock dihitung otomatis berdasarkan data transaksi historis.
                </p>
            </div>
        </FormTemplate>
    );
}
