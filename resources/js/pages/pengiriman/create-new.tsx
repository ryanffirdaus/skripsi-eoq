import { FormField, Select, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Pesanan {
    pesanan_id: string;
    pelanggan_id: string;
    total_harga: number;
    pelanggan: {
        nama_pelanggan: string;
        alamat_pembayaran: string;
        nomor_telepon: string;
    };
}

interface Props {
    pesanan: Pesanan[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengiriman',
        href: '/pengiriman',
    },
    {
        title: 'Buat Pengiriman',
        href: '/pengiriman/create',
    },
];

const kurirOptions = [
    { value: 'JNE', label: 'JNE' },
    { value: 'J&T', label: 'J&T' },
    { value: 'TIKI', label: 'TIKI' },
    { value: 'POS Indonesia', label: 'POS Indonesia' },
    { value: 'SiCepat', label: 'SiCepat' },
    { value: 'AnterAja', label: 'AnterAja' },
    { value: 'Gojek', label: 'Gojek' },
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

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/pengiriman');
    };

    const selectedPesanan = pesanan.find((p) => p.pesanan_id === data.pesanan_id);

    return (
        <FormTemplate title="Buat Pengiriman" breadcrumbs={breadcrumbs} backUrl="/pengiriman" onSubmit={handleSubmit} processing={processing}>
            {/* Informasi Pesanan */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Pesanan</h3>

                <div className="space-y-4">
                    <FormField id="pesanan_id" label="Pesanan" error={errors.pesanan_id} required>
                        <Select
                            id="pesanan_id"
                            value={data.pesanan_id}
                            onChange={(e) => setData('pesanan_id', e.target.value)}
                            options={[
                                { value: '', label: 'Pilih pesanan' },
                                ...pesanan.map((p) => ({
                                    value: p.pesanan_id,
                                    label: `${p.pesanan_id} - ${p.pelanggan.nama_pelanggan} - Rp ${p.total_harga.toLocaleString()}`,
                                })),
                            ]}
                            error={errors.pesanan_id}
                        />
                    </FormField>

                    {selectedPesanan && (
                        <div className="rounded-lg border bg-muted/50 p-4 dark:bg-gray-800">
                            <h4 className="mb-2 font-medium text-gray-900 dark:text-gray-100">Detail Pesanan</h4>
                            <div className="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                <p>
                                    <span className="font-medium">Pelanggan:</span> {selectedPesanan.pelanggan.nama_pelanggan}
                                </p>
                                <p>
                                    <span className="font-medium">Total:</span> Rp {selectedPesanan.total_harga.toLocaleString()}
                                </p>
                                <p>
                                    <span className="font-medium">Alamat:</span> {selectedPesanan.pelanggan.alamat_pembayaran}
                                </p>
                                <p>
                                    <span className="font-medium">Telepon:</span> {selectedPesanan.pelanggan.nomor_telepon}
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Informasi Pengiriman */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Pengiriman</h3>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField id="kurir" label="Kurir" error={errors.kurir} required>
                        <Select
                            id="kurir"
                            value={data.kurir}
                            onChange={(e) => setData('kurir', e.target.value)}
                            options={[{ value: '', label: 'Pilih kurir' }, ...kurirOptions]}
                            error={errors.kurir}
                        />
                    </FormField>

                    <FormField id="nomor_resi" label="Nomor Resi" error={errors.nomor_resi}>
                        <TextInput
                            id="nomor_resi"
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
                </div>

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
        </FormTemplate>
    );
}
