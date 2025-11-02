import ShowPageTemplate from '@/components/templates/show-page-template';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { BreadcrumbItem } from '@/types';
import { Building2, Calendar, CreditCard, Package } from 'lucide-react';

interface Pemasok {
    pemasok_id: string;
    nama_pemasok: string;
    alamat?: string;
    telepon?: string;
    email?: string;
    narahubung?: string;
}

interface Pengadaan {
    pengadaan_id: string;
}

interface PembelianDetail {
    pembelian_detail_id: string;
    pengadaan_detail_id: string;
    jenis_barang: string;
    nama_item: string;
    satuan: string;
    qty_dipesan: number;
    qty_diterima: number;
    harga_satuan: number;
    total_harga: number;
    outstanding_qty: number;
    is_fully_received: boolean;
    persentase_diterima?: number;
}

interface TransaksiPembayaran {
    transaksi_pembayaran_id: string;
    jenis_pembayaran: 'dp' | 'termin' | 'pelunasan';
    tanggal_pembayaran: string;
    jumlah_pembayaran: number;
    metode_pembayaran: string;
    bukti_pembayaran?: string;
}

interface Pembelian extends Record<string, unknown> {
    pembelian_id: string;
    pengadaan?: Pengadaan;
    pemasok: Pemasok;
    tanggal_pembelian: string;
    tanggal_kirim_diharapkan?: string;
    total_biaya: number;
    status: string;
    status_label: string;
    metode_pembayaran?: 'tunai' | 'transfer' | 'termin';
    termin_pembayaran?: string;
    jumlah_dp?: number;
    total_dibayar?: number;
    sisa_pembayaran?: number;
    is_dp_paid?: boolean;
    is_fully_paid?: boolean;
    catatan?: string;
    can_edit: boolean;
    can_cancel: boolean;
    detail: PembelianDetail[];
    transaksi_pembayaran?: TransaksiPembayaran[];
    created_at: string;
    updated_at: string;
    created_by?: { user_id: string; nama_lengkap: string };
    updated_by?: { user_id: string; nama_lengkap: string };
}

interface Props {
    pembelian: Pembelian;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pembelian', href: '/pembelian' },
    { title: 'Detail Pembelian', href: '#' },
];

