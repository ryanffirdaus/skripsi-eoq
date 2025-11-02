import { FormField, Select, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import React from 'react';

interface PesananDetail {
    pesanan_detail_id: string;
    produk_id: string;
    produk_nama: string;
    jumlah_produk: number;
    stok_produk: number;
    stok_cukup: boolean;
    harga_satuan: number;
    subtotal: number;
}

interface Pesanan {
    pesanan_id: string;
    pelanggan_id: string;
    total_harga: number;
    tanggal_pemesanan: string;
    all_stock_sufficient: boolean;
    detail: PesananDetail[];
    pelanggan: {
        nama_pelanggan: string;
        alamat_pengiriman: string;
        nomor_telepon: string;
    };
}

interface Props {
    pesanan: Pesanan[];
}

const kurirOptions = [
    { value: 'JNE', label: 'JNE' },
    { value: 'J&T', label: 'J&T' },
    { value: 'TIKI', label: 'TIKI' },
    { value: 'POS Indonesia', label: 'POS Indonesia' },
    { value: 'SiCepat', label: 'SiCepat' },
    { value: 'AnterAja', label: 'AnterAja' },
    { value: 'Gojek', label: 'Gojek' },
];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengiriman',
        href: '/pengiriman',
    },
    {
        title: 'Tambah Pengiriman',
        href: '#',
    },
];

export default function Create({ pesanan }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        pesanan_id: '',
        nomor_resi: '',
        kurir: '',
        biaya_pengiriman: '',
        estimasi_hari: '1',
        catatan: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/pengiriman');
    }

    const pesananOptions = pesanan
        .filter((p) => p.all_stock_sufficient)
        .map((p) => ({
            value: p.pesanan_id,
            label: `${p.pesanan_id} - ${p.pelanggan.nama_pelanggan} (Rp ${p.total_harga.toLocaleString()})`,
        }));

    const selectedPesanan = pesanan.find((p) => p.pesanan_id === data.pesanan_id);

    return (
        <FormTemplate
            title="Tambah Pengiriman"
            breadcrumbs={breadcrumbs}
            backUrl="/pengiriman"
            onSubmit={handleSubmit}
            processing={processing}
            processingText="Membuat..."
        >
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div className="sm:col-span-2">
                    <FormField id="pesanan_id" label="Pesanan" error={errors.pesanan_id} required>
                        <Select
                            id="pesanan_id"
                            value={data.pesanan_id}
                            onChange={(e) => setData('pesanan_id', e.target.value)}
                            options={pesananOptions}
                            placeholder="Pilih pesanan (hanya yang stok cukup)"
                            error={errors.pesanan_id}
                        />
                    </FormField>

                    {selectedPesanan && (
                        <div className="mt-4 space-y-4">
                            {/* Detail Pesanan */}
                            <div className="rounded-lg border bg-muted/50 p-4">
                                <h4 className="mb-3 font-semibold">Detail Pesanan</h4>
                                <div className="space-y-1 text-sm">
                                    <p>
                                        <span className="font-medium">Pesanan:</span> {selectedPesanan.pesanan_id}
                                    </p>
                                    <p>
                                        <span className="font-medium">Pelanggan:</span> {selectedPesanan.pelanggan.nama_pelanggan}
                                    </p>
                                    <p>
                                        <span className="font-medium">Total:</span> Rp {selectedPesanan.total_harga.toLocaleString()}
                                    </p>
                                    <p>
                                        <span className="font-medium">Alamat:</span> {selectedPesanan.pelanggan.alamat_pengiriman}
                                    </p>
                                    <p>
                                        <span className="font-medium">Telepon:</span> {selectedPesanan.pelanggan.nomor_telepon}
                                    </p>
                                </div>
                            </div>

                            {/* Detail Produk dengan Stok */}
                            <div className="space-y-2">
                                <h4 className="font-semibold">Produk yang Akan Dikirim</h4>
                                <div className="space-y-2">
                                    {selectedPesanan.detail.map((detail, idx) => (
                                        <div
                                            key={idx}
                                            className="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800"
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <p className="font-medium text-gray-900 dark:text-gray-100">{detail.produk_nama}</p>
                                                    <p className="text-sm text-gray-500 dark:text-gray-400">Pesanan: {detail.jumlah_produk} unit</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm font-medium">Stok: {detail.stok_produk} unit</p>
                                                    {detail.stok_cukup ? (
                                                        <p className="text-sm text-green-600 dark:text-green-400">✓ Stok cukup</p>
                                                    ) : (
                                                        <p className="text-sm text-red-600 dark:text-red-400">✗ Stok kurang</p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Peringatan stok tidak cukup */}
                    {Object.keys(errors).includes('stock') && errors['stock' as keyof typeof errors] && (
                        <div className="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-900 dark:bg-red-950">
                            <p className="text-sm font-medium text-red-800 dark:text-red-200">{errors['stock' as keyof typeof errors]}</p>
                        </div>
                    )}

                    {/* Info pesanan tanpa stok cukup */}
                    {pesanan.some((p) => !p.all_stock_sufficient) && pesananOptions.length === 0 && (
                        <div className="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 p-3 dark:border-yellow-900 dark:bg-yellow-950">
                            <p className="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                Semua pesanan tidak dapat dikirim karena stok produk tidak cukup.
                            </p>
                        </div>
                    )}
                </div>

                <FormField id="kurir" label="Kurir" error={errors.kurir} required>
                    <Select
                        id="kurir"
                        value={data.kurir}
                        onChange={(e) => setData('kurir', e.target.value)}
                        options={kurirOptions}
                        placeholder="Pilih kurir"
                        error={errors.kurir}
                    />
                </FormField>

                <FormField id="nomor_resi" label="Nomor Resi" error={errors.nomor_resi}>
                    <TextInput
                        id="nomor_resi"
                        type="text"
                        value={data.nomor_resi}
                        onChange={(e) => setData('nomor_resi', e.target.value)}
                        placeholder="Masukkan nomor resi (opsional)"
                        error={errors.nomor_resi}
                    />
                </FormField>

                <FormField id="biaya_pengiriman" label="Biaya Pengiriman" error={errors.biaya_pengiriman} required>
                    <TextInput
                        id="biaya_pengiriman"
                        type="number"
                        value={data.biaya_pengiriman}
                        onChange={(e) => setData('biaya_pengiriman', e.target.value)}
                        placeholder="0"
                        error={errors.biaya_pengiriman}
                    />
                </FormField>

                <FormField id="estimasi_hari" label="Estimasi (Hari)" error={errors.estimasi_hari} required>
                    <TextInput
                        id="estimasi_hari"
                        type="number"
                        value={data.estimasi_hari}
                        onChange={(e) => setData('estimasi_hari', e.target.value)}
                        placeholder="1"
                        min="1"
                        error={errors.estimasi_hari}
                    />
                </FormField>

                <div className="sm:col-span-2">
                    <FormField id="catatan" label="Catatan" error={errors.catatan}>
                        <TextArea
                            id="catatan"
                            value={data.catatan}
                            onChange={(e) => setData('catatan', e.target.value)}
                            placeholder="Catatan khusus untuk pengiriman (opsional)"
                            rows={3}
                            error={errors.catatan}
                        />
                    </FormField>
                </div>
            </div>
        </FormTemplate>
    );
}
