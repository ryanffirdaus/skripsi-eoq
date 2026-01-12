// Create.tsx - Produk
import { FormField, NumberInput, Select, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { buttonVariants } from '@/lib/colors';
import { type BreadcrumbItem } from '@/types';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import { useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface BahanBaku {
    bahan_baku_id: string;
    nama_bahan: string;
    satuan_bahan: string;
}

interface BahanBakuInput {
    bahan_baku_id: string;
    jumlah_bahan_baku: number;
}

interface Props {
    bahanBakus: BahanBaku[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Produk',
        href: '/produk',
    },
    {
        title: 'Tambah Produk',
        href: '/produk/create',
    },
];

export default function Create({ bahanBakus }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        nama_produk: '',
        lokasi_produk: '',
        stok_produk: '',
        satuan_produk: '',
        hpp_produk: '',
        harga_jual: '',
        bahan_baku: [] as BahanBakuInput[],
    });

    const [selectedBahan, setSelectedBahan] = useState<string>('');
    const [jumlahBahan, setJumlahBahan] = useState<string>('');

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/produk');
    };

    const addBahanBaku = () => {
        if (selectedBahan && parseFloat(jumlahBahan) > 0) {
            const exists = data.bahan_baku.some((item) => item.bahan_baku_id === selectedBahan);
            if (!exists) {
                setData('bahan_baku', [...data.bahan_baku, { bahan_baku_id: selectedBahan, jumlah_bahan_baku: parseFloat(jumlahBahan) }]);
                setSelectedBahan('');
                setJumlahBahan('');
            }
        }
    };

    const removeBahanBaku = (index: number) => {
        const newBahanBaku = data.bahan_baku.filter((_, i) => i !== index);
        setData('bahan_baku', newBahanBaku);
    };

    const getBahanBakuName = (id: string) => {
        const bahan = bahanBakus.find((b) => b.bahan_baku_id === id);
        return bahan ? `${bahan.nama_bahan} (${bahan.satuan_bahan})` : '';
    };

    return (
        <FormTemplate title="Tambah Produk" breadcrumbs={breadcrumbs} backUrl="/produk" onSubmit={handleSubmit} processing={processing}>
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <FormField id="nama_produk" label="Nama Produk" error={errors.nama_produk} required>
                    <TextInput
                        id="nama_produk"
                        type="text"
                        value={data.nama_produk}
                        onChange={(e) => setData('nama_produk', e.target.value)}
                        placeholder="Masukkan nama produk"
                        error={errors.nama_produk}
                    />
                </FormField>

                <FormField id="lokasi_produk" label="Lokasi Produk" error={errors.lokasi_produk} required>
                    <TextInput
                        id="lokasi_produk"
                        type="text"
                        value={data.lokasi_produk}
                        onChange={(e) => setData('lokasi_produk', e.target.value)}
                        placeholder="Masukkan lokasi produk"
                        error={errors.lokasi_produk}
                    />
                </FormField>

                <FormField id="stok_produk" label="Stok Produk" error={errors.stok_produk}>
                    <NumberInput
                        id="stok_produk"
                        value={data.stok_produk}
                        onChange={(e) => setData('stok_produk', e.target.value)}
                        placeholder="Masukkan jumlah stok"
                        error={errors.stok_produk}
                        min="0"
                        step="0.01"
                    />
                </FormField>

                <FormField id="satuan_produk" label="Satuan Produk" error={errors.satuan_produk} required>
                    <TextInput
                        id="satuan_produk"
                        type="text"
                        value={data.satuan_produk}
                        onChange={(e) => setData('satuan_produk', e.target.value)}
                        placeholder="Masukkan satuan (pcs, kg, liter, dll.)"
                        error={errors.satuan_produk}
                    />
                </FormField>

                <FormField id="hpp_produk" label="HPP Produk" error={errors.hpp_produk} required>
                    <NumberInput
                        id="hpp_produk"
                        value={data.hpp_produk}
                        onChange={(e) => setData('hpp_produk', e.target.value)}
                        placeholder="Masukkan HPP produk"
                        error={errors.hpp_produk}
                        min="0"
                        step="0.01"
                    />
                </FormField>

                <FormField id="harga_jual" label="Harga Jual" error={errors.harga_jual} required>
                    <NumberInput
                        id="harga_jual"
                        value={data.harga_jual}
                        onChange={(e) => setData('harga_jual', e.target.value)}
                        placeholder="Masukkan harga jual produk"
                        error={errors.harga_jual}
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

            <div className="space-y-4">
                {/* Add Bahan Baku */}
                <div className="grid grid-cols-1 items-end gap-4 sm:grid-cols-3">
                    <FormField id="select_bahan" label="Pilih Bahan Baku">
                        <Select
                            id="select_bahan"
                            value={selectedBahan}
                            onChange={(e) => setSelectedBahan(e.target.value)}
                            options={[
                                { value: '', label: 'Pilih Bahan Baku' },
                                ...bahanBakus.map((bahan) => ({
                                    value: bahan.bahan_baku_id,
                                    label: `${bahan.nama_bahan} (${bahan.satuan_bahan})`,
                                })),
                            ]}
                        />
                    </FormField>

                    <FormField id="jumlah_bahan" label="Jumlah Bahan Baku">
                        <NumberInput
                            id="jumlah_bahan"
                            value={jumlahBahan}
                            onChange={(e) => setJumlahBahan(e.target.value)}
                            placeholder="Masukkan jumlah"
                            min="0"
                            step="0.01"
                        />
                    </FormField>

                    <button
                        type="button"
                        onClick={addBahanBaku}
                        className={`${buttonVariants.primary} flex items-center justify-center space-x-2 rounded-md px-4 py-2`}
                    >
                        <PlusIcon className="h-4 w-4" />
                        <span>Tambah</span>
                    </button>
                </div>

                {/* List Bahan Baku */}
                {data.bahan_baku.length > 0 && (
                    <div className="space-y-2 rounded-md border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div className="flex items-center justify-between">
                            <h4 className="font-semibold text-gray-900 dark:text-gray-100">Bahan Baku yang Digunakan ({data.bahan_baku.length})</h4>
                        </div>
                        <div className="space-y-2">
                            {data.bahan_baku.map((item, index) => (
                                <div key={index} className="flex items-center justify-between rounded-md bg-white p-3 shadow-sm dark:bg-gray-800">
                                    <span className="flex-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {getBahanBakuName(item.bahan_baku_id)}{' '}
                                        <span className="ml-2 text-gray-500">- {item.jumlah_bahan_baku} unit</span>
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => removeBahanBaku(index)}
                                        className={`${buttonVariants.destructive} rounded p-1.5`}
                                    >
                                        <TrashIcon className="h-4 w-4" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {errors.bahan_baku && <p className="text-sm text-red-600 dark:text-red-400">{errors.bahan_baku}</p>}
            </div>
        </FormTemplate>
    );
}
