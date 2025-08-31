import TimestampSection from '@/components/timestamp-section';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency, formatDate } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Pelanggan {
    nama_pelanggan: string;
    alamat_pelanggan: string;
    kota_pelanggan: string;
    telepon_pelanggan: string;
}

interface Pesanan {
    pesanan_id: string;
    tanggal_pesanan: string;
    total_harga: number;
    status: string;
    pelanggan: Pelanggan;
}

interface User {
    user_id: string;
    nama_lengkap: string;
}

interface Pengiriman {
    pengiriman_id: string;
    pesanan_id: string;
    nomor_resi?: string;
    kurir: string;
    jenis_layanan: string;
    biaya_pengiriman: number;
    estimasi_hari: number;
    status: string;
    status_label: string;
    tanggal_kirim?: string;
    tanggal_diterima?: string;
    catatan?: string;
    pesanan: Pesanan;
    created_by?: string;
    updated_by?: string;
    created_at?: string;
    updated_at?: string;
    createdBy?: User;
    updatedBy?: User;
}

interface Props {
    pengiriman: Pengiriman;
}

export default function Show({ pengiriman }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pengiriman', href: '/pengiriman' },
        { title: pengiriman.pengiriman_id, href: `/pengiriman/${pengiriman.pengiriman_id}` },
    ];

    const getStatusColor = (status: string) => {
        const colors = {
            pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            shipped: 'bg-blue-100 text-blue-800 border-blue-200',
            delivered: 'bg-green-100 text-green-800 border-green-200',
            cancelled: 'bg-red-100 text-red-800 border-red-200',
        };
        return colors[status as keyof typeof colors] || colors.pending;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Pengiriman ${pengiriman.pengiriman_id}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Detail Pengiriman</h1>
                        <p className="text-muted-foreground">Informasi lengkap pengiriman {pengiriman.pengiriman_id}</p>
                    </div>
                    <div className="flex space-x-2">
                        <Button variant="outline" asChild>
                            <Link href={`/pengiriman/${pengiriman.pengiriman_id}/edit`}>Edit</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/pengiriman">Kembali</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Informasi Pengiriman */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Informasi Pengiriman</CardTitle>
                            <CardDescription>Detail informasi pengiriman dan tracking</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">ID Pengiriman</div>
                                    <div className="mt-1 font-mono">{pengiriman.pengiriman_id}</div>
                                </div>
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Status</div>
                                    <div className="mt-1">
                                        <span
                                            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium ${getStatusColor(pengiriman.status)}`}
                                        >
                                            {pengiriman.status_label}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Kurir</div>
                                    <div className="mt-1">{pengiriman.kurir}</div>
                                </div>
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Jenis Layanan</div>
                                    <div className="mt-1">{pengiriman.jenis_layanan}</div>
                                </div>
                            </div>

                            {pengiriman.nomor_resi && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Nomor Resi</div>
                                    <div className="mt-1 font-mono text-lg font-semibold">{pengiriman.nomor_resi}</div>
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Biaya Pengiriman</div>
                                    <div className="mt-1 text-lg font-semibold text-green-600">{formatCurrency(pengiriman.biaya_pengiriman)}</div>
                                </div>
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Estimasi</div>
                                    <div className="mt-1">{pengiriman.estimasi_hari} hari</div>
                                </div>
                            </div>

                            {pengiriman.catatan && (
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Catatan</div>
                                    <div className="mt-1 text-sm text-gray-600">{pengiriman.catatan}</div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Informasi Pesanan */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Informasi Pesanan</CardTitle>
                            <CardDescription>Detail pesanan yang dikirim</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">ID Pesanan</div>
                                <div className="mt-1">
                                    <Link
                                        href={`/pesanan/${pengiriman.pesanan.pesanan_id}`}
                                        className="font-mono text-blue-600 hover:text-blue-800 hover:underline"
                                    >
                                        {pengiriman.pesanan.pesanan_id}
                                    </Link>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Tanggal Pesanan</div>
                                    <div className="mt-1">
                                        {formatDate(pengiriman.pesanan.tanggal_pesanan, {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                        })}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-sm font-medium text-muted-foreground">Total Harga</div>
                                    <div className="mt-1 text-lg font-semibold text-green-600">{formatCurrency(pengiriman.pesanan.total_harga)}</div>
                                </div>
                            </div>

                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Pelanggan</div>
                                <div className="mt-1">
                                    <div className="font-medium">{pengiriman.pesanan.pelanggan.nama_pelanggan}</div>
                                    <div className="mt-1 text-sm text-gray-600">{pengiriman.pesanan.pelanggan.alamat_pelanggan}</div>
                                    <div className="text-sm text-gray-600">{pengiriman.pesanan.pelanggan.kota_pelanggan}</div>
                                    <div className="text-sm text-gray-600">{pengiriman.pesanan.pelanggan.telepon_pelanggan}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Timeline Pengiriman */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Timeline Pengiriman</CardTitle>
                            <CardDescription>Riwayat status pengiriman</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {pengiriman.tanggal_diterima && (
                                    <div className="flex items-center space-x-4">
                                        <div className="flex-shrink-0">
                                            <div className="h-3 w-3 rounded-full bg-green-500"></div>
                                        </div>
                                        <div className="flex-1">
                                            <div className="font-medium">Paket Diterima</div>
                                            <div className="text-sm text-gray-600">
                                                {formatDate(pengiriman.tanggal_diterima, {
                                                    year: 'numeric',
                                                    month: 'long',
                                                    day: 'numeric',
                                                })}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {pengiriman.tanggal_kirim && (
                                    <div className="flex items-center space-x-4">
                                        <div className="flex-shrink-0">
                                            <div className="h-3 w-3 rounded-full bg-blue-500"></div>
                                        </div>
                                        <div className="flex-1">
                                            <div className="font-medium">Paket Dikirim</div>
                                            <div className="text-sm text-gray-600">
                                                {formatDate(pengiriman.tanggal_kirim, {
                                                    year: 'numeric',
                                                    month: 'long',
                                                    day: 'numeric',
                                                })}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div className="flex items-center space-x-4">
                                    <div className="flex-shrink-0">
                                        <div className="h-3 w-3 rounded-full bg-gray-300"></div>
                                    </div>
                                    <div className="flex-1">
                                        <div className="font-medium">Pengiriman Dibuat</div>
                                        <div className="text-sm text-gray-600">
                                            {formatDate(pengiriman.created_at!, {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                            })}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Timestamp Information */}
                    <Card className="lg:col-span-2">
                        <CardContent className="pt-6">
                            <TimestampSection
                                createdAt={pengiriman.created_at || ''}
                                updatedAt={pengiriman.updated_at || ''}
                                createdBy={pengiriman.createdBy?.nama_lengkap}
                                updatedBy={pengiriman.updatedBy?.nama_lengkap}
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
