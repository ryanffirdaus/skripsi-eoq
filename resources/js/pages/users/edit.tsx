import { FormField, Select, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import React from 'react';

interface Role {
    role_id: string;
    name: string;
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
        title: 'Users',
        href: '/users',
    },
    {
        title: 'Edit User',
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
        label: role.name,
    }));

    const passwordSection = {
        title: 'Change Password',
        children: (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="password" label="New Password" error={errors.password}>
                    <TextInput
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="Enter new password"
                        error={errors.password}
                    />
                    <span className="text-xs text-gray-500 dark:text-gray-400">(leave blank to keep current)</span>
                </FormField>

                <FormField id="password_confirmation" label="Confirm Password" error={errors.password_confirmation}>
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        placeholder="Confirm new password"
                        error={errors.password_confirmation}
                    />
                </FormField>
            </div>
        ),
    };

    return (
        <FormTemplate
            title={`Edit User: ${user.nama_lengkap}`}
            breadcrumbs={breadcrumbs}
            backUrl="/users"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Simpan"
            processingText="Updating..."
            sections={[passwordSection]}
        >
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="nama_lengkap" label="Nama Lengkap" error={errors.nama_lengkap} required>
                    <TextInput
                        id="nama_lengkap"
                        type="text"
                        value={data.nama_lengkap}
                        onChange={(e) => setData('nama_lengkap', e.target.value)}
                        placeholder="Enter full name"
                        error={errors.nama_lengkap}
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

                <FormField id="role_id" label="Role" error={errors.role_id} required>
                    <Select
                        id="role_id"
                        value={data.role_id}
                        onChange={(e) => setData('role_id', e.target.value)}
                        options={roleOptions}
                        placeholder="Select role"
                        error={errors.role_id}
                    />
                </FormField>
            </div>
        </FormTemplate>
    );
}
