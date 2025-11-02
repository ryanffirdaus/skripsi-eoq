import { FormField, Select, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Pengiriman {
    pengiriman_id: string;
    pesanan_id: string;
    nomor_resi?: string;
    kurir: string;
    biaya_pengiriman: number;
    estimasi_hari: number;
    status: string;
    catatan?: string;
    tanggal_kirim?: string | null;
    tanggal_diterima?: string | null;
}

interface Props {
    pengiriman: Pengiriman;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengiriman',
        href: '/pengiriman',
    },
    {
        title: 'Edit',
        href: '#',
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

const statusOptions = [
    { value: 'pending', label: 'Menunggu Dikirim' },
    { value: 'dikirim', label: 'Dikirim' },
    { value: 'selesai', label: 'Selesai' },
    { value: 'dibatalkan', label: 'Dibatalkan' },
];

export default function Edit({ pengiriman }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        nomor_resi: pengiriman.nomor_resi || '',
        kurir: pengiriman.kurir,
        biaya_pengiriman: pengiriman.biaya_pengiriman.toString(),
        estimasi_hari: pengiriman.estimasi_hari.toString(),
        status: pengiriman.status,
        tanggal_kirim: pengiriman.tanggal_kirim ?? '',
        tanggal_diterima: pengiriman.tanggal_diterima ?? '',
        catatan: pengiriman.catatan || '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        put(`/pengiriman/${pengiriman.pengiriman_id}`);
    };

    return (
        <FormTemplate
            title={`Edit Pengiriman: ${pengiriman.pengiriman_id}`}
            breadcrumbs={breadcrumbs}
            backUrl="/pengiriman"
            onSubmit={handleSubmit}
            processing={processing}
        >
            {/* Informasi Pengiriman */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Pengiriman</h3>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField id="kurir" label="Kurir" error={errors.kurir} required>
                        <Select
                            id="kurir"
                            value={data.kurir}
                            onChange={(e) => setData('kurir', e.target.value)}
                            options={kurirOptions}
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
                            step="0.01"
                            min="0"
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
                            min="1"
                            value={data.estimasi_hari}
                            onChange={(e) => setData('estimasi_hari', e.target.value)}
                            placeholder="1"
                            error={errors.estimasi_hari}
                        />
                    </FormField>

                    <FormField id="status" label="Status" error={errors.status} required>
                        <Select
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            options={statusOptions}
                            error={errors.status}
                        />
                    </FormField>

                    {data.status === 'dikirim' && (
                        <FormField id="tanggal_kirim" label="Tanggal Kirim" error={errors.tanggal_kirim}>
                            <TextInput
                                id="tanggal_kirim"
                                type="date"
                                value={data.tanggal_kirim}
                                onChange={(e) => setData('tanggal_kirim', e.target.value)}
                                error={errors.tanggal_kirim}
                            />
                        </FormField>
                    )}

                    {data.status === 'selesai' && (
                        <FormField id="tanggal_diterima" label="Tanggal Diterima" error={errors.tanggal_diterima}>
                            <TextInput
                                id="tanggal_diterima"
                                type="date"
                                value={data.tanggal_diterima}
                                onChange={(e) => setData('tanggal_diterima', e.target.value)}
                                error={errors.tanggal_diterima}
                            />
                        </FormField>
                    )}
                </div>

                <FormField id="catatan" label="Catatan" error={errors.catatan}>
                    <TextArea
                        id="catatan"
                        value={data.catatan}
                        onChange={(e) => setData('catatan', e.target.value)}
                        placeholder="Catatan tambahan tentang pengiriman (opsional)"
                        rows={4}
                        error={errors.catatan}
                    />
                </FormField>
            </div>
        </FormTemplate>
    );
}
