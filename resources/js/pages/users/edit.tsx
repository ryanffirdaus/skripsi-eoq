import { FormField, Select, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import React from 'react';

interface Role {
    role_id: string;
    nama: string;
}

interface User {
    user_id: string;
    nama_lengkap: string;
    email: string;
    role_id?: string;
}

interface Props {
    user: User;
    roles: Role[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengguna',
        href: '/users',
    },
    {
        title: 'Edit Pengguna',
        href: '#',
    },
];

export default function Edit({ user, roles }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        nama_lengkap: user.nama_lengkap || '',
        email: user.email || '',
        role_id: user.role_id || '',
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/users/${user.user_id}`);
    }

    const roleOptions = roles.map((role) => ({
        value: role.role_id,
        label: role.nama,
    }));

    return (
        <FormTemplate
            title={`Edit Pengguna: ${user.nama_lengkap}`}
            breadcrumbs={breadcrumbs}
            backUrl="/users"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Simpan"
            processingText="Memperbarui..."
        >
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="nama_lengkap" label="Nama Lengkap" error={errors.nama_lengkap} required>
                    <TextInput
                        id="nama_lengkap"
                        type="text"
                        value={data.nama_lengkap}
                        onChange={(e) => setData('nama_lengkap', e.target.value)}
                        placeholder="Masukkan nama lengkap"
                        error={errors.nama_lengkap}
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

                <FormField id="role_id" label="Role" error={errors.role_id} required>
                    <Select
                        id="role_id"
                        value={data.role_id}
                        onChange={(e) => setData('role_id', e.target.value)}
                        options={roleOptions}
                        placeholder="Pilih jenis peran"
                        error={errors.role_id}
                    />
                </FormField>
            </div>
        </FormTemplate>
    );
}
