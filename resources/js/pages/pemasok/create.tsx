import { FormField, Select, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pemasok',
        href: '/pemasok',
    },
    {
        title: 'Tambah',
        href: '/pemasok/create',
    },
];

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        nama_pemasok: '',
        narahubung: '',
        email: '',
        telepon: '',
        alamat: '',
        status: 'active', // Default status
        catatan: '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/pemasok');
    };

    return (
        <FormTemplate
            title="Tambah Pemasok Baru"
            breadcrumbs={breadcrumbs}
            backUrl="/pemasok"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Simpan Pemasok"
        >
            {/* Informasi Pemasok */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Pemasok</h3>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField id="nama_pemasok" label="Nama Pemasok" error={errors.nama_pemasok} required>
                        <TextInput
                            id="nama_pemasok"
                            value={data.nama_pemasok}
                            onChange={(e) => setData('nama_pemasok', e.target.value)}
                            placeholder="Masukkan nama pemasok"
                            error={errors.nama_pemasok}
                        />
                    </FormField>

                    <FormField id="narahubung" label="Kontak Person" error={errors.narahubung} required>
                        <TextInput
                            id="narahubung"
                            value={data.narahubung}
                            onChange={(e) => setData('narahubung', e.target.value)}
                            placeholder="Masukkan nama kontak person"
                            error={errors.narahubung}
                        />
                    </FormField>

                    <FormField id="email" label="Email" error={errors.email} required>
                        <TextInput
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="Masukkan alamat email"
                            error={errors.email}
                        />
                    </FormField>

                    <FormField id="telepon" label="Telepon" error={errors.telepon} required>
                        <TextInput
                            id="telepon"
                            value={data.telepon}
                            onChange={(e) => setData('telepon', e.target.value)}
                            placeholder="Masukkan nomor telepon"
                            error={errors.telepon}
                        />
                    </FormField>
                </div>
            </div>

            {/* Informasi Alamat */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Alamat</h3>

                <div className="space-y-4">
                    <FormField id="alamat" label="Alamat" error={errors.alamat} required>
                        <TextArea
                            id="alamat"
                            value={data.alamat}
                            onChange={(e) => setData('alamat', e.target.value)}
                            placeholder="Masukkan alamat lengkap"
                            rows={3}
                            error={errors.alamat}
                        />
                    </FormField>
                </div>
            </div>

            {/* Informasi Tambahan */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Tambahan</h3>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField id="status" label="Status" error={errors.status} required>
                        <Select
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            options={[
                                { value: 'active', label: 'Aktif' },
                                { value: 'inactive', label: 'Tidak Aktif' },
                            ]}
                            placeholder="Pilih status"
                            error={errors.status}
                        />
                    </FormField>

                    <FormField id="catatan" label="Catatan" error={errors.catatan}>
                        <TextArea
                            id="catatan"
                            value={data.catatan}
                            onChange={(e) => setData('catatan', e.target.value)}
                            placeholder="Masukkan catatan tambahan (opsional)"
                            rows={3}
                            error={errors.catatan}
                        />
                    </FormField>
                </div>
            </div>
        </FormTemplate>
    );
}
