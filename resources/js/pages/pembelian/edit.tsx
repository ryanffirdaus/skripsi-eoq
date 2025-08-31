import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Building2, Calendar, FileText, Plus, X } from 'lucide-react';
import React, { useState } from 'react';

interface Supplier {
    supplier_id: string;
    nama_supplier: string;
    alamat?: string;
    telepon?: string;
    email?: string;
    kontak_person?: string;
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
}

interface Pembelian extends Record<string, unknown> {
    pembelian_id: string;
    pengadaan_id?: string;
    nomor_po: string;
    supplier_id: string;
    tanggal_pembelian: string;
    tanggal_jatuh_tempo?: string;
    total_biaya: number;
    status: string;
    metode_pembayaran?: string;
    catatan?: string;
    details: PembelianDetail[];
}

interface PembelianDetailForm {
    detail_id?: string;
    bahan_baku_id: string;
    nama_bahan: string;
    satuan: string;
    kuantitas: number;
    harga_satuan: number;
    subtotal: number;
    _deleted?: boolean;
}

interface Props {
    pembelian: Pembelian;
    suppliers: Supplier[];
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

export default function Edit({ pembelian, suppliers, flash }: Props) {
    const [showSupplierDetails, setShowSupplierDetails] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Pembelian', href: '/pembelian' },
        { title: pembelian.nomor_po, href: `/pembelian/${pembelian.pembelian_id}` },
        { title: 'Edit', href: `/pembelian/${pembelian.pembelian_id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        supplier_id: pembelian.supplier_id,
        tanggal_pembelian: pembelian.tanggal_pembelian,
        tanggal_jatuh_tempo: pembelian.tanggal_jatuh_tempo || '',
        metode_pembayaran: pembelian.metode_pembayaran || 'transfer',
        catatan: pembelian.catatan || '',
        details: pembelian.details.map((detail) => ({
            detail_id: detail.detail_id,
            bahan_baku_id: detail.bahan_baku_id,
            nama_bahan: detail.nama_bahan,
            satuan: detail.satuan,
            kuantitas: detail.kuantitas,
            harga_satuan: detail.harga_satuan,
            subtotal: detail.subtotal,
        })) as PembelianDetailForm[],
    });

    const selectedSupplier = suppliers.find((s) => s.supplier_id === data.supplier_id);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/pembelian/${pembelian.pembelian_id}`);
    };

    const addDetail = () => {
        setData('details', [
            ...data.details,
            {
                bahan_baku_id: '',
                nama_bahan: '',
                satuan: '',
                kuantitas: 0,
                harga_satuan: 0,
                subtotal: 0,
            },
        ]);
    };

    const removeDetail = (index: number) => {
        const detail = data.details[index];
        if (detail.detail_id) {
            // Mark existing detail for deletion
            const newDetails = [...data.details];
            newDetails[index] = { ...newDetails[index], _deleted: true };
            setData('details', newDetails);
        } else {
            // Remove new detail completely
            const newDetails = data.details.filter((_, i) => i !== index);
            setData('details', newDetails);
        }
    };

    const restoreDetail = (index: number) => {
        const newDetails = [...data.details];
        delete newDetails[index]._deleted;
        setData('details', newDetails);
    };

