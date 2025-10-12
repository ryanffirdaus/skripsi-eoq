import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { AlertTriangle, Building, Package, Save, X } from 'lucide-react';
import React from 'react';

interface BahanBaku {
    bahan_baku_id: string;
    nama: string;
    satuan: string;
}

interface Pemasok {
    pemasok_id: string;
    nama_pemasok: string;
}

interface Pembelian {
    pembelian_id: string;
    nomor_po: string;
    pemasok: Pemasok;
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
    checked_by?: string;
    details: PenerimaanDetail[];
    created_at: string;
    updated_at: string;
}

interface EditProps {
    penerimaan: PenerimaanBahanBaku;
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

interface PenerimaanDetailForm {
    penerimaan_detail_id: string;
    qty_received: number;
    qty_accepted: number;
    qty_rejected: number;
    status_quality: 'pending' | 'passed' | 'failed' | 'partial';
    catatan_quality?: string;
}

interface FormData {
    nomor_dokumen: string;
    tanggal_penerimaan: string;
    catatan?: string;
    checked_by?: string;
    details: PenerimaanDetailForm[];
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

export default function Edit({ penerimaan, flash }: EditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Penerimaan Bahan Baku', href: '/penerimaan-bahan-baku' },
        { title: 'Edit Penerimaan', href: '' },
    ];

    const { data, setData, put, processing, errors } = useForm<FormData>({
        nomor_dokumen: penerimaan.nomor_dokumen,
        tanggal_penerimaan: penerimaan.tanggal_penerimaan || '',
        catatan: penerimaan.catatan || '',
        checked_by: penerimaan.checked_by || '',
        details: penerimaan.details.map((detail) => ({
            penerimaan_detail_id: detail.penerimaan_detail_id,
            qty_received: detail.qty_received || 0,
            qty_accepted: Math.max(0, (detail.qty_received || 0) - (detail.qty_rejected || 0)),
            qty_rejected: detail.qty_rejected || 0,
            status_quality: 'passed' as const,
            catatan_quality: detail.catatan_qc || '',
        })),
    });

