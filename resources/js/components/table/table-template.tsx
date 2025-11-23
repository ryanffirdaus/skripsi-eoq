import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { ArrowDownIcon, ArrowUpIcon, MagnifyingGlassIcon, PlusIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { Head, Link, router } from '@inertiajs/react';
import React, { FormEvent, useEffect, useRef, useState } from 'react';

// Constants to prevent re-renders
const EMPTY_ACTIONS: ActionButton<Record<string, unknown>>[] = [];

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
}

interface ColumnDefinition<T> {
    key: string;
    label: string;
    sortable?: boolean;
    render?: (item: T) => React.ReactNode;
    className?: string;
    hideable?: boolean; // Whether this column can be hidden/shown
    defaultVisible?: boolean; // Whether this column is visible by default
}

interface FilterOption {
    key: string;
    label: string;
    type: 'text' | 'select';
    options?: Array<{ value: string; label: string }>;
    placeholder?: string;
}

interface ActionButton<T> {
    label: string;
    icon?: React.ComponentType<{ className?: string }>;
    variant?: 'default' | 'secondary' | 'outline' | 'destructive' | 'ghost' | 'link';
    onClick: (item: T) => void;
    show?: (item: T) => boolean;
}

interface TableTemplateProps<T> {
    title: string;
    breadcrumbs: BreadcrumbItem[];
    data: PaginatedData<T>;
    columns: ColumnDefinition<T>[];
    createUrl?: string;
    createButtonText?: string;
    searchPlaceholder?: string;
    filters?: {
        search?: string;
        sort_by: string;
        sort_direction: 'asc' | 'desc';
        per_page: number;
        [key: string]: string | number | undefined;
    };
    filterOptions?: FilterOption[];
    baseUrl: string;
    actions?: ActionButton<T>[];
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
    onDelete?: (item: T) => void;
    deleteDialogTitle?: string;
    deleteDialogMessage?: (item: T) => string;
    getItemName?: (item: T) => string;
    // New prop to specify the ID field name (kept for compatibility)
    idField?: string;
}