    const updateDetail = (index: number, field: keyof PembelianDetailForm, value: string | number) => {
        const newDetails = [...data.details];
        newDetails[index] = { ...newDetails[index], [field]: value };

        // Recalculate subtotal when quantity or price changes
        if (field === 'kuantitas' || field === 'harga_satuan') {
            newDetails[index].subtotal = newDetails[index].kuantitas * newDetails[index].harga_satuan;
        }

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

    const activeDetails = data.details.filter((detail) => !detail._deleted);
    const totalBiaya = activeDetails.reduce((sum, detail) => sum + detail.subtotal, 0);

    const canEditDetails = pembelian.status === 'draft' || pembelian.status === 'sent';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Purchase Order - ${pembelian.nomor_po}`} />

            <div className="space-y-6">
                {flash?.message && (
                    <Alert>
                        <AlertDescription>{flash.message}</AlertDescription>
                    </Alert>
                )}

                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Edit Purchase Order</h1>
                        <p className="text-gray-600">{pembelian.nomor_po}</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Header Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <FileText className="mr-2 h-5 w-5" />
                                Informasi Purchase Order
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {pembelian.pengadaan_id && (
                                <div className="rounded-lg bg-blue-50 p-4">
                                    <p className="text-sm text-blue-800">
                                        <strong>Berdasarkan Pengadaan:</strong> {pembelian.pengadaan_id}
                                    </p>
                                </div>
                            )}

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="supplier_id">Supplier *</Label>
                                    <div className="flex gap-2">
                                        <Select value={data.supplier_id} onValueChange={(value) => setData('supplier_id', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih Supplier" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {suppliers.map((supplier) => (
                                                    <SelectItem key={supplier.supplier_id} value={supplier.supplier_id}>
                                                        {supplier.nama_supplier}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {selectedSupplier && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setShowSupplierDetails(!showSupplierDetails)}
                                            >
                                                <Building2 className="h-4 w-4" />
                                            </Button>
                                        )}
                                    </div>
                                    {errors.supplier_id && <p className="mt-1 text-sm text-red-600">{errors.supplier_id}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="metode_pembayaran">Metode Pembayaran</Label>
                                    <Select value={data.metode_pembayaran} onValueChange={(value) => setData('metode_pembayaran', value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="cash">Tunai</SelectItem>
                                            <SelectItem value="transfer">Transfer Bank</SelectItem>
                                            <SelectItem value="credit">Kredit</SelectItem>
                                            <SelectItem value="check">Cek</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="tanggal_pembelian">Tanggal Pembelian *</Label>
                                    <div className="relative">
                                        <Calendar className="absolute top-3 left-3 h-4 w-4 text-gray-400" />
                                        <Input
                                            id="tanggal_pembelian"
                                            type="date"
                                            value={data.tanggal_pembelian}
                                            onChange={(e) => setData('tanggal_pembelian', e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                    {errors.tanggal_pembelian && <p className="mt-1 text-sm text-red-600">{errors.tanggal_pembelian}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="tanggal_jatuh_tempo">Tanggal Jatuh Tempo</Label>
                                    <div className="relative">
                                        <Calendar className="absolute top-3 left-3 h-4 w-4 text-gray-400" />
                                        <Input
                                            id="tanggal_jatuh_tempo"
                                            type="date"
                                            value={data.tanggal_jatuh_tempo}
                                            onChange={(e) => setData('tanggal_jatuh_tempo', e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                </div>
                            </div>

                            {showSupplierDetails && selectedSupplier && (
                                <div className="rounded-lg bg-gray-50 p-4">
                                    <h4 className="mb-2 font-medium">Detail Supplier</h4>
                                    <div className="space-y-1 text-sm">
                                        <p>
                                            <strong>Nama:</strong> {selectedSupplier.nama_supplier}
                                        </p>
                                        {selectedSupplier.alamat && (
                                            <p>
                                                <strong>Alamat:</strong> {selectedSupplier.alamat}
                                            </p>
                                        )}
                                        {selectedSupplier.telepon && (
                                            <p>
                                                <strong>Telepon:</strong> {selectedSupplier.telepon}
                                            </p>
                                        )}
                                        {selectedSupplier.email && (
                                            <p>
                                                <strong>Email:</strong> {selectedSupplier.email}
                                            </p>
                                        )}
                                        {selectedSupplier.kontak_person && (
                                            <p>
                                                <strong>Kontak Person:</strong> {selectedSupplier.kontak_person}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}

                            <div>
                                <Label htmlFor="catatan">Catatan</Label>
                                <Textarea
                                    id="catatan"
                                    value={data.catatan}
                                    onChange={(e) => setData('catatan', e.target.value)}
                                    placeholder="Catatan tambahan untuk purchase order..."
                                    rows={3}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Purchase Order Details */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>Detail Pembelian</CardTitle>
                                {canEditDetails && (
                                    <Button type="button" variant="outline" size="sm" onClick={addDetail}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Tambah Item
                                    </Button>
                                )}
                            </div>
                            {!canEditDetails && (
                                <p className="text-sm text-yellow-600">
                                    Detail pembelian tidak dapat diubah karena status PO sudah {pembelian.status}.
                                </p>
                            )}
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {data.details.map((detail, index) => (
                                    <div
                                        key={detail.detail_id || index}
                                        className={`rounded-lg border p-4 ${detail._deleted ? 'border-red-200 bg-red-50' : ''}`}
                                    >
                                        <div className="mb-4 flex items-center justify-between">
                                            <h4 className="font-medium">
                                                Item #{index + 1}
                                                {detail._deleted && <span className="ml-2 text-red-600">(Akan Dihapus)</span>}
                                            </h4>
                                            {canEditDetails && (
                                                <div className="flex gap-2">
                                                    {detail._deleted ? (
                                                        <Button type="button" variant="outline" size="sm" onClick={() => restoreDetail(index)}>
                                                            Restore
                                                        </Button>
                                                    ) : (
                                                        <Button type="button" variant="ghost" size="sm" onClick={() => removeDetail(index)}>
                                                            <X className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            )}
                                        </div>

                                        {!detail._deleted && (
                                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
                                                <div className="lg:col-span-2">
                                                    <Label>Nama Bahan</Label>
                                                    <Input
                                                        value={detail.nama_bahan}
                                                        onChange={(e) => updateDetail(index, 'nama_bahan', e.target.value)}
                                                        placeholder="Nama bahan baku"
                                                        disabled={!canEditDetails}
                                                    />
                                                </div>

                                                <div>
                                                    <Label>Satuan</Label>
                                                    <Input
                                                        value={detail.satuan}
                                                        onChange={(e) => updateDetail(index, 'satuan', e.target.value)}
                                                        placeholder="Kg, Pcs, dll"
                                                        disabled={!canEditDetails}
                                                    />
                                                </div>

                                                <div>
                                                    <Label>Kuantitas</Label>
                                                    <Input
                                                        type="number"
                                                        value={detail.kuantitas}
                                                        onChange={(e) => updateDetail(index, 'kuantitas', parseFloat(e.target.value) || 0)}
                                                        min="0"
                                                        step="0.01"
                                                        disabled={!canEditDetails}
                                                    />
                                                </div>

                                                <div>
                                                    <Label>Harga Satuan</Label>
                                                    <Input
                                                        type="number"
                                                        value={detail.harga_satuan}
                                                        onChange={(e) => updateDetail(index, 'harga_satuan', parseFloat(e.target.value) || 0)}
                                                        min="0"
                                                        step="0.01"
                                                        disabled={!canEditDetails}
                                                    />
                                                </div>

                                                <div>
                                                    <Label>Subtotal</Label>
                                                    <div className="rounded bg-gray-50 p-2 text-sm font-medium">
                                                        {formatCurrency(detail.subtotal)}
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}

                                {/* Total */}
                                <div className="border-t pt-4">
                                    <div className="flex justify-end">
                                        <div className="text-right">
                                            <p className="text-lg font-semibold">Total: {formatCurrency(totalBiaya)}</p>
                                        </div>
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
                        <Button type="submit" disabled={processing || activeDetails.length === 0}>
                            {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
