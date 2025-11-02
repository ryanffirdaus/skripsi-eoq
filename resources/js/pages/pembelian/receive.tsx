import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { AlertTriangle, Calendar, CheckCircle, Package } from 'lucide-react';
import React from 'react';

interface PembelianDetail {
    detail_id: string;
    bahan_baku_id: string;
    nama_bahan: string;
    satuan: string;
    kuantitas: number;
    harga_satuan: number;
    subtotal: number;
    kuantitas_diterima: number;
    sisa_kuantitas: number;
    persentase_diterima: number;
}

interface Pembelian extends Record<string, unknown> {
    pembelian_id: string;
    pemasok: {
        nama_pemasok: string;
    };
    tanggal_pembelian: string;
    total_biaya: number;
    status: string;
    status_label: string;
    details: PembelianDetail[];
}

interface ReceiveDetailForm {
    detail_id: string;
    kuantitas_diterima: number;
    catatan?: string;
}

interface Props {
    pembelian: Pembelian;
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

export default function Receive({ pembelian, flash }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Pembelian', href: '/pembelian' },
        { title: pembelian.pembelian_id as string, href: `/pembelian/${pembelian.pembelian_id}` },
        { title: 'Terima Barang', href: `/pembelian/${pembelian.pembelian_id}/receive` },
    ];

