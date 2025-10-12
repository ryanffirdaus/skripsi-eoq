import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { AlertTriangle, Building, Calendar, Edit, FileText, Package, User } from 'lucide-react';

interface Pemasok {
    pemasok_id: string;
    nama_pemasok: string;
    alamat?: string;
    telepon?: string;
}

interface BahanBaku {
    bahan_baku_id: string;
    nama: string;
    satuan: string;
}

interface Pembelian {
    pembelian_id: string;
    nomor_po: string;
    pemasok: Pemasok;
}

interface User {
    user_id: string;
    nama_lengkap: string;
}

interface ReturItem {
    retur_id: string;
    nomor_retur: string;
    qty_retur: number;
    status: string;
}

interface PenerimaanDetail {
    penerimaan_detail_id: string;
    bahan_baku_id: string;
    bahan_baku: BahanBaku;
    qty_dipesan: number;
    qty_received: number;
    qty_rejected: number;
    harga_satuan: number;
    subtotal: number;
    catatan_qc?: string;
    retur_items: ReturItem[];
}

interface PenerimaanBahanBaku {
    penerimaan_id: string;
    nomor_dokumen: string;
    pembelian_id?: string;
    pembelian?: Pembelian;
    pemasok_id: string;
    pemasok?: Pemasok;
    tanggal_penerimaan: string;
    status: 'pending' | 'partial' | 'complete' | 'returned';
    total_item: number;
    total_qty_diterima: number;
    total_qty_rejected: number;
    catatan?: string;
    received_by: string;
    receivedBy?: User;
    checked_by?: string;
    checkedBy?: User;
    details: PenerimaanDetail[];
    created_at: string;
    updated_at: string;
}

interface ShowProps {
    penerimaan: PenerimaanBahanBaku;
}

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'partial':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'complete':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'returned':
            return 'bg-red-100 text-red-800 border-red-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getStatusText = (status: string) => {
    switch (status) {
        case 'pending':
            return 'Pending';
        case 'partial':
            return 'Sebagian';
        case 'complete':
            return 'Selesai';
        case 'returned':
            return 'Diretur';
        default:
            return status;
    }
};