export default function Show({ pembelian }: Props) {
    const getStatusColor = (status: string) => {
        const statusColors: Record<string, string> = {
            draft: 'border-gray-200 bg-gray-50 text-gray-700',
            sent: 'border-blue-200 bg-blue-50 text-blue-700',
            confirmed: 'border-purple-200 bg-purple-50 text-purple-700',
            partially_received: 'border-orange-200 bg-orange-50 text-orange-700',
            fully_received: 'border-green-200 bg-green-50 text-green-700',
            cancelled: 'border-red-200 bg-red-50 text-red-700',
        };
        return statusColors[status] || 'border-gray-200 bg-gray-50 text-gray-700';
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

    const details = pembelian.detail || [];
    const totalQty = details.reduce((sum, d) => sum + (d.qty_dipesan || 0), 0);
    const totalReceived = details.reduce((sum, d) => sum + (d.qty_diterima || 0), 0);
    const progressPercent = totalQty > 0 ? (totalReceived / totalQty) * 100 : 0;

    const actions = [
        {
            label: 'Kembali',
            href: '/pembelian',
            variant: 'outline' as const,
        },
        ...(pembelian.can_edit
            ? [
                  {
                      label: 'Edit',
                      href: `/pembelian/${pembelian.pembelian_id}/edit`,
                      variant: 'default' as const,
                  },
              ]
            : []),
    ];

    return (
        <ShowPageTemplate
            title={`PO - ${pembelian.pembelian_id}`}
            pageTitle={`Detail Purchase Order ${pembelian.pembelian_id}`}
            breadcrumbs={breadcrumbs}
            subtitle={`Pemasok: ${pembelian.pemasok.nama_pemasok}`}
            badge={{
                label: pembelian.status_label,
                color: getStatusColor(pembelian.status),
            }}
            actions={actions}
        >
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Main Content */}
                <div className="space-y-6 lg:col-span-2">
                    {/* Overview Cards */}
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <Card>
                            <CardContent className="p-4">
                                <div className="flex items-center space-x-3">
                                    <Calendar className="h-5 w-5 text-blue-600" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Tanggal PO</p>
                                        <p className="text-sm font-semibold">{formatDate(pembelian.tanggal_pembelian)}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-4">
                                <div className="flex items-center space-x-3">
                                    <CreditCard className="h-5 w-5 text-green-600" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Total Biaya</p>
                                        <p className="text-sm font-semibold text-green-600">{formatCurrency(pembelian.total_biaya)}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-4">
                                <div className="flex items-center space-x-3">
                                    <Package className="h-5 w-5 text-orange-600" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Progress</p>
                                        <p className="text-sm font-semibold text-orange-600">{progressPercent.toFixed(0)}%</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Detail Pembelian */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Detail Item</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {details.length === 0 ? (
                                    <p className="text-center text-gray-500">Tidak ada item</p>
                                ) : (
                                    details.map((detail, index) => (
                                        <div key={detail.pembelian_detail_id} className="rounded-lg border p-4">
                                            <div className="flex items-center justify-between">
                                                <h4 className="font-medium">Item #{index + 1}</h4>
                                                <Badge
                                                    variant={detail.is_fully_received ? 'default' : 'secondary'}
                                                    className={
                                                        detail.is_fully_received ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                                    }
                                                >
                                                    {detail.is_fully_received ? 'Lengkap' : 'Sebagian'}
                                                </Badge>
                                            </div>

                                            <div className="mt-3 grid grid-cols-2 gap-4 md:grid-cols-4">
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Nama Item</p>
                                                    <p className="text-sm font-semibold">{detail.nama_item}</p>
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Qty Diminta</p>
                                                    <p className="text-sm font-semibold">
                                                        {detail.qty_dipesan} {detail.satuan}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Qty Diterima</p>
                                                    <p className="text-sm font-semibold text-green-600">
                                                        {detail.qty_diterima} {detail.satuan}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Harga Satuan</p>
                                                    <p className="text-sm font-semibold">{formatCurrency(detail.harga_satuan)}</p>
                                                </div>
                                            </div>

                                            <div className="mt-3 border-t pt-3">
                                                <div className="flex justify-between text-sm">
                                                    <span className="font-medium text-gray-600">Total:</span>
                                                    <span className="font-semibold text-gray-900">{formatCurrency(detail.total_harga)}</span>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}

                                {details.length > 0 && (
                                    <>
                                        <Separator />
                                        <div className="flex justify-end">
                                            <div className="text-right">
                                                <p className="text-sm text-gray-600">Total Keseluruhan:</p>
                                                <p className="text-lg font-bold text-gray-900">{formatCurrency(pembelian.total_biaya)}</p>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Payment Information */}
                    {pembelian.metode_pembayaran && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Informasi Pembayaran</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Metode Pembayaran</p>
                                        <p className="capitalize">{pembelian.metode_pembayaran}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Total Dibayar</p>
                                        <p className="font-semibold text-green-600">{formatCurrency(pembelian.total_dibayar || 0)}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Sisa Pembayaran</p>
                                        <p className="font-semibold text-orange-600">{formatCurrency(pembelian.sisa_pembayaran || 0)}</p>
                                    </div>
                                </div>

                                {pembelian.metode_pembayaran === 'termin' && pembelian.jumlah_dp && (
                                    <div className="rounded-lg bg-blue-50 p-3">
                                        <p className="text-sm font-medium text-blue-900">Down Payment (DP)</p>
                                        <p className="text-lg font-bold text-blue-700">{formatCurrency(pembelian.jumlah_dp)}</p>
                                    </div>
                                )}

                                {pembelian.transaksi_pembayaran && pembelian.transaksi_pembayaran.length > 0 && (
                                    <div className="border-t pt-4">
                                        <h4 className="mb-3 font-medium">Riwayat Transaksi</h4>
                                        <div className="space-y-2">
                                            {pembelian.transaksi_pembayaran.map((t) => (
                                                <div
                                                    key={t.transaksi_pembayaran_id}
                                                    className="flex items-center justify-between rounded-lg border p-3"
                                                >
                                                    <div>
                                                        <Badge
                                                            variant="outline"
                                                            className={
                                                                t.jenis_pembayaran === 'dp'
                                                                    ? 'bg-blue-50 text-blue-700'
                                                                    : t.jenis_pembayaran === 'termin'
                                                                      ? 'bg-purple-50 text-purple-700'
                                                                      : 'bg-green-50 text-green-700'
                                                            }
                                                        >
                                                            {t.jenis_pembayaran === 'dp'
                                                                ? 'DP'
                                                                : t.jenis_pembayaran === 'termin'
                                                                  ? 'Termin'
                                                                  : 'Pelunasan'}
                                                        </Badge>
                                                        <span className="ml-2 text-sm">{formatDate(t.tanggal_pembayaran)}</span>
                                                    </div>
                                                    <span className="font-semibold">{formatCurrency(t.jumlah_pembayaran)}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Sidebar */}
                <div className="space-y-6">
                    {/* Pemasok Card */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building2 className="h-5 w-5" />
                                Pemasok
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div>
                                <p className="text-sm font-medium text-gray-500">Nama</p>
                                <p className="font-semibold">{pembelian.pemasok.nama_pemasok}</p>
                            </div>

                            {pembelian.pemasok.alamat && (
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Alamat</p>
                                    <p className="text-sm">{pembelian.pemasok.alamat}</p>
                                </div>
                            )}

                            {pembelian.pemasok.telepon && (
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Telepon</p>
                                    <p className="text-sm">{pembelian.pemasok.telepon}</p>
                                </div>
                            )}

                            {pembelian.pemasok.email && (
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Email</p>
                                    <p className="text-sm">{pembelian.pemasok.email}</p>
                                </div>
                            )}

                            {pembelian.pemasok.narahubung && (
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Narahubung</p>
                                    <p className="text-sm">{pembelian.pemasok.narahubung}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Audit Trail */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Riwayat</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div>
                                <p className="font-medium text-gray-500">Dibuat</p>
                                <p>{formatDate(pembelian.created_at)}</p>
                                {pembelian.created_by && <p className="text-xs text-gray-500">oleh {pembelian.created_by.nama_lengkap}</p>}
                            </div>

                            <Separator />

                            <div>
                                <p className="font-medium text-gray-500">Diperbarui</p>
                                <p>{formatDate(pembelian.updated_at)}</p>
                                {pembelian.updated_by && <p className="text-xs text-gray-500">oleh {pembelian.updated_by.nama_lengkap}</p>}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </ShowPageTemplate>
    );
}