    const { data, setData, post, processing, errors } = useForm({
        tanggal_penerimaan: new Date().toISOString().split('T')[0],
        catatan_penerimaan: '',
        details: pembelian.details.map((detail) => ({
            detail_id: detail.detail_id,
            kuantitas_diterima: detail.sisa_kuantitas,
            catatan: '',
        })) as ReceiveDetailForm[],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/pembelian/${pembelian.pembelian_id}/receive`);
    };

    const updateDetailQuantity = (index: number, quantity: number) => {
        const newDetails = [...data.details];
        newDetails[index] = { ...newDetails[index], kuantitas_diterima: quantity };
        setData('details', newDetails);
    };

    const updateDetailNote = (index: number, note: string) => {
        const newDetails = [...data.details];
        newDetails[index] = { ...newDetails[index], catatan: note };
        setData('details', newDetails);
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

    const totalReceiving = data.details.reduce((sum, detail, index) => {
        const originalDetail = pembelian.details[index];
        return sum + detail.kuantitas_diterima * originalDetail.harga_satuan;
    }, 0);

    const hasAnyReceiving = data.details.some((detail) => detail.kuantitas_diterima > 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Terima Barang - ${pembelian.pembelian_id}`} />

            <div className="space-y-6">
                {flash?.message && (
                    <Alert>
                        <AlertDescription>{flash.message}</AlertDescription>
                    </Alert>
                )}

                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Penerimaan Barang</h1>
                        <p className="text-gray-600">
                            {pembelian.pembelian_id as string} - {pembelian.pemasok.nama_pemasok}
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Header Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Package className="mr-2 h-5 w-5" />
                                Informasi Penerimaan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="tanggal_penerimaan">Tanggal Penerimaan *</Label>
                                    <div className="relative">
                                        <Calendar className="absolute top-3 left-3 h-4 w-4 text-gray-400" />
                                        <Input
                                            id="tanggal_penerimaan"
                                            type="date"
                                            value={data.tanggal_penerimaan}
                                            onChange={(e) => setData('tanggal_penerimaan', e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                    {errors.tanggal_penerimaan && <p className="mt-1 text-sm text-red-600">{errors.tanggal_penerimaan}</p>}
                                </div>

                                <div>
                                    <Label>Total Nilai Penerimaan</Label>
                                    <div className="rounded bg-green-50 p-2 text-lg font-bold text-green-700">{formatCurrency(totalReceiving)}</div>
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="catatan_penerimaan">Catatan Penerimaan</Label>
                                <Textarea
                                    id="catatan_penerimaan"
                                    value={data.catatan_penerimaan}
                                    onChange={(e) => setData('catatan_penerimaan', e.target.value)}
                                    placeholder="Catatan kondisi barang, kualitas, atau informasi lainnya..."
                                    rows={3}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Purchase Order Summary */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Detail Purchase Order</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="mb-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Nomor PO</p>
                                    <p className="font-semibold">{pembelian.pembelian_id as string}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Pemasok</p>
                                    <p>{pembelian.pemasok.nama_pemasok}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Tanggal PO</p>
                                    <p>{formatDate(pembelian.tanggal_pembelian)}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Total PO</p>
                                    <p className="font-semibold">{formatCurrency(pembelian.total_biaya)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Receiving Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Detail Penerimaan Barang</CardTitle>
                            <p className="text-sm text-gray-600">
                                Masukkan jumlah barang yang diterima untuk setiap item. Anda dapat melakukan penerimaan parsial.
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {pembelian.details.map((detail, index) => (
                                    <div key={detail.detail_id} className="rounded-lg border p-4">
                                        <div className="mb-4 flex items-center justify-between">
                                            <h4 className="font-medium">{detail.nama_bahan}</h4>
                                            {detail.persentase_diterima > 0 && (
                                                <div className="flex items-center text-sm text-green-600">
                                                    <CheckCircle className="mr-1 h-4 w-4" />
                                                    {detail.persentase_diterima.toFixed(1)}% sudah diterima
                                                </div>
                                            )}
                                        </div>

                                        <div className="mb-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">Dipesan</p>
                                                <p className="font-semibold">
                                                    {detail.kuantitas} {detail.satuan}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">Sudah Diterima</p>
                                                <p className="text-green-600">
                                                    {detail.kuantitas_diterima} {detail.satuan}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">Sisa</p>
                                                <p className="text-orange-600">
                                                    {detail.sisa_kuantitas} {detail.satuan}
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

                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div>
                                                <Label htmlFor={`quantity_${index}`}>Kuantitas Diterima Sekarang *</Label>
                                                <div className="flex items-center space-x-2">
                                                    <Input
                                                        id={`quantity_${index}`}
                                                        type="number"
                                                        value={data.details[index].kuantitas_diterima}
                                                        onChange={(e) => updateDetailQuantity(index, parseFloat(e.target.value) || 0)}
                                                        min="0"
                                                        max={detail.sisa_kuantitas}
                                                        step="0.01"
                                                        placeholder="0"
                                                    />
                                                    <span className="text-sm text-gray-500">{detail.satuan}</span>
                                                </div>
                                                {data.details[index].kuantitas_diterima > detail.sisa_kuantitas && (
                                                    <div className="mt-1 flex items-center text-sm text-red-600">
                                                        <AlertTriangle className="mr-1 h-4 w-4" />
                                                        Melebihi sisa yang belum diterima
                                                    </div>
                                                )}
                                                {errors[`details.${index}.kuantitas_diterima`] && (
                                                    <p className="mt-1 text-sm text-red-600">{errors[`details.${index}.kuantitas_diterima`]}</p>
                                                )}
                                            </div>

                                            <div>
                                                <Label htmlFor={`note_${index}`}>Catatan Item</Label>
                                                <Input
                                                    id={`note_${index}`}
                                                    value={data.details[index].catatan || ''}
                                                    onChange={(e) => updateDetailNote(index, e.target.value)}
                                                    placeholder="Kondisi barang, kualitas, dll..."
                                                />
                                            </div>
                                        </div>

                                        {data.details[index].kuantitas_diterima > 0 && (
                                            <div className="mt-3 rounded bg-green-50 p-3">
                                                <p className="text-sm text-green-800">
                                                    <strong>Nilai penerimaan item ini:</strong>{' '}
                                                    {formatCurrency(data.details[index].kuantitas_diterima * detail.harga_satuan)}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                ))}

                                {/* Summary */}
                                <div className="border-t pt-4">
                                    <div className="rounded-lg bg-gray-50 p-4">
                                        <div className="flex items-center justify-between">
                                            <p className="text-lg font-semibold">Total Nilai Penerimaan:</p>
                                            <p className="text-xl font-bold text-green-600">{formatCurrency(totalReceiving)}</p>
                                        </div>
                                        {!hasAnyReceiving && <p className="mt-2 text-sm text-red-600">Masukkan minimal satu item yang diterima</p>}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex justify-end space-x-2">
                        <Button type="button" variant="outline" onClick={() => router.visit(`/pembelian/${pembelian.pembelian_id}`)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing || !hasAnyReceiving}>
                            {processing ? 'Memproses...' : 'Konfirmasi Penerimaan'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
