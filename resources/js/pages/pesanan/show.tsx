import TimestampSection from '@/components/timestamp-section';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { colors } from '@/lib/colors';
import { formatCurrency, formatDate, formatDateTime } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface UserRef {
    user_id: string;
    nama_lengkap: string;
}

interface Produk {
    produk_id: string;
    nama_produk: string;
    satuan_produk: string;
    pivot: {
        jumlah_produk: number;
        harga_satuan: number;
        subtotal: number;
    };
}

interface Pelanggan {
    pelanggan_id: string;
    nama_pelanggan: string;
    email_pelanggan: string;
    nomor_telepon: string;
    alamat_pembayaran: string;
    alamat_pengiriman: string;
}

interface Pesanan {
    pesanan_id: string;
    pelanggan_id: string;
    tanggal_pemesanan: string;
    total_harga: number;
    status: 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
    pelanggan: Pelanggan;
    produk: Produk[];
    created_at: string;
    updated_at: string;
    created_by?: UserRef;
    updated_by?: UserRef;
}

interface Props {
    pesanan: Pesanan;
}

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    confirmed: 'bg-blue-100 text-blue-800 border-blue-200',
    processing: 'bg-purple-100 text-purple-800 border-purple-200',
    shipped: 'bg-indigo-100 text-indigo-800 border-indigo-200',
    delivered: 'bg-green-100 text-green-800 border-green-200',
    cancelled: 'bg-red-100 text-red-800 border-red-200',
};

const statusLabels = {
    pending: 'Pending',
    confirmed: 'Dikonfirmasi',
    processing: 'Diproses',
    shipped: 'Dikirim',
    delivered: 'Diterima',
    cancelled: 'Dibatalkan',
};

export default function Show({ pesanan }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Pesanan',
            href: '/pesanan',
        },
        {
            title: `View ${pesanan.pesanan_id}`,
            href: `/pesanan/${pesanan.pesanan_id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`View Pesanan ${pesanan.pesanan_id}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className={colors.card.base}>
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Pesanan {pesanan.pesanan_id}</h1>
                                <p className={cn('mt-1', colors.text.secondary)}>Dibuat pada {formatDateTime(pesanan.created_at)}</p>
                            </div>
                            <div className="flex gap-3">
                                <Link href={`/pesanan/${pesanan.pesanan_id}/edit`}>
                                    <Button variant="outline">Edit Pesanan</Button>
                                </Link>
                                <Link href="/pesanan">
                                    <Button variant="outline">Kembali</Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="p-6">
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            {/* Status and Basic Info */}
                            <div className="space-y-4">
                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Status</label>
                                    <div className="mt-1">
                                        <span
                                            className={cn(
                                                'inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium',
                                                statusColors[pesanan.status],
                                            )}
                                        >
                                            {statusLabels[pesanan.status]}
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Tanggal Pemesanan</label>
                                    <p className={cn('mt-1', colors.text.primary)}>
                                        {formatDate(pesanan.tanggal_pemesanan, {
                                            weekday: 'long',
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                        })}
                                    </p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Total Harga</label>
                                    <p className={cn('mt-1 text-2xl font-semibold text-green-600')}>{formatCurrency(pesanan.total_harga)}</p>
                                </div>
                            </div>

                            {/* Customer Info */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Informasi Pelanggan</h3>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Nama</label>
                                    <p className={cn('mt-1', colors.text.primary)}>{pesanan.pelanggan.nama_pelanggan}</p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Email</label>
                                    <p className={cn('mt-1', colors.text.primary)}>{pesanan.pelanggan.email_pelanggan}</p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Nomor Telepon</label>
                                    <p className={cn('mt-1', colors.text.primary)}>{pesanan.pelanggan.nomor_telepon}</p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Alamat Pembayaran</label>
                                    <p className={cn('mt-1', colors.text.primary)}>{pesanan.pelanggan.alamat_pembayaran}</p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Alamat Pengiriman</label>
                                    <p className={cn('mt-1', colors.text.primary)}>{pesanan.pelanggan.alamat_pengiriman}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Products */}
                <div className={colors.card.base}>
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Produk Dipesan ({pesanan.produk.length} item)</h2>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className={cn(colors.background.secondary)}>
                                <tr>
                                    <th className={cn('px-6 py-3 text-left text-xs font-medium tracking-wider uppercase', colors.text.secondary)}>
                                        Produk
                                    </th>
                                    <th className={cn('px-6 py-3 text-left text-xs font-medium tracking-wider uppercase', colors.text.secondary)}>
                                        Harga Satuan
                                    </th>
                                    <th className={cn('px-6 py-3 text-left text-xs font-medium tracking-wider uppercase', colors.text.secondary)}>
                                        Jumlah
                                    </th>
                                    <th className={cn('px-6 py-3 text-left text-xs font-medium tracking-wider uppercase', colors.text.secondary)}>
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody className={cn('divide-y divide-gray-200 dark:divide-gray-700', colors.background.primary)}>
                                {pesanan.produk.map((produk) => (
                                    <tr key={produk.produk_id} className={colors.hover.primary}>
                                        <td className={cn('px-6 py-4 text-sm', colors.text.primary)}>
                                            <div>
                                                <div className="font-medium">{produk.nama_produk}</div>
                                                <div className={colors.text.secondary}>ID: {produk.produk_id}</div>
                                            </div>
                                        </td>
                                        <td className={cn('px-6 py-4 text-sm', colors.text.primary)}>{formatCurrency(produk.pivot.harga_satuan)}</td>
                                        <td className={cn('px-6 py-4 text-sm', colors.text.primary)}>
                                            {produk.pivot.jumlah_produk} {produk.satuan_produk}
                                        </td>
                                        <td className={cn('px-6 py-4 text-sm font-medium', colors.text.primary)}>
                                            {formatCurrency(produk.pivot.subtotal)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Total Summary */}
                    <div className="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div className="flex justify-end">
                            <div className="w-64">
                                <div className="flex items-center justify-between text-lg font-semibold text-gray-900 dark:text-white">
                                    <span>Total:</span>
                                    <span>{formatCurrency(pesanan.total_harga)}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Timestamps */}
                    <div className="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        <TimestampSection
                            createdAt={pesanan.created_at}
                            updatedAt={pesanan.updated_at}
                            createdBy={pesanan.created_by?.nama_lengkap}
                            updatedBy={pesanan.updated_by?.nama_lengkap}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