export default function Show({ penerimaan }: ShowProps) {
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const formatDateTime = (dateString: string) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const totalNilaiPenerimaan = penerimaan.details.reduce((sum, detail) => sum + (detail.subtotal || 0), 0);

    return (
        <AppLayout>
            <Head title={`Detail Penerimaan - ${penerimaan.nomor_dokumen}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Detail Penerimaan</h1>
                        <p className="text-muted-foreground">{penerimaan.nomor_dokumen}</p>
                    </div>
                    <div className="flex items-center gap-4">
                        <Badge className={getStatusColor(penerimaan.status)}>{getStatusText(penerimaan.status)}</Badge>
                        <Button variant="outline" onClick={() => router.visit(`/penerimaan-bahan-baku/${penerimaan.penerimaan_id}/edit`)}>
                            <Edit className="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                    </div>
                </div>

                <Separator />
                <div className="space-y-6">
                    {/* Main Info Cards */}
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        {/* Informasi Umum */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <FileText className="h-5 w-5" />
                                    Informasi Umum
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <Package className="h-4 w-4 text-gray-400" />
                                    <div>
                                        <p className="text-sm text-gray-500">No. Dokumen</p>
                                        <p className="font-medium">{penerimaan.nomor_dokumen}</p>
                                    </div>
                                </div>
                                {penerimaan.pembelian && (
                                    <div className="flex items-center gap-3">
                                        <FileText className="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p className="text-sm text-gray-500">No. Pembelian</p>
                                            <p className="font-medium">{penerimaan.pembelian.nomor_po}</p>
                                        </div>
                                    </div>
                                )}
                                <div className="flex items-center gap-3">
                                    <Calendar className="h-4 w-4 text-gray-400" />
                                    <div>
                                        <p className="text-sm text-gray-500">Tanggal Penerimaan</p>
                                        <p className="font-medium">{formatDate(penerimaan.tanggal_penerimaan)}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <User className="h-4 w-4 text-gray-400" />
                                    <div>
                                        <p className="text-sm text-gray-500">Diterima Oleh</p>
                                        <p className="font-medium">{penerimaan.receivedBy?.nama_lengkap || '-'}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Informasi Pemasok */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Building className="h-5 w-5" />
                                    Informasi Pemasok
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-sm text-gray-500">Nama Pemasok</p>
                                    <p className="font-medium">{penerimaan.pemasok?.nama_pemasok || '-'}</p>
                                </div>
                                {penerimaan.pemasok?.alamat && (
                                    <div>
                                        <p className="text-sm text-gray-500">Alamat</p>
                                        <p className="font-medium">{penerimaan.pemasok.alamat}</p>
                                    </div>
                                )}
                                {penerimaan.pemasok?.telepon && (
                                    <div>
                                        <p className="text-sm text-gray-500">Telepon</p>
                                        <p className="font-medium">{penerimaan.pemasok.telepon}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Ringkasan */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Ringkasan Penerimaan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-6 md:grid-cols-4">
                                <div className="text-center">
                                    <p className="text-2xl font-bold text-blue-600">{penerimaan.total_item}</p>
                                    <p className="text-sm text-gray-500">Total Item</p>
                                </div>
                                <div className="text-center">
                                    <p className="text-2xl font-bold text-green-600">{penerimaan.total_qty_diterima}</p>
                                    <p className="text-sm text-gray-500">Qty Diterima</p>
                                </div>
                                <div className="text-center">
                                    <p className="text-2xl font-bold text-red-600">{penerimaan.total_qty_rejected}</p>
                                    <p className="text-sm text-gray-500">Qty Ditolak</p>
                                </div>
                                <div className="text-center">
                                    <p className="text-2xl font-bold text-gray-900">{formatCurrency(totalNilaiPenerimaan)}</p>
                                    <p className="text-sm text-gray-500">Total Nilai</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Detail Items */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Detail Item Diterima</CardTitle>
                            <CardDescription>Daftar bahan baku yang diterima dalam pengiriman ini</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full border-collapse">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="p-3 text-left font-medium">Bahan Baku</th>
                                            <th className="p-3 text-left font-medium">Satuan</th>
                                            <th className="p-3 text-right font-medium">Qty Dipesan</th>
                                            <th className="p-3 text-right font-medium">Qty Diterima</th>
                                            <th className="p-3 text-right font-medium">Qty Ditolak</th>
                                            <th className="p-3 text-right font-medium">Harga Satuan</th>
                                            <th className="p-3 text-right font-medium">Subtotal</th>
                                            <th className="p-3 text-left font-medium">Catatan QC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {penerimaan.details.map((detail) => (
                                            <tr key={detail.penerimaan_detail_id} className="border-b">
                                                <td className="p-3 font-medium">{detail.bahan_baku.nama}</td>
                                                <td className="p-3">{detail.bahan_baku.satuan}</td>
                                                <td className="p-3 text-right">{detail.qty_dipesan.toLocaleString()}</td>
                                                <td className="p-3 text-right font-medium text-green-600">{detail.qty_received.toLocaleString()}</td>
                                                <td className="p-3 text-right font-medium text-red-600">{detail.qty_rejected.toLocaleString()}</td>
                                                <td className="p-3 text-right">{formatCurrency(detail.harga_satuan)}</td>
                                                <td className="p-3 text-right font-medium">{formatCurrency(detail.subtotal)}</td>
                                                <td className="p-3">
                                                    {detail.catatan_qc && (
                                                        <div className="flex items-center gap-1">
                                                            <AlertTriangle className="h-4 w-4 text-yellow-500" />
                                                            <span className="text-sm">{detail.catatan_qc}</span>
                                                        </div>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Catatan */}
                    {penerimaan.catatan && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Catatan</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-gray-700">{penerimaan.catatan}</p>
                            </CardContent>
                        </Card>
                    )}

                    {/* Audit Trail */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Informasi Audit</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <p className="text-sm text-gray-500">Dibuat pada</p>
                                    <p className="font-medium">{formatDateTime(penerimaan.created_at)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Terakhir diperbarui</p>
                                    <p className="font-medium">{formatDateTime(penerimaan.updated_at)}</p>
                                </div>
                                {penerimaan.checkedBy && (
                                    <div>
                                        <p className="text-sm text-gray-500">Dicek oleh</p>
                                        <p className="font-medium">{penerimaan.checkedBy.nama_lengkap}</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
