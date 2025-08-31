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

interface Pengadaan {
    pengadaan_id: string;
    nomor_pengadaan: string;
    tanggal_pengadaan: string;
    total_biaya: number;
    status: string;
}

interface PengadaanDetail {
    pengadaan_detail_id: string;
    item_type: string;
    item_id: string;
    nama_item: string;
    satuan: string;
    qty_disetujui: number;
    qty_tersisa: number;
    harga_satuan: number;
    total_harga: number;
    alasan_kebutuhan?: string;
    catatan?: string;
}

interface PembelianDetailForm {
    pengadaan_detail_id?: string;
    item_type: string;
    item_id: string;
    nama_item: string;
    satuan: string;
    qty_po: number;
    harga_satuan: number;
    total_harga: number;
    spesifikasi?: string;
    catatan?: string;
}

interface Props {
    suppliers: Supplier[];
    pengadaan?: Pengadaan;
    pengadaanDetails?: PengadaanDetail[];
    errors: Record<string, string>;
    flash?: {
        message?: string;
        type?: 'success' | 'error' | 'warning' | 'info';
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pembelian', href: '/pembelian' },
    { title: 'Buat PO Baru', href: '/pembelian/create' },
];

export default function Create({ suppliers, pengadaan, pengadaanDetails, flash }: Props) {
    const [showSupplierDetails, setShowSupplierDetails] = useState(false);

    const {
        data,
        setData,
        post,
        processing,
        errors: formErrors,
    } = useForm({
        pengadaan_id: pengadaan?.pengadaan_id || '',
        supplier_id: '',
        tanggal_pembelian: new Date().toISOString().split('T')[0],
        tanggal_jatuh_tempo: '',
        metode_pembayaran: 'transfer',
        catatan: '',
        details: pengadaanDetails?.map((detail) => ({
            pengadaan_detail_id: detail.pengadaan_detail_id,
            item_type: detail.item_type,
            item_id: detail.item_id,
            nama_item: detail.nama_item,
            satuan: detail.satuan,
            qty_po: detail.qty_tersisa,
            harga_satuan: detail.harga_satuan,
            total_harga: detail.qty_tersisa * detail.harga_satuan,
            spesifikasi: detail.alasan_kebutuhan,
            catatan: '',
        })) || [
            {
                pengadaan_detail_id: '',
                item_type: 'bahan_baku',
                item_id: '',
                nama_item: '',
                satuan: '',
                qty_po: 0,
                harga_satuan: 0,
                total_harga: 0,
                spesifikasi: '',
                catatan: '',
            },
        ],
    });

    const selectedSupplier = suppliers.find((s) => s.supplier_id === data.supplier_id);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/pembelian');
    };

    const addDetail = () => {
        setData('details', [
            ...data.details,
            {
                pengadaan_detail_id: '',
                item_type: 'bahan_baku',
                item_id: '',
                nama_item: '',
                satuan: '',
                qty_po: 0,
                harga_satuan: 0,
                total_harga: 0,
                spesifikasi: '',
                catatan: '',
            },
        ]);
    };

    const removeDetail = (index: number) => {
        if (data.details.length > 1) {
            const newDetails = data.details.filter((_, i) => i !== index);
            setData('details', newDetails);
        }
    };

    const updateDetail = (index: number, field: keyof PembelianDetailForm, value: string | number) => {
        const newDetails = [...data.details];
        newDetails[index] = { ...newDetails[index], [field]: value };

        // Recalculate total when quantity or price changes
        if (field === 'qty_po' || field === 'harga_satuan') {
            newDetails[index].total_harga = newDetails[index].qty_po * newDetails[index].harga_satuan;
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

    const totalBiaya = data.details.reduce((sum, detail) => sum + detail.total_harga, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buat Purchase Order" />

            <div className="space-y-6">
                {flash?.message && (
                    <Alert>
                        <AlertDescription>{flash.message}</AlertDescription>
                    </Alert>
                )}

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
                            {pengadaan && (
                                <div className="rounded-lg bg-blue-50 p-4">
                                    <p className="text-sm text-blue-800">
                                        <strong>Berdasarkan Pengadaan:</strong> {pengadaan.nomor_pengadaan} - {pengadaan.tanggal_pengadaan}
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
                                    {formErrors.supplier_id && <p className="mt-1 text-sm text-red-600">{formErrors.supplier_id}</p>}
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
                                    {formErrors.tanggal_pembelian && <p className="mt-1 text-sm text-red-600">{formErrors.tanggal_pembelian}</p>}
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
                                <Button type="button" variant="outline" size="sm" onClick={addDetail}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Tambah Item
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {data.details.map((detail, index) => (
                                    <div key={index} className="rounded-lg border p-4">
                                        <div className="mb-4 flex items-center justify-between">
                                            <h4 className="font-medium">Item #{index + 1}</h4>
                                            {data.details.length > 1 && (
                                                <Button type="button" variant="ghost" size="sm" onClick={() => removeDetail(index)}>
                                                    <X className="h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
                                            <div className="lg:col-span-2">
                                                <Label>Nama Item</Label>
                                                <Input
                                                    value={detail.nama_item}
                                                    onChange={(e) => updateDetail(index, 'nama_item', e.target.value)}
                                                    placeholder="Nama item"
                                                    disabled={!!pengadaan}
                                                />
                                            </div>

                                            <div>
                                                <Label>Satuan</Label>
                                                <Input
                                                    value={detail.satuan}
                                                    onChange={(e) => updateDetail(index, 'satuan', e.target.value)}
                                                    placeholder="Kg, Pcs, dll"
                                                    disabled={!!pengadaan}
                                                />
                                            </div>

                                            <div>
                                                <Label>
                                                    Kuantitas
                                                    {pengadaan && pengadaanDetails && (
                                                        <span className="ml-1 text-xs text-gray-500">
                                                            (Tersedia:{' '}
                                                            {pengadaanDetails.find((pd) => pd.pengadaan_detail_id === detail.pengadaan_detail_id)
                                                                ?.qty_tersisa || 0}
                                                            )
                                                        </span>
                                                    )}
                                                </Label>
                                                <Input
                                                    type="number"
                                                    value={detail.qty_po}
                                                    onChange={(e) => updateDetail(index, 'qty_po', parseFloat(e.target.value) || 0)}
                                                    min="0"
                                                    max={
                                                        pengadaanDetails?.find((pd) => pd.pengadaan_detail_id === detail.pengadaan_detail_id)
                                                            ?.qty_tersisa
                                                    }
                                                    step="0.01"
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
                                                />
                                            </div>

                                            <div>
                                                <Label>Total</Label>
                                                <div className="rounded bg-gray-50 p-2 text-sm font-medium">{formatCurrency(detail.total_harga)}</div>
                                            </div>
                                        </div>
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
                        <Button type="button" variant="outline" onClick={() => router.visit('/pembelian')}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing || data.details.length === 0}>
                            {processing ? 'Menyimpan...' : 'Simpan Purchase Order'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
