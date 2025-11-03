import ShowPageTemplate from '@/components/templates/show-page-template';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { BreadcrumbItem } from '@/types';
import {
    AlertTriangle,
    Building2,
    Calendar,
    Calendar as CalendarIcon,
    CheckCircle,
    Clock,
    CreditCard,
    FileText,
    Info,
    Mail,
    Package,
    Phone,
    ShoppingCart,
    Star,
    TrendingUp,
    User,
    Users,
} from 'lucide-react';

interface Pemasok {
    pemasok_id: string;
    nama_pemasok: string;
    narahubung: string;
    telepon: string;
    email?: string;
}

interface Pesanan {
    pesanan_id: string;
    tanggal_pemesanan: string;
    total_harga: number;
    pelanggan?: {
        nama_pelanggan: string;
    };
}

interface PengadaanDetail {
    pengadaan_detail_id: string;
    item_type: 'bahan_baku' | 'produk';
    item_id: string;
    nama_item: string;
    satuan: string;
    qty_diminta: number;
    qty_disetujui?: number;
    qty_diterima?: number;
    harga_satuan: number;
    total_harga: number;
    catatan?: string;
    outstanding_qty: number;
    is_fully_received: boolean;
}

interface Pengadaan {
    pengadaan_id: string;
    pemasok_id: string;
    jenis_pengadaan: string;
    pesanan_id?: string;
    tanggal_pengadaan: string;
    tanggal_dibutuhkan: string;
    tanggal_delivery?: string;
    total_biaya: number;
    status: string;
    status_label: string;
    prioritas: string;
    prioritas_label: string;
    alasan_pengadaan?: string;
    catatan?: string;
    alasan_penolakan?: string;
    rejected_by?: string;
    rejected_at?: string;
    pemasok: Pemasok;
    pesanan?: Pesanan;
    detail: PengadaanDetail[];
    can_edit: boolean;
    can_cancel: boolean;
    created_at: string;
    updated_at: string;
}

interface Props {
    pengadaan: Pengadaan;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengadaan', href: '/pengadaan' },
    { title: 'Detail Pengadaan', href: '#' },
];

