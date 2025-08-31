// Create.tsx - Pelanggan
import { FormField, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pelanggan',
        href: '/pelanggan',
    },
    {
        title: 'Create',
        href: '/pelanggan/create',
    },
];

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        nama_pelanggan: '',
        email_pelanggan: '',
        nomor_telepon: '',
        alamat_pembayaran: '',
        alamat_pengiriman: '',
    });

    const [sameAsPayment, setSameAsPayment] = useState<boolean>(true);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        // If "same as payment address" is checked, clear shipping address
        if (sameAsPayment) {
            setData('alamat_pengiriman', '');
        }

        post('/pelanggan');
    };

    const handleSameAsPaymentChange = (checked: boolean) => {
        setSameAsPayment(checked);
        if (checked) {
            setData('alamat_pengiriman', '');
        }
    };

    return (
        <FormTemplate
            title="Create New Pelanggan"
            breadcrumbs={breadcrumbs}
            backUrl="/pelanggan"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Create Pelanggan"
        >
            {/* Basic Information */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Customer Information</h3>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField id="nama_pelanggan" label="Nama Pelanggan" error={errors.nama_pelanggan} required>
                        <TextInput
                            id="nama_pelanggan"
                            value={data.nama_pelanggan}
                            onChange={(e) => setData('nama_pelanggan', e.target.value)}
                            placeholder="Enter customer name"
                            error={errors.nama_pelanggan}
                        />
                    </FormField>

                    <FormField id="email_pelanggan" label="Email Pelanggan" error={errors.email_pelanggan} required>
                        <TextInput
                            id="email_pelanggan"
                            type="email"
                            value={data.email_pelanggan}
                            onChange={(e) => setData('email_pelanggan', e.target.value)}
                            placeholder="Enter email address"
                            error={errors.email_pelanggan}
                        />
                    </FormField>

                    <FormField id="nomor_telepon" label="Nomor Telepon" error={errors.nomor_telepon} required>
                        <TextInput
                            id="nomor_telepon"
                            value={data.nomor_telepon}
                            onChange={(e) => setData('nomor_telepon', e.target.value)}
                            placeholder="Enter phone number"
                            error={errors.nomor_telepon}
                        />
                    </FormField>
                </div>
            </div>

            {/* Address Information */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Address Information</h3>

                <div className="space-y-4">
                    <FormField id="alamat_pembayaran" label="Alamat Pembayaran" error={errors.alamat_pembayaran} required>
                        <TextArea
                            id="alamat_pembayaran"
                            value={data.alamat_pembayaran}
                            onChange={(e) => setData('alamat_pembayaran', e.target.value)}
                            placeholder="Enter billing address"
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
                            Use billing address as shipping address
                        </label>
                    </div>

                    {!sameAsPayment && (
                        <FormField id="alamat_pengiriman" label="Alamat Pengiriman" error={errors.alamat_pengiriman}>
                            <TextArea
                                id="alamat_pengiriman"
                                value={data.alamat_pengiriman}
                                onChange={(e) => setData('alamat_pengiriman', e.target.value)}
                                placeholder="Enter shipping address"
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
