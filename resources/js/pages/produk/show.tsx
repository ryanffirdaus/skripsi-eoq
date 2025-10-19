import TimestampSection from '@/components/timestamp-section';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { colors } from '@/lib/colors';
import { formatCurrency, formatNumber, formatPercentage, safeAdd, safeDivide, safeMultiply } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface UserRef {
    user_id: string;
    nama_lengkap: string;
}

interface BahanBaku {
    bahan_baku_id: string;
    nama_bahan: string;
    satuan: string;
    harga_per_unit: number;
    pivot: {
        produk_id: string;
        bahan_baku_id: string;
        jumlah_bahan_baku: number;
    };
}

interface Produk {
    produk_id: string;
    nama_produk: string;
    deskripsi?: string;
    hpp: number;
    harga_jual: number;
    demand_tahunan: number;
    created_by_id?: string;
    updated_by_id?: string;
    created_by?: UserRef | null;
    updated_by?: UserRef | null;
    created_at: string;
    updated_at: string;
    bahan_baku?: BahanBaku[];
}

interface Props {
    produk: Produk;
    permissions: {
        canEdit?: boolean;
        canDelete?: boolean;
    };
}

export default function Show({ produk, permissions }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Produk',
            href: '/produk',
        },
        {
            title: `Detail ${produk.nama_produk}`,
            href: `/produk/${produk.produk_id}`,
        },
    ];

    const profitMargin = safeDivide(produk.harga_jual - produk.hpp, produk.harga_jual) * 100;
    const totalMaterialCost = safeAdd(
        ...(produk.bahan_baku?.map((bahan) => safeMultiply(bahan.harga_per_unit, bahan.pivot.jumlah_bahan_baku)) || []),
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Produk ${produk.nama_produk}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className={colors.card.base}>
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">{produk.nama_produk}</h1>
                                <p className={cn('mt-1', colors.text.secondary)}>Product ID: {produk.produk_id}</p>
                            </div>
                            <div className="flex gap-3">
                                {permissions.canEdit && (
                                    <Link href={`/produk/${produk.produk_id}/edit`}>
                                        <Button variant="outline">Edit Produk</Button>
                                    </Link>
                                )}
                                <Link href="/produk">
                                    <Button variant="outline">Kembali</Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="p-6">
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            {/* Basic Information */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Informasi Produk</h3>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Nama Produk</label>
                                    <p className={cn('mt-1 text-lg', colors.text.primary)}>{produk.nama_produk}</p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Deskripsi</label>
                                    <p className={cn('mt-1', colors.text.primary)}>{produk.deskripsi || 'Tidak ada deskripsi'}</p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Product ID</label>
                                    <p className={cn('mt-1 rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-800', colors.text.primary)}>
                                        {produk.produk_id}
                                    </p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Demand Tahunan</label>
                                    <p className={cn('mt-1', colors.text.primary)}>{formatNumber(produk.demand_tahunan)} unit/tahun</p>
                                </div>
                            </div>

                            {/* Pricing Information */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Informasi Harga</h3>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Harga Pokok Produksi (HPP)</label>
                                    <p className={cn('mt-1 text-lg font-semibold', colors.text.primary)}>{formatCurrency(produk.hpp)}</p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Harga Jual</label>
                                    <p className={cn('mt-1 text-lg font-semibold text-green-600 dark:text-green-400')}>
                                        {formatCurrency(produk.harga_jual)}
                                    </p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Profit per Unit</label>
                                    <p className={cn('mt-1 text-lg font-semibold text-blue-600 dark:text-blue-400')}>
                                        {formatCurrency(produk.harga_jual - produk.hpp)}
                                    </p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Margin Keuntungan</label>
                                    <p
                                        className={cn(
                                            'mt-1 text-lg font-semibold',
                                            profitMargin > 20
                                                ? 'text-green-600 dark:text-green-400'
                                                : profitMargin > 10
                                                  ? 'text-yellow-600 dark:text-yellow-400'
                                                  : 'text-red-600 dark:text-red-400',
                                        )}
                                    >
                                        {formatPercentage(profitMargin)}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Bahan Baku Information */}
                        {produk.bahan_baku && produk.bahan_baku.length > 0 && (
                            <div className="mt-8">
                                <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-white">Komposisi Bahan Baku</h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                                    Nama Bahan
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                                    Jumlah
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                                    Harga per Unit
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                                    Total Biaya
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                            {produk.bahan_baku.map((bahan) => (
                                                <tr key={bahan.bahan_baku_id} className="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <Link
                                                            href={`/bahan-baku/${bahan.bahan_baku_id}`}
                                                            className="text-blue-600 hover:underline dark:text-blue-400"
                                                        >
                                                            {bahan.nama_bahan}
                                                        </Link>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                                        {formatNumber(bahan.pivot.jumlah_bahan_baku)} {bahan.satuan}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                                        {formatCurrency(bahan.harga_per_unit)}
                                                    </td>
                                                    <td className="px-6 py-4 font-medium whitespace-nowrap text-gray-900 dark:text-white">
                                                        {formatCurrency(safeMultiply(bahan.harga_per_unit, bahan.pivot.jumlah_bahan_baku))}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                        <tfoot className="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <td colSpan={3} className="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">
                                                    Total Biaya Bahan Baku:
                                                </td>
                                                <td className="px-6 py-4 font-bold whitespace-nowrap text-gray-900 dark:text-white">
                                                    {formatCurrency(totalMaterialCost)}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* Timestamps */}
                        <TimestampSection
                            createdAt={produk.created_at}
                            updatedAt={produk.updated_at}
                            createdBy={produk.created_by?.nama_lengkap}
                            updatedBy={produk.updated_by?.nama_lengkap}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