export default function Show({ pengadaan }: Props) {
    const getStatusColor = (status: string) => {
        const statusColors: Record<string, string> = {
            draft: 'border-gray-200 bg-gray-50 text-gray-700',
            pending: 'border-yellow-200 bg-yellow-50 text-yellow-700',
            approved: 'border-blue-200 bg-blue-50 text-blue-700',
            rejected: 'border-red-200 bg-red-50 text-red-700',
            cancelled: 'border-gray-300 bg-gray-100 text-gray-800',
            po_sent: 'border-purple-200 bg-purple-50 text-purple-700',
            partial_received: 'border-orange-200 bg-orange-50 text-orange-700',
            received: 'border-green-200 bg-green-50 text-green-700',
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

    const formatDateTime = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getProgressPercentage = (item: PengadaanDetail) => {
        const received = item.qty_diterima || 0;
        const requested = item.qty_diminta;
        return Math.round((received / requested) * 100);
    };

    const getOverallProgress = () => {
        const totalItems = pengadaan.detail.length;
        const completedItems = pengadaan.detail.filter((item) => item.is_fully_received).length;
        return Math.round((completedItems / totalItems) * 100);
    };

    const actions = [
        {
            label: 'Kembali ke Daftar',
            href: '/pengadaan',
            variant: 'outline' as const,
        },
        ...(pengadaan.status === 'approved'
            ? [
                  {
                      label: 'Buat Purchase Order',
                      href: `/pembelian/create/from-pengadaan/${pengadaan.pengadaan_id}`,
                      variant: 'default' as const,
                  },
              ]
            : []),
        ...(pengadaan.can_edit
            ? [
                  {
                      label: 'Edit Pengadaan',
                      href: `/pengadaan/${pengadaan.pengadaan_id}/edit`,
                      variant: 'default' as const,
                  },
              ]
            : []),
    ];

    return (
        <ShowPageTemplate
            title={`Pengadaan ${pengadaan.pengadaan_id}`}
            pageTitle={`Detail Pengadaan ${pengadaan.pengadaan_id}`}
            breadcrumbs={breadcrumbs}
            subtitle={`${pengadaan.jenis_pengadaan.toUpperCase()} - ${pengadaan.pemasok.nama_pemasok}`}
            badge={{
                label: `${pengadaan.status_label} • ${pengadaan.prioritas_label}`,
                color: getStatusColor(pengadaan.status),
            }}
            actions={actions}
        >
            <div className="space-y-6">
                {/* Status Overview Cards */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center space-x-2">
                                <ShoppingCart className="h-5 w-5 text-blue-600" />
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Status</p>
                                    <p className="text-lg font-semibold">{pengadaan.status_label}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center space-x-2">
                                <Star className="h-5 w-5 text-yellow-600" />
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Prioritas</p>
                                    <p className="text-lg font-semibold">{pengadaan.prioritas_label}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center space-x-2">
                                <Package className="h-5 w-5 text-green-600" />
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Total Item</p>
                                    <p className="text-lg font-semibold">{pengadaan.detail.length}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center space-x-2">
                                <TrendingUp className="h-5 w-5 text-purple-600" />
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Progress</p>
                                    <p className="text-lg font-semibold">{getOverallProgress()}%</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Content Grid */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Informasi Dasar */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Info className="mr-2 h-5 w-5" />
                                Informasi Dasar
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <div className="flex items-center text-sm font-medium text-gray-600">
                                        <Calendar className="mr-2 h-4 w-4" />
                                        Tanggal Pengadaan
                                    </div>
                                    <p className="text-sm">{formatDate(pengadaan.tanggal_pengadaan)}</p>
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center text-sm font-medium text-gray-600">
                                        <Clock className="mr-2 h-4 w-4" />
                                        Tanggal Dibutuhkan
                                    </div>
                                    <p className="text-sm">{formatDate(pengadaan.tanggal_dibutuhkan)}</p>
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center text-sm font-medium text-gray-600">
                                        <Package className="mr-2 h-4 w-4" />
                                        Jenis Pengadaan
                                    </div>
                                    <Badge variant="outline" className="text-sm font-medium">
                                        {pengadaan.jenis_pengadaan.toUpperCase()}
                                    </Badge>
                                </div>

                                {pengadaan.tanggal_delivery && (
                                    <div className="space-y-2">
                                        <div className="flex items-center text-sm font-medium text-gray-600">
                                            <CalendarIcon className="mr-2 h-4 w-4" />
                                            Tanggal Delivery
                                        </div>
                                        <p className="text-sm">{formatDate(pengadaan.tanggal_delivery)}</p>
                                    </div>
                                )}
                            </div>

                            {(pengadaan.alasan_pengadaan || pengadaan.catatan) && (
                                <>
                                    <Separator />

                                    {pengadaan.alasan_pengadaan && (
                                        <div className="space-y-2">
                                            <div className="flex items-center text-sm font-medium text-gray-600">
                                                <AlertTriangle className="mr-2 h-4 w-4" />
                                                Alasan Pengadaan
                                            </div>
                                            <p className="rounded-md bg-gray-50 p-3 text-sm text-gray-700">{pengadaan.alasan_pengadaan}</p>
                                        </div>
                                    )}

                                    {pengadaan.catatan && (
                                        <div className="space-y-2">
                                            <div className="flex items-center text-sm font-medium text-gray-600">
                                                <FileText className="mr-2 h-4 w-4" />
                                                Catatan
                                            </div>
                                            <p className="rounded-md bg-gray-50 p-3 text-sm text-gray-700">{pengadaan.catatan}</p>
                                        </div>
                                    )}
                                </>
                            )}

                            <Separator />

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <div className="flex items-center text-sm font-medium text-gray-600">
                                        <User className="mr-2 h-4 w-4" />
                                        Dibuat
                                    </div>
                                    <p className="text-sm">{formatDateTime(pengadaan.created_at)}</p>
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center text-sm font-medium text-gray-600">
                                        <User className="mr-2 h-4 w-4" />
                                        Diupdate
                                    </div>
                                    <p className="text-sm">{formatDateTime(pengadaan.updated_at)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Total Biaya */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <CreditCard className="mr-2 h-5 w-5" />
                                Total Biaya
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 text-center">
                                <div className="text-3xl font-bold text-green-600">{formatCurrency(pengadaan.total_biaya)}</div>
                                <p className="text-sm text-gray-600">Total keseluruhan pengadaan</p>
                                <div className="border-t pt-4">
                                    <div className="text-sm text-gray-600">Progress Penerimaan: {getOverallProgress()}%</div>
                                    <div className="mt-2 h-2 w-full rounded-full bg-gray-200">
                                        <div className="h-2 rounded-full bg-green-500" style={{ width: `${getOverallProgress()}%` }} />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Informasi Pemasok */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <Building2 className="mr-2 h-5 w-5" />
                            Informasi Pemasok
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div className="space-y-2">
                                <div className="flex items-center text-sm font-medium text-gray-600">
                                    <Building2 className="mr-2 h-4 w-4" />
                                    Nama Pemasok
                                </div>
                                <p className="text-sm font-medium">{pengadaan.pemasok.nama_pemasok}</p>
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center text-sm font-medium text-gray-600">
                                    <User className="mr-2 h-4 w-4" />
                                    Narahubung
                                </div>
                                <p className="text-sm">{pengadaan.pemasok.narahubung}</p>
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center text-sm font-medium text-gray-600">
                                    <Phone className="mr-2 h-4 w-4" />
                                    Telepon
                                </div>
                                <p className="text-sm">{pengadaan.pemasok.telepon}</p>
                            </div>

                            {pengadaan.pemasok.email && (
                                <div className="space-y-2">
                                    <div className="flex items-center text-sm font-medium text-gray-600">
                                        <Mail className="mr-2 h-4 w-4" />
                                        Email
                                    </div>
                                    <p className="text-sm">{pengadaan.pemasok.email}</p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Informasi Pesanan (jika ada) */}
                {pengadaan.pesanan && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Users className="mr-2 h-5 w-5" />
                                Informasi Pesanan Terkait
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                                <div className="space-y-2">
                                    <div className="flex items-center text-sm font-medium text-gray-600">
                                        <FileText className="mr-2 h-4 w-4" />
                                        ID Pesanan
                                    </div>
                                    <p className="text-sm font-medium">{pengadaan.pesanan.pesanan_id}</p>
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center text-sm font-medium text-gray-600">
                                        <Calendar className="mr-2 h-4 w-4" />
                                        Tanggal Pemesanan
                                    </div>
                                    <p className="text-sm">{formatDate(pengadaan.pesanan.tanggal_pemesanan)}</p>
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center text-sm font-medium text-gray-600">
                                        <CreditCard className="mr-2 h-4 w-4" />
                                        Total Harga Pesanan
                                    </div>
                                    <p className="text-sm font-medium">{formatCurrency(pengadaan.pesanan.total_harga)}</p>
                                </div>

                                {pengadaan.pesanan.pelanggan && (
                                    <div className="space-y-2">
                                        <div className="flex items-center text-sm font-medium text-gray-600">
                                            <User className="mr-2 h-4 w-4" />
                                            Nama Pelanggan
                                        </div>
                                        <p className="text-sm">{pengadaan.pesanan.pelanggan.nama_pelanggan}</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Detail Item Pengadaan */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <Package className="mr-2 h-5 w-5" />
                            Detail Item Pengadaan
                        </CardTitle>
                        <CardDescription>Daftar item yang diminta dalam pengadaan ini</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {pengadaan.detail.map((item, index) => (
                                <div key={index} className="rounded-lg border bg-gray-50 p-4">
                                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-6">
                                        <div className="lg:col-span-2">
                                            <div className="space-y-2">
                                                <div className="flex items-center space-x-2">
                                                    <Badge variant="outline" className="text-xs">
                                                        {item.item_type === 'bahan_baku' ? 'Bahan Baku' : 'Produk'}
                                                    </Badge>
                                                    {item.is_fully_received && (
                                                        <Badge className="border-green-200 bg-green-100 text-green-800">
                                                            <CheckCircle className="mr-1 h-3 w-3" />
                                                            Selesai
                                                        </Badge>
                                                    )}
                                                </div>
                                                <h4 className="font-medium text-gray-900">{item.nama_item}</h4>
                                            </div>
                                        </div>

                                        <div className="text-center">
                                            <div className="text-sm font-medium text-gray-600">Qty Diminta</div>
                                            <div className="text-lg font-semibold text-gray-900">
                                                {item.qty_diminta} {item.satuan}
                                            </div>
                                        </div>

                                        <div className="text-center">
                                            <div className="text-sm font-medium text-gray-600">Harga Satuan</div>
                                            <div className="text-sm text-gray-900">{formatCurrency(item.harga_satuan)}</div>
                                        </div>

                                        <div className="text-center">
                                            <div className="text-sm font-medium text-gray-600">Total</div>
                                            <div className="text-lg font-semibold text-green-600">{formatCurrency(item.total_harga)}</div>
                                        </div>

                                        <div className="text-center">
                                            <div className="text-sm font-medium text-gray-600">Progress</div>
                                            <div className="text-sm font-semibold">{getProgressPercentage(item)}%</div>
                                            <div className="mt-2 h-2 w-full rounded-full bg-gray-200">
                                                <div className="h-2 rounded-full bg-green-500" style={{ width: `${getProgressPercentage(item)}%` }} />
                                            </div>
                                        </div>
                                    </div>

                                    {/* Detail Progress */}
                                    {(item.qty_disetujui || item.qty_diterima) && (
                                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                                            <div className="rounded border border-blue-200 bg-blue-50 p-3 text-center">
                                                <div className="text-xs font-medium text-blue-600">Qty Disetujui</div>
                                                <div className="font-semibold text-blue-800">
                                                    {item.qty_disetujui || 0} {item.satuan}
                                                </div>
                                            </div>

                                            <div className="rounded border border-green-200 bg-green-50 p-3 text-center">
                                                <div className="text-xs font-medium text-green-600">Qty Diterima</div>
                                                <div className="font-semibold text-green-800">
                                                    {item.qty_diterima || 0} {item.satuan}
                                                </div>
                                            </div>

                                            <div className="rounded border border-yellow-200 bg-yellow-50 p-3 text-center">
                                                <div className="text-xs font-medium text-yellow-600">Outstanding</div>
                                                <div className="font-semibold text-yellow-800">
                                                    {item.outstanding_qty} {item.satuan}
                                                </div>
                                            </div>

                                            <div className="rounded border border-gray-200 bg-gray-50 p-3 text-center">
                                                <div className="text-xs font-medium text-gray-600">Status</div>
                                                <div className="font-semibold">
                                                    {item.is_fully_received ? (
                                                        <span className="text-green-600">Complete</span>
                                                    ) : item.qty_diterima ? (
                                                        <span className="text-yellow-600">Partial</span>
                                                    ) : (
                                                        <span className="text-gray-600">Pending</span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Catatan Item */}
                                    {item.catatan && (
                                        <div className="mt-4 rounded-md border border-yellow-200 bg-yellow-50 p-3">
                                            <div className="mb-1 text-sm font-medium text-yellow-800">Catatan:</div>
                                            <p className="text-sm text-yellow-700">{item.catatan}</p>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>

                        {/* Summary */}
                        <Separator className="my-6" />
                        <div className="rounded-lg bg-gradient-to-r from-gray-50 to-gray-100 p-6">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-6">
                                    <div className="text-center">
                                        <div className="text-sm text-gray-600">Total Item</div>
                                        <div className="text-xl font-bold text-gray-900">{pengadaan.detail.length}</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-sm text-gray-600">Item Selesai</div>
                                        <div className="text-xl font-bold text-green-600">
                                            {pengadaan.detail.filter((item) => item.is_fully_received).length}
                                        </div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-sm text-gray-600">Progress</div>
                                        <div className="text-xl font-bold text-blue-600">{getOverallProgress()}%</div>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className="text-sm text-gray-600">Total Biaya Pengadaan</div>
                                    <div className="text-3xl font-bold text-green-600">{formatCurrency(pengadaan.total_biaya)}</div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </ShowPageTemplate>
    );
}
