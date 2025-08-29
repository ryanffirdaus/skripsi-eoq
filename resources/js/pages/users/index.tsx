import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    ArrowDownIcon,
    ArrowUpIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    PlusIcon,
    TrashIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';

interface Role {
    role_id: string;
    name: string;
}

interface User {
    user_id: string;
    nama_lengkap: string;
    email: string;
    role_id?: string;
    role?: Role | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
}

interface Filters {
    search?: string;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
    per_page: number;
    role_id?: string;
}

interface Props {
    users: PaginatedUsers;
    roles: Role[];
    filters: Filters;
    flash?: {
        message?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];

export default function Index({ users, roles, filters, flash }: Props) {
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [userToDelete, setUserToDelete] = useState<User | null>(null);
    const [message, setMessage] = useState<string | null>(null);
    const [isFilterOpen, setIsFilterOpen] = useState(false);

    // Local state for form inputs
    const [search, setSearch] = useState(filters.search || '');
    const [sortBy, setSortBy] = useState(filters.sort_by);
    const [sortDirection, setSortDirection] = useState(filters.sort_direction);
    const [perPage, setPerPage] = useState(filters.per_page);
    const [selectedRole, setSelectedRole] = useState(filters.role_id || '');

    // Show flash message if available
    useEffect(() => {
        if (flash?.message) {
            setMessage(flash.message);
            const timer = setTimeout(() => setMessage(null), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash?.message]);

    const handleSearch = (e: FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        const params: Record<string, string> = {
            sort_by: sortBy,
            sort_direction: sortDirection,
            per_page: perPage.toString(),
        };

        if (search.trim()) {
            params.search = search.trim();
        }

        if (selectedRole) {
            params.role_id = selectedRole;
        }

        router.get('/users', params, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSort = (column: string) => {
        if (sortBy === column) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortBy(column);
            setSortDirection('asc');
        }

        // Apply sorting immediately
        setTimeout(applyFilters, 0);
    };

    const clearFilters = () => {
        setSearch('');
        setSelectedRole('');
        setSortBy('user_id');
        setSortDirection('asc');
        setPerPage(10);

        router.get(
            '/users',
            {},
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const hasActiveFilters = search || selectedRole || sortBy !== 'user_id' || sortDirection !== 'asc' || perPage !== 10;

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

    const getSortIcon = (column: string) => {
        if (sortBy !== column) return null;
        return sortDirection === 'asc' ? <ArrowUpIcon className="h-4 w-4" /> : <ArrowDownIcon className="h-4 w-4" />;
    };

    const handlePagination = (url: string | null) => {
        if (url) {
            router.visit(url, {
                preserveState: true,
                replace: true,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users Management" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                {message && (
                    <Alert className="border border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
                        <AlertDescription>{message}</AlertDescription>
                    </Alert>
                )}

                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Users Management</h1>
                    <Link href="/users/create">
                        <Button className="flex items-center gap-2">
                            <PlusIcon className="h-4 w-4" />
                            <span>Add User</span>
                        </Button>
                    </Link>
                </div>

                {/* Search and Filter Bar */}
                <div className="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <form onSubmit={handleSearch} className="flex flex-col gap-4 sm:flex-row">
                        {/* Search Input */}
                        <div className="relative flex-1">
                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                            </div>
                            <input
                                type="text"
                                placeholder="Search by name or email..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="block w-full rounded-md border border-gray-300 bg-white py-2 pr-3 pl-10 text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                            />
                        </div>

                        {/* Filter Toggle */}
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsFilterOpen(!isFilterOpen)}
                                className="flex items-center gap-2 dark:border-gray-600 dark:hover:bg-gray-700"
                            >
                                <FunnelIcon className="h-4 w-4" />
                                <span>Filters</span>
                                {hasActiveFilters && (
                                    <span className="ml-1 rounded-full bg-blue-500 px-2 py-0.5 text-xs text-white">
                                        {[search, selectedRole].filter(Boolean).length}
                                    </span>
                                )}
                            </Button>

                            <Button type="submit" className="flex items-center gap-2">
                                <MagnifyingGlassIcon className="h-4 w-4" />
                                <span>Search</span>
                            </Button>
                        </div>
                    </form>

                    {/* Advanced Filters */}
                    {isFilterOpen && (
                        <div className="border-t border-gray-200 pt-4 dark:border-gray-600">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                {/* Role Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                                    <select
                                        value={selectedRole}
                                        onChange={(e) => setSelectedRole(e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="">All Roles</option>
                                        {roles?.map((role) => (
                                            <option key={role.role_id} value={role.role_id}>
                                                {role.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Sort By */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Sort By</label>
                                    <select
                                        value={sortBy}
                                        onChange={(e) => setSortBy(e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="user_id">User ID</option>
                                        <option value="nama_lengkap">Name</option>
                                        <option value="email">Email</option>
                                        <option value="role_id">Role</option>
                                    </select>
                                </div>

                                {/* Sort Direction */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Direction</label>
                                    <select
                                        value={sortDirection}
                                        onChange={(e) => setSortDirection(e.target.value as 'asc' | 'desc')}
                                        className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="asc">Ascending</option>
                                        <option value="desc">Descending</option>
                                    </select>
                                </div>

                                {/* Per Page */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Per Page</label>
                                    <select
                                        value={perPage}
                                        onChange={(e) => setPerPage(Number(e.target.value))}
                                        className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="5">5</option>
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>

                            <div className="mt-4 flex justify-between">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={clearFilters}
                                    disabled={!hasActiveFilters}
                                    className="flex items-center gap-2 dark:border-gray-600 dark:hover:bg-gray-700"
                                >
                                    <XMarkIcon className="h-4 w-4" />
                                    <span>Clear Filters</span>
                                </Button>

                                <Button type="button" onClick={applyFilters} className="flex items-center gap-2">
                                    <FunnelIcon className="h-4 w-4" />
                                    <span>Apply Filters</span>
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

                {/* Results Summary */}
                <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                    <div>
                        Showing {users.from || 0} to {users.to || 0} of {users.total} results
                        {hasActiveFilters && <span className="ml-2 text-blue-600 dark:text-blue-400">(filtered)</span>}
                    </div>
                </div>

                {/* Users Table */}
                <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div className="overflow-x-auto">
                        <table className="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-800/50">
                                <tr>
                                    <th className="px-6 py-3 text-left">
                                        <button
                                            onClick={() => handleSort('user_id')}
                                            className="flex items-center gap-2 text-xs font-medium tracking-wider text-gray-500 uppercase hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                        >
                                            User ID
                                            {getSortIcon('user_id')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left">
                                        <button
                                            onClick={() => handleSort('nama_lengkap')}
                                            className="flex items-center gap-2 text-xs font-medium tracking-wider text-gray-500 uppercase hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                        >
                                            Nama Lengkap
                                            {getSortIcon('nama_lengkap')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left">
                                        <button
                                            onClick={() => handleSort('email')}
                                            className="flex items-center gap-2 text-xs font-medium tracking-wider text-gray-500 uppercase hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                        >
                                            Email
                                            {getSortIcon('email')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left">
                                        <button
                                            onClick={() => handleSort('role_id')}
                                            className="flex items-center gap-2 text-xs font-medium tracking-wider text-gray-500 uppercase hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                        >
                                            Role
                                            {getSortIcon('role_id')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {users.data.map((user) => (
                                    <tr key={user.user_id} className="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                        <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-200">{user.user_id}</td>
                                        <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-200">{user.nama_lengkap}</td>
                                        <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-200">{user.email}</td>
                                        <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-200">
                                            {user.role?.name || 'No role assigned'}
                                        </td>
                                        <td className="flex gap-2 px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                            <Link href={`/users/${user.user_id}/edit`}>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="flex items-center gap-1 dark:border-gray-700 dark:hover:bg-gray-800"
                                                >
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
                                {users.data.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                            {hasActiveFilters ? 'No users found matching your criteria' : 'No users found'}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {users.last_page > 1 && (
                        <div className="border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <span className="text-sm text-gray-700 dark:text-gray-300">
                                        Page {users.current_page} of {users.last_page}
                                    </span>
                                </div>

                                <div className="flex items-center gap-1">
                                    {/* First Page */}
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={users.current_page === 1}
                                        onClick={() => handlePagination(users.links.find((link) => link.label === '&laquo; Previous')?.url ?? null)}
                                        className="dark:border-gray-600 dark:hover:bg-gray-700"
                                    >
                                        <ChevronLeftIcon className="h-4 w-4" />
                                    </Button>

                                    {/* Page Numbers */}
                                    {users.links
                                        .filter((link) => link.label !== '&laquo; Previous' && link.label !== 'Next &raquo;')
                                        .map((link, index) => (
                                            <Button
                                                key={index}
                                                variant={link.active ? 'default' : 'outline'}
                                                size="sm"
                                                disabled={!link.url}
                                                onClick={() => handlePagination(link.url)}
                                                className="dark:border-gray-600 dark:hover:bg-gray-700"
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}

                                    {/* Last Page */}
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={users.current_page === users.last_page}
                                        onClick={() => handlePagination(users.links.find((link) => link.label === 'Next &raquo;')?.url ?? null)}
                                        className="dark:border-gray-600 dark:hover:bg-gray-700"
                                    >
                                        <ChevronRightIcon className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Delete Confirmation Dialog */}
            {isDeleteDialogOpen && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
                    onClick={() => setIsDeleteDialogOpen(false)}
                >
                    <div
                        className="w-full max-w-md rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-900"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-white">Delete Confirmation</h3>
                        <p className="mb-6 text-gray-600 dark:text-gray-300">
                            Are you sure you want to delete user{' '}
                            <span className="font-medium text-gray-900 dark:text-white">{userToDelete?.nama_lengkap}</span>? This action cannot be
                            undone.
                        </p>
                        <div className="flex justify-end space-x-3">
                            <Button
                                variant="outline"
                                onClick={() => setIsDeleteDialogOpen(false)}
                                className="dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </Button>
                            <Button variant="destructive" onClick={handleDelete}>
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
