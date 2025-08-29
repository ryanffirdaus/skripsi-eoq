import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { CheckIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { Head, Link, useForm } from '@inertiajs/react';
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit User: ${user.nama_lengkap}`} />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                <div className="mb-4 flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Edit User</h1>
                    <Link href="/users">
                        <Button variant="outline" className="flex items-center gap-2 dark:border-gray-700 dark:hover:bg-gray-800">
                            <XMarkIcon className="h-4 w-4" />
                            <span>Cancel</span>
                        </Button>
                    </Link>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div className="p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <label htmlFor="nama_lengkap" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Nama Lengkap
                                    </label>
                                    <input
                                        id="nama_lengkap"
                                        type="text"
                                        className="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-400 dark:focus:border-blue-500"
                                        value={data.nama_lengkap}
                                        onChange={(e) => setData('nama_lengkap', e.target.value)}
                                        placeholder="Enter full name"
                                    />
                                    {errors.nama_lengkap && <InputError message={errors.nama_lengkap} className="mt-1" />}
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="email" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Email
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        className="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-400 dark:focus:border-blue-500"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="Enter email address"
                                    />
                                    {errors.email && <InputError message={errors.email} className="mt-1" />}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label htmlFor="role_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Role
                                </label>
                                <select
                                    id="role_id"
                                    className="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500"
                                    value={data.role_id}
                                    onChange={(e) => setData('role_id', e.target.value)}
                                >
                                    <option value="">Select role</option>
                                    {roles.map((role: Role) => (
                                        <option key={role.role_id} value={role.role_id}>
                                            {role.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.role_id && <InputError message={errors.role_id} className="mt-1" />}
                            </div>

                            <div className="border-t border-gray-200 pt-6 dark:border-gray-700">
                                <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-white">Change Password</h3>
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <label htmlFor="password" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            New Password
                                            <span className="ml-1 text-xs text-gray-500 dark:text-gray-400">(leave blank to keep current)</span>
                                        </label>
                                        <input
                                            id="password"
                                            type="password"
                                            className="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-400 dark:focus:border-blue-500"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            placeholder="Enter new password"
                                        />
                                        {errors.password && <InputError message={errors.password} className="mt-1" />}
                                    </div>

                                    <div className="space-y-2">
                                        <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Confirm Password
                                        </label>
                                        <input
                                            id="password_confirmation"
                                            type="password"
                                            className="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-400 dark:focus:border-blue-500"
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            placeholder="Confirm new password"
                                        />
                                        {errors.password_confirmation && <InputError message={errors.password_confirmation} className="mt-1" />}
                                    </div>
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                                <Link href="/users">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                                    >
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={processing} className="flex items-center gap-2">
                                    <CheckIcon className="h-4 w-4" />
                                    <span>{processing ? 'Updating...' : 'Update User'}</span>
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
