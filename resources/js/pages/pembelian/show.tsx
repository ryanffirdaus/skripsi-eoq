import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Building2, Calendar, CheckCircle, CreditCard, Edit, Eye, FileText, Package, Truck, XCircle } from 'lucide-react';

interface Supplier {
    supplier_id: string;
    nama_supplier: string;
    alamat?: string;
    telepon?: string;
    email?: string;
    kontak_person?: string;
}

interface Pengadaan {
    pengadaan_id: string;
    nomor_pengadaan: string;
    tanggal_pengadaan: string;
}

interface PembelianDetail {
    detail_id: string;
    bahan_baku_id: string;
    nama_bahan: string;
    satuan: string;
    kuantitas: number;
    harga_satuan: number;
    subtotal: number;
    kuantitas_diterima: number;
    persentase_diterima: number;
}

interface Pembelian extends Record<string, unknown> {
    pembelian_id: string;
    pengadaan?: Pengadaan;
    nomor_po: string;
    supplier: Supplier;
    tanggal_pembelian: string;
    tanggal_jatuh_tempo?: string;
    total_biaya: number;
    status: string;
    status_label: string;
    metode_pembayaran?: string;
    catatan?: string;
    can_edit: boolean;
    can_cancel: boolean;
    can_receive: boolean;
    can_invoice: boolean;
    can_mark_paid: boolean;
    created_at: string;
    updated_at: string;
    details: PembelianDetail[];
}

interface Props {
    pembelian: Pembelian;
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

export default function Show({ pembelian, flash }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Pembelian', href: '/pembelian' },
        { title: pembelian.nomor_po, href: `/pembelian/${pembelian.pembelian_id}` },
    ];

