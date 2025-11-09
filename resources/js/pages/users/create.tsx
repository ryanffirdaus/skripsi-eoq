import { FormField, Select, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import React from 'react';

interface Role {
    role_id: number;
    nama: string;
}

interface CreateProps {
    roles: Role[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengguna',
        href: '/users',
    },
    {
        title: 'Tambah Pengguna',
        href: '#',
    },
];

export default function Create({ roles }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        nama_lengkap: '',
        email: '',
        password: '',
        password_confirmation: '',
        role_id: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/users');
    }

    const roleOptions = roles.map((role) => ({
        value: role.role_id.toString(),
        label: role.nama,
    }));

    return (
        <FormTemplate title="Tambah Pengguna" breadcrumbs={breadcrumbs} backUrl="/users" onSubmit={handleSubmit} processing={processing}>
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
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="password" label="Password" error={errors.password} required>
                    <TextInput
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="Masukkan kata sandi"
                        error={errors.password}
                    />
                </FormField>

                <FormField id="password_confirmation" label="Konfirmasi Password" error={errors.password_confirmation} required>
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        placeholder="Masukkan konfirmasi kata sandi"
                        error={errors.password_confirmation}
                    />
                </FormField>
            </div>
        </FormTemplate>
    );
}
