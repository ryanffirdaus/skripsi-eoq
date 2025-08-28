import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import InputError from '@/components/input-error';

interface User {
  user_id: string;
  nama_lengkap: string;
  email: string;
}

interface Props {
  user: User;
}

export default function Edit({ user }: Props) {
  const { data, setData, put, processing, errors } = useForm({
    nama_lengkap: user.nama_lengkap || '',
    email: user.email || '',
    password: '',
    password_confirmation: '',
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    put(`/users/${user.user_id}`);
  }

  return (
    <AppLayout>
      <Head title={`Edit User: ${user.nama_lengkap}`} />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6 bg-white border-b border-gray-200">
              <h2 className="text-2xl font-semibold mb-6">Edit User</h2>

              <form onSubmit={handleSubmit}>
                <div className="mb-4">
                  <label htmlFor="nama_lengkap" className="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                  <input
                    id="nama_lengkap"
                    type="text"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    value={data.nama_lengkap}
                    onChange={e => setData('nama_lengkap', e.target.value)}
                  />
                  {errors.nama_lengkap && <InputError message={errors.nama_lengkap} className="mt-2" />}
                </div>

                <div className="mb-4">
                  <label htmlFor="email" className="block text-sm font-medium text-gray-700">Email</label>
                  <input
                    id="email"
                    type="email"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    value={data.email}
                    onChange={e => setData('email', e.target.value)}
                  />
                  {errors.email && <InputError message={errors.email} className="mt-2" />}
                </div>

                <div className="mb-4">
                  <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                    Password <span className="text-xs text-gray-500">(leave blank to keep current password)</span>
                  </label>
                  <input
                    id="password"
                    type="password"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    value={data.password}
                    onChange={e => setData('password', e.target.value)}
                  />
                  {errors.password && <InputError message={errors.password} className="mt-2" />}
                </div>

                <div className="mb-4">
                  <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700">Confirm Password</label>
                  <input
                    id="password_confirmation"
                    type="password"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    value={data.password_confirmation}
                    onChange={e => setData('password_confirmation', e.target.value)}
                  />
                </div>

                <div className="flex items-center justify-end mt-6">
                  <Button
                    type="button"
                    variant="outline"
                    className="mr-2"
                    onClick={() => window.history.back()}
                  >
                    Cancel
                  </Button>
                  <Button type="submit" disabled={processing}>
                    Update User
                  </Button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
