import { FormField, Select, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import React from 'react';

interface Pesanan {
    pesanan_id: string;
    pelanggan_id: string;
    total_harga: number;
    pelanggan: {
        nama_pelanggan: string;
        alamat_pelanggan: string;
        kota_pelanggan: string;
        telepon_pelanggan: string;
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
        title: 'Buat Pengiriman',
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

    const pesananOptions = pesanan.map((p) => ({
        value: p.pesanan_id,
        label: `${p.pesanan_id} - ${p.pelanggan.nama_pelanggan} (Rp ${p.total_harga.toLocaleString()})`,
    }));

    const selectedPesanan = pesanan.find((p) => p.pesanan_id === data.pesanan_id);

    return (
        <FormTemplate
            title="Buat Pengiriman Baru"
            breadcrumbs={breadcrumbs}
            backUrl="/pengiriman"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Buat Pengiriman"
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
                            placeholder="Pilih pesanan"
                            error={errors.pesanan_id}
                        />
                    </FormField>

                    {selectedPesanan && (
                        <div className="mt-4 rounded-lg border bg-muted/50 p-4">
                            <h4 className="mb-2 font-medium">Detail Pesanan</h4>
                            <div className="space-y-1 text-sm">
                                <p>
                                    <span className="font-medium">Pelanggan:</span> {selectedPesanan.pelanggan.nama_pelanggan}
                                </p>
                                <p>
                                    <span className="font-medium">Total:</span> Rp {selectedPesanan.total_harga.toLocaleString()}
                                </p>
                                <p>
                                    <span className="font-medium">Alamat:</span> {selectedPesanan.pelanggan.alamat_pelanggan},{' '}
                                    {selectedPesanan.pelanggan.kota_pelanggan}
                                </p>
                                <p>
                                    <span className="font-medium">Telepon:</span> {selectedPesanan.pelanggan.telepon_pelanggan}
                                </p>
                            </div>
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
