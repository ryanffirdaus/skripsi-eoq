import TimestampSection from '@/components/timestamp-section';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { colors } from '@/lib/colors';
import { formatCurrency, formatDate, formatNumber, safeAdd, safeDivide } from '@/lib/formatters';
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
    harga_jual: number;
    pivot: {
        pesanan_id: string;
        produk_id: string;
        jumlah: number;
        harga: number;
    };
}

interface Pesanan {
    pesanan_id: string;
    tanggal_pesanan: string;
    status: string;
    total_harga: number;
    produk?: Produk[];
}

interface Pelanggan {
    pelanggan_id: string;
    nama: string;
    email: string;
    telepon: string;
    alamat: string;
    tipe_pelanggan: 'company' | 'individual';
    created_at: string;
    updated_at: string;
    dibuat_oleh?: UserRef;
    diubah_oleh?: UserRef;
    pesanan?: Pesanan[];
}

interface Props {
    pelanggan: Pelanggan;
}

export default function Show({ pelanggan }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Pelanggan',
            href: '/pelanggan',
        },
        {
            title: `Detail ${pelanggan.nama}`,
            href: `/pelanggan/${pelanggan.pelanggan_id}`,
        },
    ];

    const getStatusColor = (status: string) => {
        switch (status.toLowerCase()) {
            case 'pending':
                return 'bg-yellow-100 text-yellow-800 border-yellow-200';
            case 'diproses':
                return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'dikirim':
                return 'bg-purple-100 text-purple-800 border-purple-200';
            case 'selesai':
                return 'bg-green-100 text-green-800 border-green-200';
            case 'dibatalkan':
                return 'bg-red-100 text-red-800 border-red-200';
            default:
                return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };

    const totalPesanan = pelanggan.pesanan?.length || 0;
    const totalNilaiPesanan = safeAdd(...(pelanggan.pesanan?.map((pesanan) => pesanan.total_harga) || []));
    const rataRataNilaiPesanan = safeDivide(totalNilaiPesanan, totalPesanan);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Pelanggan ${pelanggan.nama}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className={colors.card.base}>
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">{pelanggan.nama}</h1>
                                <p className={cn('mt-1', colors.text.secondary)}>Customer ID: {pelanggan.pelanggan_id}</p>
                                <div className="mt-2">
                                    <span
                                        className={cn(
                                            'inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium',
                                            pelanggan.tipe_pelanggan === 'company'
                                                ? 'border-blue-200 bg-blue-100 text-blue-800'
                                                : 'border-green-200 bg-green-100 text-green-800',
                                        )}
                                    >
                                        {pelanggan.tipe_pelanggan === 'company' ? 'Perusahaan' : 'Perorangan'}
                                    </span>
                                </div>
                            </div>
                            <div className="flex gap-3">
                                <Link href={`/pelanggan/${pelanggan.pelanggan_id}/edit`}>
                                    <Button variant="outline">Edit Pelanggan</Button>
                                </Link>
                                <Link href="/pelanggan">
                                    <Button variant="outline">Kembali</Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="p-6">
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            {/* Contact Information */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Informasi Kontak</h3>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Nama</label>
                                    <p className={cn('mt-1 text-lg', colors.text.primary)}>{pelanggan.nama}</p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Email</label>
                                    <p className={cn('mt-1', colors.text.primary)}>
                                        <a href={`mailto:${pelanggan.email}`} className="text-blue-600 hover:underline dark:text-blue-400">
                                            {pelanggan.email}
                                        </a>
                                    </p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Telepon</label>
                                    <p className={cn('mt-1', colors.text.primary)}>
                                        <a href={`tel:${pelanggan.telepon}`} className="text-blue-600 hover:underline dark:text-blue-400">
                                            {pelanggan.telepon}
                                        </a>
                                    </p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Alamat</label>
                                    <p className={cn('mt-1', colors.text.primary)}>{pelanggan.alamat}</p>
                                </div>
                            </div>

                            {/* Statistics */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Statistik Pesanan</h3>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Total Pesanan</label>
                                    <p className={cn('mt-1 text-lg font-semibold', colors.text.primary)}>{formatNumber(totalPesanan)} pesanan</p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Total Nilai Pesanan</label>
                                    <p className={cn('mt-1 text-lg font-semibold text-green-600 dark:text-green-400')}>
                                        {formatCurrency(totalNilaiPesanan)}
                                    </p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Rata-rata Nilai Pesanan</label>
                                    <p className={cn('mt-1 text-lg font-semibold text-blue-600 dark:text-blue-400')}>
                                        {formatCurrency(rataRataNilaiPesanan)}
                                    </p>
                                </div>

                                <div>
                                    <label className={cn('text-sm font-medium', colors.label.base)}>Customer ID</label>
                                    <p className={cn('mt-1 rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-800', colors.text.primary)}>
                                        {pelanggan.pelanggan_id}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Order History */}
                        {pelanggan.pesanan && pelanggan.pesanan.length > 0 && (
                            <div className="mt-8">
                                <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-white">Riwayat Pesanan ({totalPesanan})</h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                                    Pesanan ID
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                                    Tanggal
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                                    Total Harga
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                                    Aksi
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                            {pelanggan.pesanan.map((pesanan) => (
                                                <tr key={pesanan.pesanan_id} className="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="font-mono text-sm text-gray-900 dark:text-white">{pesanan.pesanan_id}</div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                                        {formatDate(pesanan.tanggal_pesanan, {
                                                            year: 'numeric',
                                                            month: 'short',
                                                            day: 'numeric',
                                                        })}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span
                                                            className={cn(
                                                                'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                                getStatusColor(pesanan.status),
                                                            )}
                                                        >
                                                            {pesanan.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 font-medium whitespace-nowrap text-gray-900 dark:text-white">
                                                        {formatCurrency(pesanan.total_harga)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <Link
                                                            href={`/pesanan/${pesanan.pesanan_id}`}
                                                            className="text-sm text-blue-600 hover:underline dark:text-blue-400"
                                                        >
                                                            Lihat Detail
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* Timestamps */}
                        <TimestampSection
                            createdAt={pelanggan.created_at}
                            updatedAt={pelanggan.updated_at}
                            createdBy={pelanggan.dibuat_oleh?.nama_lengkap}
                            updatedBy={pelanggan.diubah_oleh?.nama_lengkap}
                            createdLabel="Terdaftar"
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