export default function TableTemplate<T extends Record<string, unknown>>({
    title,
    breadcrumbs,
    data,
    columns,
    createUrl,
    createButtonText = 'Tambah',
    searchPlaceholder = 'Search',
    filters,
    baseUrl,
    actions = EMPTY_ACTIONS,
    flash,
    onDelete,
    deleteDialogTitle = 'Konfirmasi Penghapusan',
    deleteDialogMessage = () => 'Apakah Anda yakin ingin menghapus item ini?',
    getItemName = () => '',
}: TableTemplateProps<T>) {
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [itemToDelete, setItemToDelete] = useState<T | null>(null);
    const [deleteCallback, setDeleteCallback] = useState<((item: T) => void) | null>(null);
    const [message, setMessage] = useState<string | null>(null);

    // Local state for form inputs - Initialize from filters prop
    const [search, setSearch] = useState(filters?.search || '');
    const [sortBy, setSortBy] = useState(filters?.sort_by || '');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'asc');
    const [perPage, setPerPage] = useState(filters?.per_page || 10);

    // Refs for debouncing
    const debounceTimeouts = useRef<Record<string, NodeJS.Timeout>>({});

    // Cleanup timeouts on unmount
    useEffect(() => {
        return () => {
            Object.values(debounceTimeouts.current).forEach(clearTimeout);
        };
    }, []);

    // Show flash message if available
    useEffect(() => {
        if (flash?.message) {
            setMessage(flash.message);
            const timer = setTimeout(() => setMessage(null), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash?.message]);

    const buildUrlParams = (overrides: Record<string, string | number> = {}) => {
        const params: Record<string, string> = {
            sort_by: sortBy,
            sort_direction: sortDirection,
            per_page: perPage.toString(),
            ...overrides,
        };

        if (search.trim()) {
            params.search = search.trim();
        }

        return params;
    };

    const navigateWithFilters = (overrides: Record<string, string | number> = {}) => {
        const params = buildUrlParams(overrides);

        router.get(baseUrl, params, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSearch = (e: FormEvent) => {
        e.preventDefault();
        navigateWithFilters();
    };

    const handleSort = (column: string) => {
        const newSortBy = column;
        const newSortDirection: 'asc' | 'desc' = sortBy === column ? (sortDirection === 'asc' ? 'desc' : 'asc') : 'asc';

        setSortBy(newSortBy);
        setSortDirection(newSortDirection);

        navigateWithFilters({
            sort_by: newSortBy,
            sort_direction: newSortDirection,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setSortBy(filters?.sort_by || '');
        setSortDirection(filters?.sort_direction || 'asc');
        setPerPage(10);

        // Clear any pending timeouts
        Object.values(debounceTimeouts.current).forEach(clearTimeout);
        debounceTimeouts.current = {};

        router.get(
            baseUrl,
            {
                sort_by: filters?.sort_by || '',
                sort_direction: filters?.sort_direction || 'asc',
                per_page: '10',
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    // Check if there are any active filters applied by user
    const hasActiveFilters = search.trim() !== '';

    const confirmDelete = (item: T, callback?: (item: T) => void) => {
        setItemToDelete(item);
        setDeleteCallback(() => callback || onDelete);
        setIsDeleteDialogOpen(true);
    };

    const handleDelete = () => {
        if (itemToDelete && deleteCallback) {
            deleteCallback(itemToDelete);
            setIsDeleteDialogOpen(false);
            setDeleteCallback(null);
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
            <Head title={title} />

            <div className="flex h-full flex-1 flex-col gap-2 overflow-hidden p-3 sm:gap-3 sm:p-4 md:gap-4 md:p-6">
                {/* Flash Message */}
                {message && (
                    <Alert
                        className={cn(
                            'border text-sm',
                            flash?.type === 'error'
                                ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300'
                                : flash?.type === 'warning'
                                  ? 'border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                                  : 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300',
                        )}
                    >
                        <AlertDescription>{message}</AlertDescription>
                    </Alert>
                )}

                {/* Header */}
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h1 className={cn(colors.text.primary, 'text-xl font-bold sm:text-2xl')}>{title}</h1>
                    {createUrl && (
                        <Link href={createUrl}>
                            <Button className="flex items-center gap-2 px-3 text-xs sm:px-4 sm:text-sm">
                                <PlusIcon className="h-4 w-4" />
                                <span>{createButtonText}</span>
                            </Button>
                        </Link>
                    )}
                </div>

                {/* Search and Filter Bar */}
                <div className={cn('flex flex-col gap-2 rounded-lg p-3 sm:gap-3 sm:p-4', colors.card.base)}>
                    <form onSubmit={handleSearch} className="flex flex-col gap-2 sm:gap-3">
                        {/* Search Input */}
                        <div className="relative w-full">
                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2 sm:pl-3">
                                <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                            </div>
                            <input
                                type="text"
                                placeholder={searchPlaceholder}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className={cn(colors.input.base, 'pl-10')}
                            />
                        </div>

                        {/* Filter Toggle */}
                        <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                            <Button
                                type="submit"
                                className="flex flex-1 items-center justify-center gap-2 px-3 text-xs sm:flex-none sm:px-4 sm:text-sm"
                            >
                                <MagnifyingGlassIcon className="h-4 w-4" />
                                <span>Search</span>
                            </Button>

                            {hasActiveFilters && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={clearFilters}
                                    className="flex flex-1 items-center justify-center gap-2 px-3 text-xs sm:flex-none sm:px-4 sm:text-sm"
                                >
                                    <XMarkIcon className="h-4 w-4" />
                                    <span>Clear</span>
                                </Button>
                            )}
                        </div>
                    </form>
                </div>

                {/* Table */}
                <div className={colors.card.base}>
                    <div className="overflow-x-auto">
                        <table className="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className={cn(colors.background.secondary)}>
                                <tr>
                                    {columns.map((column) => (
                                        <th
                                            key={column.key}
                                            className={cn(
                                                'px-6 py-3 text-left text-xs font-medium tracking-wider uppercase',
                                                colors.text.secondary,
                                                column.sortable && 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700',
                                                column.className,
                                            )}
                                            onClick={() => column.sortable && handleSort(column.key)}
                                        >
                                            <div className="flex items-center gap-2">
                                                {column.label}
                                                {column.sortable && getSortIcon(column.key)}
                                            </div>
                                        </th>
                                    ))}
                                    {actions.length > 0 && (
                                        <th
                                            className={cn('px-6 py-3 text-right text-xs font-medium tracking-wider uppercase', colors.text.secondary)}
                                        ></th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className={cn('divide-y divide-gray-200 dark:divide-gray-700', colors.background.primary)}>
                                {data.data.map((item, index) => (
                                    <tr key={index} className={colors.hover.primary}>
                                        {columns.map((column) => (
                                            <td
                                                key={column.key}
                                                className={cn('px-6 py-4 text-sm whitespace-nowrap', colors.text.primary, column.className)}
                                            >
                                                {column.render ? column.render(item) : String(item[column.key] || '')}
                                            </td>
                                        ))}
                                        {actions.length > 0 && (
                                            <td className="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                                <div className="flex items-center justify-end gap-2">
                                                    {/* Custom actions only */}
                                                    {actions.map((action, actionIndex) => {
                                                        if (action.show && !action.show(item)) return null;

                                                        return (
                                                            <Button
                                                                key={actionIndex}
                                                                variant={action.variant || 'outline'}
                                                                size="sm"
                                                                onClick={() => {
                                                                    if (action.variant === 'destructive') {
                                                                        confirmDelete(item, action.onClick);
                                                                    } else {
                                                                        action.onClick(item);
                                                                    }
                                                                }}
                                                                className="flex items-center gap-1"
                                                            >
                                                                {action.icon && <action.icon className="h-3 w-3" />}
                                                                {action.label}
                                                            </Button>
                                                        );
                                                    })}
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                                {data.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={columns.length + (actions.length > 0 ? 1 : 0)}
                                            className={cn('px-6 py-8 text-center text-sm', colors.text.secondary)}
                                        >
                                            No data available
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {data.last_page > 1 && (
                        <div className={cn('border-t px-3 py-2 sm:px-4 sm:py-3', colors.border.primary, colors.background.primary)}>
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-center gap-2">
                                    <span className={cn('text-xs sm:text-sm', colors.text.secondary)}>
                                        Hal {data.current_page} dari {data.last_page}
                                    </span>
                                </div>
                                <div className="flex items-center gap-1 overflow-x-auto">
                                    {data.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => handlePagination(link.url)}
                                            disabled={!link.url}
                                            className={cn(
                                                'rounded px-2 py-1 text-xs whitespace-nowrap sm:px-3',
                                                link.active
                                                    ? 'bg-blue-600 text-white'
                                                    : cn('border', colors.border.primary, colors.hover.primary, colors.text.secondary),
                                                !link.url && 'cursor-not-allowed opacity-50',
                                            )}
                                            title={link.label}
                                        >
                                            {link.label.includes('Previous') ? 'Sebelumnya' : link.label.includes('Next') ? 'Berikutnya' : link.label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Delete Confirmation Dialog */}
            {isDeleteDialogOpen && itemToDelete && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
                    onClick={() => setIsDeleteDialogOpen(false)}
                >
                    <div className={cn('w-full max-w-md rounded-lg p-6 shadow-lg', colors.card.base)} onClick={(e) => e.stopPropagation()}>
                        <h3 className={cn('mb-4 text-lg font-medium', colors.text.primary)}>{deleteDialogTitle}</h3>
                        <p className={cn('mb-6', colors.text.secondary)}>
                            {deleteDialogMessage(itemToDelete)}
                            {getItemName(itemToDelete) && (
                                <span className={cn('font-medium', colors.text.primary)}> {getItemName(itemToDelete)}</span>
                            )}
                        </p>
                        <div className="flex justify-end space-x-3">
                            <Button
                                variant="outline"
                                onClick={() => setIsDeleteDialogOpen(false)}
                                className={cn(colors.background.primary, colors.border.secondary, colors.text.secondary, colors.hover.primary)}
                            >
                                Batal
                            </Button>
                            <Button variant="destructive" onClick={handleDelete}>
                                Ya
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
