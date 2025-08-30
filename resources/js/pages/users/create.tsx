import { FormField, Select, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import React from 'react';

interface Role {
    role_id: number;
    name: string;
}

interface CreateProps {
    roles: Role[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
    {
        title: 'Create User',
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
        label: role.name,
    }));

    const passwordSection = {
        title: 'Password',
        children: (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="password" label="Password" error={errors.password} required>
                    <TextInput
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="Enter password"
                        error={errors.password}
                    />
                </FormField>

                <FormField id="password_confirmation" label="Confirm Password" error={errors.password_confirmation} required>
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        placeholder="Confirm password"
                        error={errors.password_confirmation}
                    />
                </FormField>
            </div>
        ),
    };

    return (
        <FormTemplate
            title="Create New User"
            breadcrumbs={breadcrumbs}
            backUrl="/users"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Create User"
            processingText="Creating..."
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
