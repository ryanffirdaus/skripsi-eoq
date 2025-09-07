import { FormField, Select, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

// Interface untuk data supplier
interface Supplier {
    supplier_id: string;
    nama_supplier: string;
    kontak_person: string;
    email: string;
    telepon: string;
    alamat: string;
    kota: string;
    provinsi: string;
    kode_pos: string;
    status: 'active' | 'inactive';
    catatan: string;
}

// Interface untuk props komponen
interface Props {
    supplier: Supplier;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Supplier',
        href: '/supplier',
    },
    {
        title: 'Edit',
        href: '#', // Halaman saat ini tidak perlu link
    },
];

export default function Edit({ supplier }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        nama_supplier: supplier.nama_supplier || '',
        kontak_person: supplier.kontak_person || '',
        email: supplier.email || '',
        telepon: supplier.telepon || '',
        alamat: supplier.alamat || '',
        kota: supplier.kota || '',
        provinsi: supplier.provinsi || '',
        kode_pos: supplier.kode_pos || '',
        status: supplier.status || 'active',
        catatan: supplier.catatan || '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        // Menggunakan metode PUT untuk update
        put(`/supplier/${supplier.supplier_id}`);
    };

    return (
        <FormTemplate
            title={`Edit Supplier: ${supplier.nama_supplier}`}
            breadcrumbs={breadcrumbs}
            backUrl="/supplier"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Update Supplier"
            processingText="Updating..."
        >
            {/* Supplier Information */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Supplier Information</h3>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField id="nama_supplier" label="Nama Supplier" error={errors.nama_supplier} required>
                        <TextInput
                            id="nama_supplier"
                            value={data.nama_supplier}
                            onChange={(e) => setData('nama_supplier', e.target.value)}
                            placeholder="Enter supplier name"
                            error={errors.nama_supplier}
                        />
                    </FormField>

                    <FormField id="kontak_person" label="Kontak Person" error={errors.kontak_person} required>
                        <TextInput
                            id="kontak_person"
                            value={data.kontak_person}
                            onChange={(e) => setData('kontak_person', e.target.value)}
                            placeholder="Enter contact person name"
                            error={errors.kontak_person}
                        />
                    </FormField>

                    <FormField id="email" label="Email" error={errors.email} required>
                        <TextInput
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="Enter email address"
                            error={errors.email}
                        />
                    </FormField>

                    <FormField id="telepon" label="Telepon" error={errors.telepon} required>
                        <TextInput
                            id="telepon"
                            value={data.telepon}
                            onChange={(e) => setData('telepon', e.target.value)}
                            placeholder="Enter phone number"
                            error={errors.telepon}
                        />
                    </FormField>
                </div>
            </div>

            {/* Address Information */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Address Information</h3>

                <div className="space-y-4">
                    <FormField id="alamat" label="Alamat" error={errors.alamat} required>
                        <TextArea
                            id="alamat"
                            value={data.alamat}
                            onChange={(e) => setData('alamat', e.target.value)}
                            placeholder="Enter full address"
                            rows={3}
                            error={errors.alamat}
                        />
                    </FormField>
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <FormField id="kota" label="Kota" error={errors.kota} required>
                            <TextInput
                                id="kota"
                                value={data.kota}
                                onChange={(e) => setData('kota', e.target.value)}
                                placeholder="Enter city"
                                error={errors.kota}
                            />
                        </FormField>
                        <FormField id="provinsi" label="Provinsi" error={errors.provinsi} required>
                            <TextInput
                                id="provinsi"
                                value={data.provinsi}
                                onChange={(e) => setData('provinsi', e.target.value)}
                                placeholder="Enter province"
                                error={errors.provinsi}
                            />
                        </FormField>
                        <FormField id="kode_pos" label="Kode Pos" error={errors.kode_pos} required>
                            <TextInput
                                id="kode_pos"
                                value={data.kode_pos}
                                onChange={(e) => setData('kode_pos', e.target.value)}
                                placeholder="Enter postal code"
                                error={errors.kode_pos}
                            />
                        </FormField>
                    </div>
                </div>
            </div>

            {/* Additional Information */}
            <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Additional Information</h3>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField id="status" label="Status" error={errors.status} required>
                        <Select
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value as 'active' | 'inactive')}
                            options={[
                                { value: 'active', label: 'Active' },
                                { value: 'inactive', label: 'Inactive' },
                            ]}
                            placeholder="Select status"
                            error={errors.status}
                        />
                    </FormField>

                    <FormField id="catatan" label="Catatan" error={errors.catatan}>
                        <TextArea
                            id="catatan"
                            value={data.catatan}
                            onChange={(e) => setData('catatan', e.target.value)}
                            placeholder="Enter additional notes"
                            rows={3}
                            error={errors.catatan}
                        />
                    </FormField>
                </div>
            </div>
        </FormTemplate>
    );
}
