// Edit.tsx - Produk
import { FormField, NumberInput, Select, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { buttonVariants } from '@/lib/colors';
import { type BreadcrumbItem } from '@/types';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import { useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';

interface BahanBaku {
    bahan_baku_id: string;
    nama_bahan: string;
    satuan_bahan: string;
}

interface BahanProduksi extends BahanBaku {
    jumlah_bahan_baku: number;
}

interface BahanBakuInput {
    bahan_baku_id: string;
    jumlah_bahan_baku: number;
}

interface Produk {
    produk_id: string;
    nama_produk: string;
    lokasi_produk: string;
    stok_produk: number;
    satuan_produk: string;
    hpp_produk: number;
    harga_jual: number;
    permintaan_harian_rata2_produk: number;
    permintaan_harian_maksimum_produk: number;
    waktu_tunggu_rata2_produk: number;
    waktu_tunggu_maksimum_produk: number;
    permintaan_tahunan: number;
    biaya_pemesanan_produk: number;
    biaya_penyimpanan_produk: number;
}

interface Props {
    produk: Produk;
    bahanProduksi: BahanProduksi[];
    bahanBakus: BahanBaku[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Produk',
        href: '/produk',
    },
    {
        title: 'Edit',
        href: '#',
    },
];

export default function Edit({ produk, bahanProduksi, bahanBakus }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        nama_produk: produk.nama_produk,
        lokasi_produk: produk.lokasi_produk,
        stok_produk: produk.stok_produk,
        satuan_produk: produk.satuan_produk,
        hpp_produk: produk.hpp_produk,
        harga_jual: produk.harga_jual,
        permintaan_harian_rata2_produk: produk.permintaan_harian_rata2_produk,
        permintaan_harian_maksimum_produk: produk.permintaan_harian_maksimum_produk,
        waktu_tunggu_rata2_produk: produk.waktu_tunggu_rata2_produk,
        waktu_tunggu_maksimum_produk: produk.waktu_tunggu_maksimum_produk,
        permintaan_tahunan: produk.permintaan_tahunan,
        biaya_pemesanan_produk: produk.biaya_pemesanan_produk,
        biaya_penyimpanan_produk: produk.biaya_penyimpanan_produk,
        bahan_baku: [] as BahanBakuInput[],
    });

    const [selectedBahan, setSelectedBahan] = useState<string>('');
    const [jumlahBahan, setJumlahBahan] = useState<number>(0);

    // Load existing bahan baku on component mount
    useEffect(() => {
        const initialBahanBaku = bahanProduksi.map((item) => ({
            bahan_baku_id: item.bahan_baku_id,
            jumlah_bahan_baku: item.jumlah_bahan_baku,
        }));
        setData('bahan_baku', initialBahanBaku);
    }, [bahanProduksi, setData]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        put(`/produk/${produk.produk_id}`);
    };

    const addBahanBaku = () => {
        if (selectedBahan && jumlahBahan > 0) {
            const exists = data.bahan_baku.some((item) => item.bahan_baku_id === selectedBahan);
            if (!exists) {
                setData('bahan_baku', [...data.bahan_baku, { bahan_baku_id: selectedBahan, jumlah_bahan_baku: jumlahBahan }]);
                setSelectedBahan('');
                setJumlahBahan(0);
            }
        }
    };

    const removeBahanBaku = (index: number) => {
        const newBahanBaku = data.bahan_baku.filter((_, i) => i !== index);
        setData('bahan_baku', newBahanBaku);
    };

    const updateJumlahBahan = (index: number, newJumlah: number) => {
        const newBahanBaku = [...data.bahan_baku];
        newBahanBaku[index].jumlah_bahan_baku = newJumlah;
        setData('bahan_baku', newBahanBaku);
    };

    const getBahanBakuName = (id: string) => {
        const bahan = bahanBakus.find((b) => b.bahan_baku_id === id);
        return bahan ? `${bahan.nama_bahan} (${bahan.satuan_bahan})` : '';
    };

    return (
        <FormTemplate
            title={`Edit Produk: ${produk.nama_produk}`}
            breadcrumbs={breadcrumbs}
            backUrl="/produk"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Update Produk"
        >
            {/* Basic Information */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Basic Information</h3>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField id="nama_produk" label="Nama Produk" error={errors.nama_produk} required>
                        <TextInput
                            id="nama_produk"
                            value={data.nama_produk}
                            onChange={(e) => setData('nama_produk', e.target.value)}
                            placeholder="Enter product name"
                            error={errors.nama_produk}
                        />
                    </FormField>

                    <FormField id="lokasi_produk" label="Lokasi Produk" error={errors.lokasi_produk} required>
                        <TextInput
                            id="lokasi_produk"
                            value={data.lokasi_produk}
                            onChange={(e) => setData('lokasi_produk', e.target.value)}
                            placeholder="Enter product location"
                            error={errors.lokasi_produk}
                        />
                    </FormField>

                    <FormField id="stok_produk" label="Stok Produk" error={errors.stok_produk}>
                        <NumberInput
                            id="stok_produk"
                            value={data.stok_produk}
                            onChange={(e) => setData('stok_produk', parseFloat(e.target.value) || 0)}
                            placeholder="Enter current stock"
                            min={0}
                            error={errors.stok_produk}
                        />
                    </FormField>

                    <FormField id="satuan_produk" label="Satuan Produk" error={errors.satuan_produk} required>
                        <TextInput
                            id="satuan_produk"
                            value={data.satuan_produk}
                            onChange={(e) => setData('satuan_produk', e.target.value)}
                            placeholder="e.g., pcs, kg, liter"
                            error={errors.satuan_produk}
                        />
                    </FormField>

                    <FormField id="hpp_produk" label="HPP Produk" error={errors.hpp_produk} required>
                        <NumberInput
                            id="hpp_produk"
                            value={data.hpp_produk}
                            onChange={(e) => setData('hpp_produk', parseFloat(e.target.value) || 0)}
                            placeholder="Enter cost of goods sold"
                            min={0}
                            step="0.01"
                            error={errors.hpp_produk}
                        />
                    </FormField>

                    <FormField id="harga_jual" label="Harga Jual" error={errors.harga_jual} required>
                        <NumberInput
                            id="harga_jual"
                            value={data.harga_jual}
                            onChange={(e) => setData('harga_jual', parseFloat(e.target.value) || 0)}
                            placeholder="Enter selling price"
                            min={0}
                            step="0.01"
                            error={errors.harga_jual}
                        />
                    </FormField>
                </div>
            </div>

            {/* Demand Information */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Demand Information</h3>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField
                        id="permintaan_harian_rata2_produk"
                        label="Permintaan Harian Rata-rata"
                        error={errors.permintaan_harian_rata2_produk}
                        required
                    >
                        <NumberInput
                            id="permintaan_harian_rata2_produk"
                            value={data.permintaan_harian_rata2_produk}
                            onChange={(e) => setData('permintaan_harian_rata2_produk', parseFloat(e.target.value) || 0)}
                            placeholder="Average daily demand"
                            min={0}
                            error={errors.permintaan_harian_rata2_produk}
                        />
                    </FormField>

                    <FormField
                        id="permintaan_harian_maksimum_produk"
                        label="Permintaan Harian Maksimum"
                        error={errors.permintaan_harian_maksimum_produk}
                        required
                    >
                        <NumberInput
                            id="permintaan_harian_maksimum_produk"
                            value={data.permintaan_harian_maksimum_produk}
                            onChange={(e) => setData('permintaan_harian_maksimum_produk', parseFloat(e.target.value) || 0)}
                            placeholder="Maximum daily demand"
                            min={0}
                            error={errors.permintaan_harian_maksimum_produk}
                        />
                    </FormField>

                    <FormField id="waktu_tunggu_rata2_produk" label="Waktu Tunggu Rata-rata (hari)" error={errors.waktu_tunggu_rata2_produk} required>
                        <NumberInput
                            id="waktu_tunggu_rata2_produk"
                            value={data.waktu_tunggu_rata2_produk}
                            onChange={(e) => setData('waktu_tunggu_rata2_produk', parseFloat(e.target.value) || 0)}
                            placeholder="Average lead time"
                            min={0}
                            error={errors.waktu_tunggu_rata2_produk}
                        />
                    </FormField>

                    <FormField
                        id="waktu_tunggu_maksimum_produk"
                        label="Waktu Tunggu Maksimum (hari)"
                        error={errors.waktu_tunggu_maksimum_produk}
                        required
                    >
                        <NumberInput
                            id="waktu_tunggu_maksimum_produk"
                            value={data.waktu_tunggu_maksimum_produk}
                            onChange={(e) => setData('waktu_tunggu_maksimum_produk', parseFloat(e.target.value) || 0)}
                            placeholder="Maximum lead time"
                            min={0}
                            error={errors.waktu_tunggu_maksimum_produk}
                        />
                    </FormField>

                    <FormField id="permintaan_tahunan" label="Permintaan Tahunan" error={errors.permintaan_tahunan} required>
                        <NumberInput
                            id="permintaan_tahunan"
                            value={data.permintaan_tahunan}
                            onChange={(e) => setData('permintaan_tahunan', parseFloat(e.target.value) || 0)}
                            placeholder="Annual demand"
                            min={0}
                            error={errors.permintaan_tahunan}
                        />
                    </FormField>
                </div>
            </div>

            {/* Cost Information */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Cost Information</h3>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField id="biaya_pemesanan_produk" label="Biaya Pemesanan" error={errors.biaya_pemesanan_produk} required>
                        <NumberInput
                            id="biaya_pemesanan_produk"
                            value={data.biaya_pemesanan_produk}
                            onChange={(e) => setData('biaya_pemesanan_produk', parseFloat(e.target.value) || 0)}
                            placeholder="Order cost"
                            min={0}
                            step="0.01"
                            error={errors.biaya_pemesanan_produk}
                        />
                    </FormField>

                    <FormField id="biaya_penyimpanan_produk" label="Biaya Penyimpanan" error={errors.biaya_penyimpanan_produk} required>
                        <NumberInput
                            id="biaya_penyimpanan_produk"
                            value={data.biaya_penyimpanan_produk}
                            onChange={(e) => setData('biaya_penyimpanan_produk', parseFloat(e.target.value) || 0)}
                            placeholder="Holding cost"
                            min={0}
                            step="0.01"
                            error={errors.biaya_penyimpanan_produk}
                        />
                    </FormField>
                </div>
            </div>

            {/* Bahan Baku */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Bahan Baku</h3>

                {/* Add Bahan Baku */}
                <div className="grid grid-cols-1 items-end gap-4 md:grid-cols-3">
                    <FormField id="select_bahan" label="Pilih Bahan Baku">
                        <Select
                            id="select_bahan"
                            value={selectedBahan}
                            onChange={(e) => setSelectedBahan(e.target.value)}
                            options={[
                                { value: '', label: 'Select material...' },
                                ...bahanBakus
                                    .filter((bahan) => !data.bahan_baku.some((item) => item.bahan_baku_id === bahan.bahan_baku_id))
                                    .map((bahan) => ({
                                        value: bahan.bahan_baku_id,
                                        label: `${bahan.nama_bahan} (${bahan.satuan_bahan})`,
                                    })),
                            ]}
                        />
                    </FormField>

                    <FormField id="jumlah_bahan" label="Jumlah Diperlukan">
                        <NumberInput
                            id="jumlah_bahan"
                            value={jumlahBahan}
                            onChange={(e) => setJumlahBahan(parseFloat(e.target.value) || 0)}
                            placeholder="Quantity needed"
                            min={0}
                            step={0.01}
                        />
                    </FormField>

                    <button
                        type="button"
                        onClick={addBahanBaku}
                        className={`${buttonVariants.primary} flex items-center space-x-2 rounded-md px-4 py-2`}
                    >
                        <PlusIcon className="h-4 w-4" />
                        <span>Add</span>
                    </button>
                </div>

                {/* List Bahan Baku */}
                {data.bahan_baku.length > 0 && (
                    <div className="space-y-2">
                        <h4 className="font-medium text-gray-900 dark:text-gray-100">Materials Used:</h4>
                        {data.bahan_baku.map((item, index) => (
                            <div
                                key={index}
                                className="grid grid-cols-1 items-center gap-4 rounded-md bg-gray-50 p-3 md:grid-cols-3 dark:bg-gray-800"
                            >
                                <span className="text-sm text-gray-900 dark:text-gray-100">{getBahanBakuName(item.bahan_baku_id)}</span>
                                <NumberInput
                                    value={item.jumlah_bahan_baku}
                                    onChange={(e) => updateJumlahBahan(index, parseFloat(e.target.value) || 0)}
                                    min={0}
                                    step={0.01}
                                    placeholder="Quantity"
                                />
                                <button
                                    type="button"
                                    onClick={() => removeBahanBaku(index)}
                                    className={`${buttonVariants.destructive} w-fit rounded p-2`}
                                >
                                    <TrashIcon className="h-4 w-4" />
                                </button>
                            </div>
                        ))}
                    </div>
                )}

                {errors.bahan_baku && <p className="text-sm text-red-600 dark:text-red-400">{errors.bahan_baku}</p>}
            </div>
        </FormTemplate>
    );
}