    const getStatusColor = (status: string) => {
        const colors: Record<string, string> = {
            draft: 'bg-gray-100 text-gray-800',
            sent: 'bg-blue-100 text-blue-800',
            confirmed: 'bg-purple-100 text-purple-800',
            received: 'bg-green-100 text-green-800',
            invoiced: 'bg-yellow-100 text-yellow-800',
            paid: 'bg-emerald-100 text-emerald-800',
            cancelled: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const handleStatusUpdate = (action: string) => {
        if (confirm(`Apakah Anda yakin ingin ${action} Purchase Order ini?`)) {
            router.patch(`/pembelian/${pembelian.pembelian_id}/status`, {
                action: action,
            });
        }
    };

    const handleCancel = () => {
        if (confirm('Apakah Anda yakin ingin membatalkan Purchase Order ini? Tindakan ini tidak dapat dibatalkan.')) {
            router.delete(`/pembelian/${pembelian.pembelian_id}`);
        }
    };

    const totalDiterima = pembelian.details.reduce((sum, detail) => sum + detail.kuantitas_diterima, 0);
    const totalKuantitas = pembelian.details.reduce((sum, detail) => sum + detail.kuantitas, 0);
    const persentaseKeseluruhan = totalKuantitas > 0 ? (totalDiterima / totalKuantitas) * 100 : 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Purchase Order - ${pembelian.nomor_po}`} />

            <div className="space-y-6">
                {flash?.message && (
                    <Alert>
                        <AlertDescription>{flash.message}</AlertDescription>
                    </Alert>
                )}

                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{pembelian.nomor_po}</h1>
                        <p className="text-gray-600">Purchase Order Detail</p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Badge className={cn('text-sm', getStatusColor(pembelian.status))}>{pembelian.status_label}</Badge>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="flex flex-wrap gap-2">
                    {pembelian.can_edit && (
                        <Button onClick={() => router.visit(`/pembelian/${pembelian.pembelian_id}/edit`)} variant="outline">
                            <Edit className="mr-2 h-4 w-4" />
                            Edit PO
                        </Button>
                    )}

                    {pembelian.status === 'draft' && (
                        <Button onClick={() => handleStatusUpdate('send')} variant="default">
                            <Truck className="mr-2 h-4 w-4" />
                            Kirim PO
                        </Button>
                    )}

                    {pembelian.status === 'sent' && (
                        <Button onClick={() => handleStatusUpdate('confirm')} variant="default">
                            <CheckCircle className="mr-2 h-4 w-4" />
                            Konfirmasi Diterima
                        </Button>
                    )}

                    {pembelian.can_receive && (
                        <Button onClick={() => router.visit(`/pembelian/${pembelian.pembelian_id}/receive`)} variant="default">
                            <Package className="mr-2 h-4 w-4" />
                            Terima Barang
                        </Button>
                    )}

                    {pembelian.can_invoice && (
                        <Button onClick={() => handleStatusUpdate('invoice')} variant="default">
                            <FileText className="mr-2 h-4 w-4" />
                            Buat Invoice
                        </Button>
                    )}

                    {pembelian.can_mark_paid && (
                        <Button onClick={() => handleStatusUpdate('mark_paid')} variant="default">
                            <CreditCard className="mr-2 h-4 w-4" />
                            Tandai Lunas
                        </Button>
                    )}

                    {pembelian.can_cancel && (
                        <Button onClick={handleCancel} variant="destructive">
                            <XCircle className="mr-2 h-4 w-4" />
                            Batalkan PO
                        </Button>
                    )}
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Purchase Order Information */}
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <FileText className="mr-2 h-5 w-5" />
                                    Informasi Purchase Order
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {pembelian.pengadaan && (
                                    <div className="rounded-lg bg-blue-50 p-4">
                                        <p className="text-sm text-blue-800">
                                            <strong>Berdasarkan Pengadaan:</strong>{' '}
                                            <Button
                                                variant="link"
                                                className="h-auto p-0 text-blue-800 underline"
                                                onClick={() => router.visit(`/pengadaan/${pembelian.pengadaan?.pengadaan_id}`)}
                                            >
                                                {pembelian.pengadaan.nomor_pengadaan}
                                            </Button>{' '}
                                            ({formatDate(pembelian.pengadaan.tanggal_pengadaan)})
                                        </p>
                                    </div>
                                )}

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Nomor PO</p>
                                        <p className="font-semibold">{pembelian.nomor_po}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Status</p>
                                        <Badge className={cn('text-sm', getStatusColor(pembelian.status))}>{pembelian.status_label}</Badge>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Tanggal Pembelian</p>
                                        <p className="flex items-center">
                                            <Calendar className="mr-2 h-4 w-4 text-gray-400" />
                                            {formatDate(pembelian.tanggal_pembelian)}
                                        </p>
                                    </div>
                                    {pembelian.tanggal_jatuh_tempo && (
                                        <div>
                                            <p className="text-sm font-medium text-gray-500">Tanggal Jatuh Tempo</p>
                                            <p className="flex items-center">
                                                <Calendar className="mr-2 h-4 w-4 text-gray-400" />
                                                {formatDate(pembelian.tanggal_jatuh_tempo)}
                                            </p>
                                        </div>
                                    )}
                                    {pembelian.metode_pembayaran && (
                                        <div>
                                            <p className="text-sm font-medium text-gray-500">Metode Pembayaran</p>
                                            <p className="capitalize">{pembelian.metode_pembayaran}</p>
                                        </div>
                                    )}
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Total Biaya</p>
                                        <p className="text-lg font-bold text-green-600">{formatCurrency(pembelian.total_biaya)}</p>
                                    </div>
                                </div>

                                {pembelian.catatan && (
                                    <div>
                                        <p className="mb-2 text-sm font-medium text-gray-500">Catatan</p>
                                        <p className="rounded-lg bg-gray-50 p-3 text-sm">{pembelian.catatan}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Purchase Order Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Detail Pembelian</CardTitle>
                                {persentaseKeseluruhan > 0 && (
                                    <div className="text-sm text-gray-600">Progress penerimaan: {persentaseKeseluruhan.toFixed(1)}%</div>
                                )}
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {pembelian.details.map((detail, index) => (
                                        <div key={detail.detail_id} className="rounded-lg border p-4">
                                            <div className="mb-3 flex items-center justify-between">
                                                <h4 className="font-medium">Item #{index + 1}</h4>
                                                {detail.persentase_diterima > 0 && (
                                                    <Badge variant="secondary">{detail.persentase_diterima.toFixed(1)}% diterima</Badge>
                                                )}
                                            </div>

                                            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Nama Bahan</p>
                                                    <p>{detail.nama_bahan}</p>
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Kuantitas</p>
                                                    <p>
                                                        {detail.kuantitas} {detail.satuan}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Harga Satuan</p>
                                                    <p>{formatCurrency(detail.harga_satuan)}</p>
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Subtotal</p>
                                                    <p className="font-semibold">{formatCurrency(detail.subtotal)}</p>
                                                </div>
                                            </div>

                                            {detail.kuantitas_diterima > 0 && (
                                                <div className="mt-3 border-t pt-3">
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <p className="text-sm font-medium text-green-600">Diterima</p>
                                                            <p className="text-green-700">
                                                                {detail.kuantitas_diterima} {detail.satuan}
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <p className="text-sm font-medium text-orange-600">Sisa</p>
                                                            <p className="text-orange-700">
                                                                {detail.kuantitas - detail.kuantitas_diterima} {detail.satuan}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    ))}

                                    <Separator />

                                    <div className="flex justify-end">
                                        <div className="text-right">
                                            <p className="text-lg font-bold">Total: {formatCurrency(pembelian.total_biaya)}</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Supplier Information */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Building2 className="mr-2 h-5 w-5" />
                                    Informasi Supplier
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Nama Supplier</p>
                                    <p className="font-semibold">{pembelian.supplier.nama_supplier}</p>
                                </div>
                                {pembelian.supplier.alamat && (
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Alamat</p>
                                        <p className="text-sm">{pembelian.supplier.alamat}</p>
                                    </div>
                                )}
                                {pembelian.supplier.telepon && (
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Telepon</p>
                                        <p className="text-sm">{pembelian.supplier.telepon}</p>
                                    </div>
                                )}
                                {pembelian.supplier.email && (
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Email</p>
                                        <p className="text-sm">{pembelian.supplier.email}</p>
                                    </div>
                                )}
                                {pembelian.supplier.kontak_person && (
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Kontak Person</p>
                                        <p className="text-sm">{pembelian.supplier.kontak_person}</p>
                                    </div>
                                )}
                                <div className="border-t pt-3">
                                    <Button variant="outline" size="sm" onClick={() => router.visit(`/supplier/${pembelian.supplier.supplier_id}`)}>
                                        <Eye className="mr-2 h-4 w-4" />
                                        Lihat Detail Supplier
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        {/* History/Timeline could go here */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Riwayat</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <div className="text-sm">
                                    <p className="font-medium">Dibuat</p>
                                    <p className="text-gray-600">{formatDate(pembelian.created_at)}</p>
                                </div>
                                <div className="text-sm">
                                    <p className="font-medium">Terakhir Diupdate</p>
                                    <p className="text-gray-600">{formatDate(pembelian.updated_at)}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
