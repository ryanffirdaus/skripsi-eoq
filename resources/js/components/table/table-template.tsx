import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { ArrowDownIcon, ArrowUpIcon, EyeIcon, FunnelIcon, MagnifyingGlassIcon, PlusIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { Head, Link, router } from '@inertiajs/react';
import React, { FormEvent, useEffect, useRef, useState } from 'react';

// Constants to prevent re-renders
const EMPTY_FILTER_OPTIONS: FilterOption[] = [];
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
    searchPlaceholder = 'Cari...',
    filters,
    filterOptions = EMPTY_FILTER_OPTIONS,
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
    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const [isColumnSelectorOpen, setIsColumnSelectorOpen] = useState(false);

    // Initialize visible columns based on defaultVisible or all visible by default
    const [visibleColumns, setVisibleColumns] = useState<Set<string>>(() => {
        const initialVisible = new Set<string>();
        columns.forEach((column) => {
            if (column.defaultVisible !== false) {
                // Show by default unless explicitly set to false
                initialVisible.add(column.key);
            }
        });
        return initialVisible;
    });

    // Local state for form inputs - Initialize from filters prop
    const [search, setSearch] = useState(filters?.search || '');
    const [sortBy, setSortBy] = useState(filters?.sort_by || '');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'asc');
    const [perPage, setPerPage] = useState(filters?.per_page || 10);
    const [localFilters, setLocalFilters] = useState<Record<string, string>>({});

    // Refs for debouncing
    const debounceTimeouts = useRef<Record<string, NodeJS.Timeout>>({});

    // Initialize local filters and sync with server state
    useEffect(() => {
        if (filters) {
            // Update local state to match server state
            setSearch(filters.search || '');
            setSortBy(filters.sort_by || '');
            setSortDirection(filters.sort_direction || 'asc');
            setPerPage(filters.per_page || 10);

            const initialFilters: Record<string, string> = {};
            if (filterOptions) {
                filterOptions.forEach((option) => {
                    const value = filters[option.key];
                    if (value !== undefined && value !== null && value !== '') {
                        initialFilters[option.key] = String(value);
                    }
                });
            }
            setLocalFilters(initialFilters);
        }
    }, [filters, filterOptions]);

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

        // Add additional filters
        Object.entries(localFilters).forEach(([key, value]) => {
            if (value && value !== 'all' && value !== '') {
                params[key] = value;
            }
        });

        return params;
    };

    const navigateWithFilters = (overrides: Record<string, string | number> = {}) => {
        const params = buildUrlParams(overrides);

        router.get(baseUrl, params, {
            preserveState: true,
            replace: true,
        });
    };

    // Column visibility management
    const toggleColumnVisibility = (columnKey: string) => {
        setVisibleColumns((prev) => {
            const newVisible = new Set(prev);
            if (newVisible.has(columnKey)) {
                newVisible.delete(columnKey);
            } else {
                newVisible.add(columnKey);
            }
            return newVisible;
        });
    };

    const showAllColumns = () => {
        const allColumnKeys = new Set(columns.map((col) => col.key));
        setVisibleColumns(allColumnKeys);
    };

    const hideAllColumns = () => {
        setVisibleColumns(new Set());
    };

    // Filter visible columns
    const visibleColumnsArray = columns.filter((column) => visibleColumns.has(column.key));

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
        setLocalFilters({});
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
    const hasActiveFilters =
        search.trim() !== '' || Object.values(localFilters).some((val) => val !== '' && val !== null && val !== undefined && val !== 'all');

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

    const handlePerPageChange = (newPerPage: number) => {
        // Prevent double execution with same value
        if (newPerPage === perPage) {
            return;
        }

        // Clear any existing per_page timeout
        if (debounceTimeouts.current['per_page']) {
            clearTimeout(debounceTimeouts.current['per_page']);
        }

        // Update state immediately for UI responsiveness
        setPerPage(newPerPage);

        // Debounce the actual navigation to prevent rapid calls
        debounceTimeouts.current['per_page'] = setTimeout(() => {
            const params: Record<string, string> = {
                sort_by: sortBy,
                sort_direction: sortDirection,
                per_page: newPerPage.toString(),
            };

            if (search.trim()) {
                params.search = search.trim();
            }

            Object.entries(localFilters).forEach(([key, value]) => {
                if (value && value !== 'all' && value !== '') {
                    params[key] = value;
                }
            });

            router.get(baseUrl, params, {
                preserveState: true,
                replace: true,
            });

            delete debounceTimeouts.current['per_page'];
        }, 200); // Slightly longer delay for per page
    };

    const handleFilterChange = (filterKey: string, value: string) => {
        // Clear existing timeout for this filter
        if (debounceTimeouts.current[filterKey]) {
            clearTimeout(debounceTimeouts.current[filterKey]);
        }

        // For select filters, apply immediately
        if (filterOptions.find((f) => f.key === filterKey)?.type === 'select') {
            // Update local state immediately
            const updatedFilters = { ...localFilters };

            // Remove the filter if value is empty or 'all'
            if (!value || value === '' || value === 'all') {
                delete updatedFilters[filterKey];
            } else {
                updatedFilters[filterKey] = value;
            }

            setLocalFilters(updatedFilters);

            // Build params without the cleared filter
            const params: Record<string, string> = {
                sort_by: sortBy,
                sort_direction: sortDirection,
                per_page: perPage.toString(),
            };

            if (search.trim()) {
                params.search = search.trim();
            }

            // Add other active filters
            Object.entries(updatedFilters).forEach(([key, val]) => {
                if (val && val !== 'all' && val !== '') {
                    params[key] = val;
                }
            });

            router.get(baseUrl, params, {
                preserveState: true,
                replace: true,
            });
        } else {
            // Update local state immediately for text filters
            const updatedFilters = { ...localFilters, [filterKey]: value };
            setLocalFilters(updatedFilters);

            // For text filters, debounce the API call
            debounceTimeouts.current[filterKey] = setTimeout(() => {
                navigateWithFilters({
                    [filterKey]: value,
                });
                delete debounceTimeouts.current[filterKey];
            }, 500);
        }
    };

    const handleTextFilterChange = (filterKey: string, value: string) => {
        // Update local state immediately for responsive UI
        setLocalFilters((prev) => ({ ...prev, [filterKey]: value }));

        // Clear existing timeout
        if (debounceTimeouts.current[filterKey]) {
            clearTimeout(debounceTimeouts.current[filterKey]);
        }

        // Set new timeout for debounced API call
        debounceTimeouts.current[filterKey] = setTimeout(() => {
            handleFilterChange(filterKey, value);
        }, 500);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                {/* Flash Message */}
                {message && (
                    <Alert
                        className={cn(
                            'border',
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
                <div className="flex items-center justify-between">
                    <h1 className={cn(colors.text.primary, 'text-2xl font-bold')}>{title}</h1>
                    {createUrl && (
                        <Link href={createUrl}>
                            <Button className="flex items-center gap-2">
                                <PlusIcon className="h-4 w-4" />
                                <span>{createButtonText}</span>
                            </Button>
                        </Link>
                    )}
                </div>

                {/* Search and Filter Bar */}
                <div className={cn('flex flex-col gap-4 rounded-lg p-4', colors.card.base)}>
                    <form onSubmit={handleSearch} className="flex flex-col gap-4 sm:flex-row">
                        {/* Search Input */}
                        <div className="relative flex-1">
                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
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
                        <div className="flex gap-2">
                            {filterOptions.length > 0 && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsFilterOpen(!isFilterOpen)}
                                    className="flex items-center gap-2"
                                >
                                    <FunnelIcon className="h-4 w-4" />
                                    Filters
                                </Button>
                            )}

                            {/* Column Selector Toggle */}
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsColumnSelectorOpen(!isColumnSelectorOpen)}
                                className="flex items-center gap-2"
                            >
                                <EyeIcon className="h-4 w-4" />
                                Columns
                            </Button>

                            <Button type="submit" className="flex items-center gap-2">
                                <MagnifyingGlassIcon className="h-4 w-4" />
                                Search
                            </Button>

                            {hasActiveFilters && (
                                <Button type="button" variant="outline" onClick={clearFilters} className="flex items-center gap-2">
                                    <XMarkIcon className="h-4 w-4" />
                                    Clear
                                </Button>
                            )}
                        </div>
                    </form>

                    {/* Advanced Filters */}
                    {isFilterOpen && filterOptions.length > 0 && (
                        <div className={cn('border-t pt-4', colors.border.primary)}>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {filterOptions.map((filter) => (
                                    <div key={filter.key} className="space-y-2">
                                        <label className={colors.label.base}>{filter.label}</label>
                                        {filter.type === 'select' ? (
                                            <select
                                                value={localFilters[filter.key] || ''}
                                                onChange={(e) => handleFilterChange(filter.key, e.target.value)}
                                                className={colors.input.base}
                                            >
                                                <option value="">{filter.placeholder || 'Select...'}</option>
                                                {filter.options?.map((option) => (
                                                    <option key={option.value} value={option.value}>
                                                        {option.label}
                                                    </option>
                                                ))}
                                            </select>
                                        ) : (
                                            <input
                                                type="text"
                                                value={localFilters[filter.key] || ''}
                                                onChange={(e) => handleTextFilterChange(filter.key, e.target.value)}
                                                placeholder={filter.placeholder}
                                                className={colors.input.base}
                                            />
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Column Selector */}
                    {isColumnSelectorOpen && (
                        <div className={cn('border-t pt-4', colors.border.primary)}>
                            <div className="mb-3 flex items-center justify-between">
                                <h3 className="text-sm font-medium text-gray-900">Column Visibility</h3>
                                <div className="flex gap-2">
                                    <Button type="button" variant="outline" size="sm" onClick={showAllColumns} className="text-xs">
                                        Show All
                                    </Button>
                                    <Button type="button" variant="outline" size="sm" onClick={hideAllColumns} className="text-xs">
                                        Hide All
                                    </Button>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                {columns
                                    .filter((col) => col.hideable !== false)
                                    .map((column) => (
                                        <label key={column.key} className="flex items-center space-x-2 text-sm">
                                            <input
                                                type="checkbox"
                                                checked={visibleColumns.has(column.key)}
                                                onChange={() => toggleColumnVisibility(column.key)}
                                                className="rounded"
                                            />
                                            <span className="truncate">{column.label}</span>
                                        </label>
                                    ))}
                            </div>
                        </div>
                    )}
                </div>

                {/* Results Summary */}
                <div className={cn('flex items-center justify-between text-sm', colors.text.secondary)}>
                    <div>
                        Showing {data.from || 0} to {data.to || 0} of {data.total} results
                        {hasActiveFilters && <span className="ml-2 text-blue-600">(filtered)</span>}
                    </div>
                    <div className="flex items-center gap-2">
                        <label className="text-sm">Per page:</label>
                        <select
                            value={perPage}
                            onChange={(e) => handlePerPageChange(Number(e.target.value))}
                            className="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
                        >
                            <option value={10}>10</option>
                            <option value={25}>25</option>
                            <option value={50}>50</option>
                            <option value={100}>100</option>
                        </select>
                    </div>
                </div>

                {/* Table */}
                <div className={colors.card.base}>
                    <div className="overflow-x-auto">
                        <table className="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className={cn(colors.background.secondary)}>
                                <tr>
                                    {visibleColumnsArray.map((column) => (
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
                                        {visibleColumnsArray.map((column) => (
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
                        <div className={cn('border-t px-4 py-3', colors.border.primary, colors.background.primary)}>
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <span className={cn('text-sm', colors.text.secondary)}>
                                        Page {data.current_page} of {data.last_page}
                                    </span>
                                </div>
                                <div className="flex items-center gap-1">
                                    {data.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => handlePagination(link.url)}
                                            disabled={!link.url}
                                            className={cn(
                                                'rounded px-3 py-1 text-sm',
                                                link.active
                                                    ? 'bg-blue-600 text-white'
                                                    : cn('border', colors.border.primary, colors.hover.primary, colors.text.secondary),
                                                !link.url && 'cursor-not-allowed opacity-50',
                                            )}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
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
                                Hapus
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