    const updateDetail = (index: number, field: keyof PenerimaanDetailForm, value: string | number) => {
        const newDetails = [...data.details];
        newDetails[index] = { ...newDetails[index], [field]: value };

        // Auto-calculate accepted/rejected based on received quantity
        if (field === 'qty_received') {
            const qtyReceived = Number(value);
            const qtyRejected = newDetails[index].qty_rejected;
            newDetails[index].qty_accepted = Math.max(0, qtyReceived - qtyRejected);
        }

        if (field === 'qty_rejected') {
            const qtyReceived = newDetails[index].qty_received;
            const qtyRejected = Number(value);
            newDetails[index].qty_accepted = Math.max(0, qtyReceived - qtyRejected);
        }

        setData('details', newDetails);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/penerimaan-bahan-baku/${penerimaan.penerimaan_id}`);
    };

    const formatCurrency = (amount: number | undefined | null) => {
        if (amount === undefined || amount === null || isNaN(amount)) {
            return 'Rp 0';
        }
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const formatNumber = (number: number | undefined | null) => {
        if (number === undefined || number === null || isNaN(number)) {
            return '0';
        }
        return number.toLocaleString('id-ID');
    };

    return (
        <AppLayout>
            <Head title={`Edit Penerimaan - ${penerimaan.nomor_dokumen}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Edit Penerimaan Bahan Baku</h1>
                        <p className="text-muted-foreground">{penerimaan.nomor_dokumen}</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Badge className={getStatusColor(penerimaan.status)}>{getStatusText(penerimaan.status)}</Badge>
                    </div>
                </div>

                {flash?.message && (
                    <Alert className={flash.type === 'error' ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50'}>
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>{flash.message}</AlertDescription>
                    </Alert>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Form Information */}
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Package className="h-5 w-5" />
                                    Informasi Penerimaan
                                </CardTitle>
                                <CardDescription>Detail penerimaan bahan baku</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="nomor_dokumen">
                                        Nomor Dokumen <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="nomor_dokumen"
                                        type="text"
                                        value={data.nomor_dokumen}
                                        onChange={(e) => setData('nomor_dokumen', e.target.value)}
                                        className={errors.nomor_dokumen ? 'border-red-500' : ''}
                                        placeholder="Masukkan nomor dokumen..."
                                    />
                                    {errors.nomor_dokumen && <p className="text-sm text-red-500">{errors.nomor_dokumen}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="tanggal_penerimaan">
                                        Tanggal Penerimaan <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="tanggal_penerimaan"
                                        type="date"
                                        value={data.tanggal_penerimaan}
                                        onChange={(e) => setData('tanggal_penerimaan', e.target.value)}
                                        className={errors.tanggal_penerimaan ? 'border-red-500' : ''}
                                    />
                                    {errors.tanggal_penerimaan && <p className="text-sm text-red-500">{errors.tanggal_penerimaan}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="catatan">Catatan</Label>
                                    <Textarea
                                        id="catatan"
                                        value={data.catatan || ''}
                                        onChange={(e) => setData('catatan', e.target.value)}
                                        placeholder="Catatan tambahan..."
                                        rows={3}
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Pemasok Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Building className="h-5 w-5" />
                                    Informasi Pemasok
                                </CardTitle>
                                <CardDescription>Detailpemasok pembelian</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label>Nama Pemasok</Label>
                                    <p className="font-medium">{penerimaan.pemasok?.nama_pemasok || penerimaan.pembelian?.pemasok?.nama_pemasok}</p>
                                </div>
                                {penerimaan.pembelian && (
                                    <div>
                                        <Label>Nomor PO</Label>
                                        <p className="font-medium">{penerimaan.pembelian.nomor_po}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Detail Items */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Package className="h-5 w-5" />
                                Detail Bahan Baku
                            </CardTitle>
                            <CardDescription>Detail penerimaan untuk setiap bahan baku</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full border-collapse border border-gray-200">
                                    <thead>
                                        <tr className="bg-gray-50">
                                            <th className="border border-gray-200 p-3 text-left">Bahan Baku</th>
                                            <th className="border border-gray-200 p-3 text-center">Satuan</th>
                                            <th className="border border-gray-200 p-3 text-center">Qty Dipesan</th>
                                            <th className="border border-gray-200 p-3 text-center">Qty Diterima</th>
                                            <th className="border border-gray-200 p-3 text-center">Qty Ditolak</th>
                                            <th className="border border-gray-200 p-3 text-center">Harga Satuan</th>
                                            <th className="border border-gray-200 p-3 text-center">Catatan QC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {penerimaan.details.map((detail, index) => (
                                            <tr key={detail.penerimaan_detail_id}>
                                                <td className="border border-gray-200 p-3">
                                                    <div>
                                                        <p className="font-medium">{detail.bahan_baku.nama}</p>
                                                    </div>
                                                </td>
                                                <td className="border border-gray-200 p-3 text-center">{detail.bahan_baku.satuan}</td>
                                                <td className="border border-gray-200 p-3 text-center">{formatNumber(detail.qty_dipesan)}</td>
                                                <td className="border border-gray-200 p-3">
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        max={detail.qty_dipesan || 0}
                                                        value={data.details[index]?.qty_received || 0}
                                                        onChange={(e) => updateDetail(index, 'qty_received', parseInt(e.target.value) || 0)}
                                                        className="w-24 text-center"
                                                    />
                                                </td>
                                                <td className="border border-gray-200 p-3">
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        max={data.details[index]?.qty_received || 0}
                                                        value={data.details[index]?.qty_rejected || 0}
                                                        onChange={(e) => updateDetail(index, 'qty_rejected', parseInt(e.target.value) || 0)}
                                                        className="w-24 text-center"
                                                    />
                                                </td>
                                                <td className="border border-gray-200 p-3 text-right">{formatCurrency(detail.harga_satuan)}</td>
                                                <td className="border border-gray-200 p-3">
                                                    <Input
                                                        type="text"
                                                        value={data.details[index]?.catatan_quality || ''}
                                                        onChange={(e) => updateDetail(index, 'catatan_quality', e.target.value)}
                                                        placeholder="Catatan QC..."
                                                        className="w-40"
                                                    />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-end space-x-4 pt-6">
                        <Button type="button" variant="outline" onClick={() => window.history.back()} className="flex items-center gap-2">
                            <X className="h-4 w-4" />
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing} className="flex items-center gap-2">
                            <Save className="h-4 w-4" />
                            {processing ? 'Menyimpan...' : 'Simpan Penerimaan'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
