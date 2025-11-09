// Edit.tsx - Pelanggan
import { FormField, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

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
        title: 'Edit Pelanggan',
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

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        put(`/pelanggan/${pelanggan.pelanggan_id}`);
    };

    return (
        <FormTemplate
            title={`Edit Pelanggan: ${pelanggan.nama_pelanggan}`}
            breadcrumbs={breadcrumbs}
            backUrl="/pelanggan"
            onSubmit={handleSubmit}
            processing={processing}
        >
            {/* Basic Information */}
            <div className="space-y-6">
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
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

                    <FormField id="alamat_pengiriman" label="Alamat Pengiriman" error={errors.alamat_pengiriman} required>
                        <TextArea
                            id="alamat_pengiriman"
                            value={data.alamat_pengiriman}
                            onChange={(e) => setData('alamat_pengiriman', e.target.value)}
                            placeholder="Masukkan alamat pengiriman"
                            rows={3}
                            error={errors.alamat_pengiriman}
                        />
                    </FormField>
                </div>
            </div>
        </FormTemplate>
    );
}
