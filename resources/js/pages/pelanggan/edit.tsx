// Edit.tsx - Pelanggan
import { FormField, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Pelanggan {
    pelanggan_id: string;
    nama_pelanggan: string;
    email_pelanggan: string;
    nomor_telepon: string;
    alamat_pembayaran: string;
    alamat_pengiriman: string;
}

interface Props {
    pelanggan: Pelanggan;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pelanggan',
        href: '/pelanggan',
    },
    {
        title: 'Ubah Pelanggan',
        href: '#',
    },
];

export default function Edit({ pelanggan }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        nama_pelanggan: pelanggan.nama_pelanggan,
        email_pelanggan: pelanggan.email_pelanggan,
        nomor_telepon: pelanggan.nomor_telepon,
        alamat_pembayaran: pelanggan.alamat_pembayaran,
        alamat_pengiriman: pelanggan.alamat_pengiriman,
    });

    const [sameAsPayment, setSameAsPayment] = useState<boolean>(
        pelanggan.alamat_pengiriman === pelanggan.alamat_pembayaran || !pelanggan.alamat_pengiriman,
    );

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        // If "same as payment address" is checked, clear shipping address
        if (sameAsPayment) {
            setData('alamat_pengiriman', '');
        }

        put(`/pelanggan/${pelanggan.pelanggan_id}`);
    };

    const handleSameAsPaymentChange = (checked: boolean) => {
        setSameAsPayment(checked);
        if (checked) {
            setData('alamat_pengiriman', '');
        } else if (data.alamat_pengiriman === '') {
            // If unchecking and shipping address is empty, copy from billing address
            setData('alamat_pengiriman', data.alamat_pembayaran);
        }
    };

    return (
        <FormTemplate
            title={`Ubah Pelanggan: ${pelanggan.nama_pelanggan}`}
            breadcrumbs={breadcrumbs}
            backUrl="/pelanggan"
            onSubmit={handleSubmit}
            processing={processing}
        >
            {/* Basic Information */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Pelanggan</h3>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField id="nama_pelanggan" label="Nama" error={errors.nama_pelanggan} required>
                        <TextInput
                            id="nama_pelanggan"
                            value={data.nama_pelanggan}
                            onChange={(e) => setData('nama_pelanggan', e.target.value)}
                            placeholder="Masukkan nama pelanggan"
                            error={errors.nama_pelanggan}
                        />
                    </FormField>

                    <FormField id="email_pelanggan" label="Email" error={errors.email_pelanggan} required>
                        <TextInput
                            id="email_pelanggan"
                            type="email"
                            value={data.email_pelanggan}
                            onChange={(e) => setData('email_pelanggan', e.target.value)}
                            placeholder="Masukkan alamat email"
                            error={errors.email_pelanggan}
                        />
                    </FormField>

                    <FormField id="nomor_telepon" label="Nomor Telepon" error={errors.nomor_telepon} required>
                        <TextInput
                            id="nomor_telepon"
                            value={data.nomor_telepon}
                            onChange={(e) => setData('nomor_telepon', e.target.value)}
                            placeholder="Masukkan nomor telepon"
                            error={errors.nomor_telepon}
                        />
                    </FormField>
                </div>
            </div>

            {/* Address Information */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Alamat</h3>

                <div className="space-y-4">
                    <FormField id="alamat_pembayaran" label="Alamat Pembayaran" error={errors.alamat_pembayaran} required>
                        <TextArea
                            id="alamat_pembayaran"
                            value={data.alamat_pembayaran}
                            onChange={(e) => setData('alamat_pembayaran', e.target.value)}
                            placeholder="Masukkan alamat pembayaran"
                            rows={3}
                            error={errors.alamat_pembayaran}
                        />
                    </FormField>

                    {/* Same as payment address checkbox */}
                    <div className="flex items-center space-x-2">
                        <input
                            type="checkbox"
                            id="same_as_payment"
                            checked={sameAsPayment}
                            onChange={(e) => handleSameAsPaymentChange(e.target.checked)}
                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                        />
                        <label htmlFor="same_as_payment" className="text-sm text-gray-700 dark:text-gray-300">
                            Gunakan alamat pembayaran sebagai alamat pengiriman
                        </label>
                    </div>

                    {!sameAsPayment && (
                        <FormField id="alamat_pengiriman" label="Alamat Pengiriman" error={errors.alamat_pengiriman}>
                            <TextArea
                                id="alamat_pengiriman"
                                value={data.alamat_pengiriman}
                                onChange={(e) => setData('alamat_pengiriman', e.target.value)}
                                placeholder="Masukkan alamat pengiriman"
                                rows={3}
                                error={errors.alamat_pengiriman}
                            />
                        </FormField>
                    )}
                </div>
            </div>
        </FormTemplate>
    );
}
