import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { PlusIcon, TrashIcon, PencilIcon, EyeIcon } from '@heroicons/react/24/outline';

interface User {
  user_id: string;
  nama_lengkap: string;
  email: string;
}

interface Props {
  users: User[];
  flash?: {
    message?: string;
  };
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Users',
    href: '/users', // Direct URL instead of routes helper
  },
];

export default function Index({ users, flash }: Props) {
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
  const [userToDelete, setUserToDelete] = useState<User | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  // Show flash message if available
  useEffect(() => {
    if (flash?.message) {
      setMessage(flash.message);
      const timer = setTimeout(() => setMessage(null), 5000); // Auto-dismiss after 5 seconds
      return () => clearTimeout(timer);
    }
  }, [flash?.message]);

  const confirmDelete = (user: User) => {
    setUserToDelete(user);
    setIsDeleteDialogOpen(true);
  };

  const handleDelete = () => {
    if (userToDelete) {
      router.delete(`/users/${userToDelete.user_id}`);
      setIsDeleteDialogOpen(false);
    }
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Users Management" />

      <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
        {message && (
          <Alert className="bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-300 border border-green-200 dark:border-green-800">
            <AlertDescription>{message}</AlertDescription>
          </Alert>
        )}

        <div className="flex justify-between items-center mb-4">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Users Management</h1>
          <Link href="/users/create">
            <Button className="flex items-center gap-2">
              <PlusIcon className="h-4 w-4" />
              <span>Add User</span>
            </Button>
          </Link>
        </div>

        <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div className="overflow-x-auto">
            <table className="w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead className="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">User ID</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Nama Lengkap</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Email</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                {users.map((user) => (
                  <tr key={user.user_id} className="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{user.user_id}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{user.nama_lengkap}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{user.email}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 flex gap-2">
                      <Link href={`/users/${user.user_id}`}>
                        <Button variant="outline" size="sm" className="flex items-center gap-1 dark:border-gray-700 dark:hover:bg-gray-800">
                          <EyeIcon className="h-4 w-4" />
                          <span>View</span>
                        </Button>
                      </Link>
                      <Link href={`/users/${user.user_id}/edit`}>
                        <Button variant="outline" size="sm" className="flex items-center gap-1 dark:border-gray-700 dark:hover:bg-gray-800">
                          <PencilIcon className="h-4 w-4" />
                          <span>Edit</span>
                        </Button>
                      </Link>
                      <Button
                        variant="destructive"
                        size="sm"
                        className="flex items-center gap-1"
                        onClick={() => confirmDelete(user)}
                      >
                        <TrashIcon className="h-4 w-4" />
                        <span>Delete</span>
                      </Button>
                    </td>
                  </tr>
                ))}
                {users.length === 0 && (
                  <tr>
                    <td colSpan={4} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                      No users found
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Delete Confirmation Dialog */}
      {isDeleteDialogOpen && (
        <div
          className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
          onClick={() => setIsDeleteDialogOpen(false)}
        >
          <div
            className="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg max-w-md w-full border border-gray-200 dark:border-gray-700"
            onClick={(e) => e.stopPropagation()}
          >
            <h3 className="text-lg font-medium mb-4 text-gray-900 dark:text-white">Delete Confirmation</h3>
            <p className="mb-6 text-gray-600 dark:text-gray-300">
              Are you sure you want to delete user <span className="font-medium text-gray-900 dark:text-white">{userToDelete?.nama_lengkap}</span>? This action cannot be undone.
            </p>
            <div className="flex justify-end space-x-3">
              <Button
                variant="outline"
                onClick={() => setIsDeleteDialogOpen(false)}
                className="dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
              >
                Cancel
              </Button>
              <Button
                variant="destructive"
                onClick={handleDelete}
              >
                Delete
              </Button>
            </div>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
