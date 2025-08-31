import FormTemplate from '@/components/form/form-template';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { InformationCircleIcon, PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import { Head, useForm } from '@inertiajs/react';
import React, { useState } from 'react';

interface Supplier {
    supplier_id: string;
    nama_supplier: string;
    kontak_person: string;
    email: string;
    telepon: string;
}

interface BahanBaku {
    bahan_baku_id: string;
    nama_bahan: string;
    satuan: string;
    harga_per_unit: number;
    stok_saat_ini: number;
    reorder_point: number;
    eoq: number;
}

interface Produk {
    produk_id: string;
    nama_produk: string;
    satuan: string;
    hpp: number;
    stok_saat_ini: number;
    reorder_point: number;
    eoq: number;
}

interface ItemDetail {
    item_type: 'bahan_baku' | 'produk';
    item_id: string;
    catatan: string;
}

interface Props {
    suppliers: Supplier[];
    bahanBaku: BahanBaku[];
    produk: Produk[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengadaan', href: '/pengadaan' },
    { title: 'Tambah Pengadaan', href: '#' },
];

export default function Create({ suppliers, bahanBaku, produk }: Props) {
    const [items, setItems] = useState<ItemDetail[]>([
        {
            item_type: 'bahan_baku',
            item_id: '',
            catatan: '',
        },
    ]);

    const { data, setData, post, processing, errors, reset } = useForm({
        supplier_id: '',
        jenis_pengadaan: 'rop',
        tanggal_pengadaan: new Date().toISOString().split('T')[0],
        tanggal_dibutuhkan: '',
        prioritas: 'normal',
        alasan_pengadaan: '',
        catatan: '',
        items: items,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/pengadaan', {
            onSuccess: () => {
                reset();
            },
        });
    };

    const addItem = () => {
        const newItems = [
            ...items,
            {
                item_type: 'bahan_baku' as const,
                item_id: '',
                catatan: '',
            },
        ];
        setItems(newItems);
        setData('items', newItems);
    };

    const removeItem = (index: number) => {
        if (items.length > 1) {
            const newItems = items.filter((_, i) => i !== index);
            setItems(newItems);
            setData('items', newItems);
        }
    };

    const updateItem = (index: number, field: keyof ItemDetail, value: string) => {
        const newItems = [...items];
        newItems[index] = { ...newItems[index], [field]: value };

        // Reset item_id when item_type changes
        if (field === 'item_type') {
            newItems[index].item_id = '';
        }

        setItems(newItems);
        setData('items', newItems);
    };

    const getItemOptions = (itemType: 'bahan_baku' | 'produk') => {
        if (itemType === 'bahan_baku') {
            return bahanBaku.map((item) => ({
                value: item.bahan_baku_id,
                label: `${item.nama_bahan} (${item.satuan})`,
                item: item,
            }));
        } else {
            return produk.map((item) => ({
                value: item.produk_id,
                label: `${item.nama_produk} (${item.satuan})`,
                item: item,
            }));
        }
    };

    const getItemDetails = (itemType: 'bahan_baku' | 'produk', itemId: string) => {
        if (itemType === 'bahan_baku') {
            const item = bahanBaku.find((b) => b.bahan_baku_id === itemId);
            return item
                ? {
                      satuan: item.satuan,
                      stok: item.stok_saat_ini,
                      rop: item.reorder_point,
                      eoq: item.eoq,
                      harga: item.harga_per_unit,
                      nama: item.nama_bahan,
                  }
                : null;
        } else {
            const item = produk.find((p) => p.produk_id === itemId);
            return item
                ? {
                      satuan: item.satuan,
                      stok: item.stok_saat_ini,
                      rop: item.reorder_point,
                      eoq: item.eoq,
                      harga: item.hpp,
                      nama: item.nama_produk,
                  }
                : null;
        }
    };

    const calculateTotal = () => {
        return items.reduce((total, item) => {
            const details = getItemDetails(item.item_type, item.item_id);
            return total + (details ? details.eoq * details.harga : 0);
        }, 0);
    };

    const isStockCritical = (itemType: 'bahan_baku' | 'produk', itemId: string) => {
        const details = getItemDetails(itemType, itemId);
        if (!details) return false;
        return details.stok <= details.rop;
    };

    return (
        <FormTemplate
            title="Tambah Pengadaan"
            breadcrumbs={breadcrumbs}
            backUrl="/pengadaan"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Simpan Pengadaan"
            processingText="Menyimpan..."
        >
            <Head title="Tambah Pengadaan" />

            {/* Information Notice */}
            <div className="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div className="flex items-start">
                    <div className="flex-shrink-0">
                        <InformationCircleIcon className="h-5 w-5 text-blue-400" />
                    </div>
                    <div className="ml-3">
                        <h3 className="text-sm font-medium text-blue-800">Informasi Pengadaan EOQ</h3>
                        <div className="mt-2 text-sm text-blue-700">
                            <p>• Quantity pengadaan otomatis menggunakan nilai EOQ (Economic Order Quantity)</p>
                            <p>• Harga otomatis menggunakan harga standard dari master data</p>
                            <p>• Sistem akan menampilkan status stok dan ROP untuk membantu prioritas</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Basic Information */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <Label htmlFor="supplier_id">Supplier *</Label>
                    <Select value={data.supplier_id} onValueChange={(value) => setData('supplier_id', value)}>
                        <SelectTrigger className={cn('mt-1', errors.supplier_id && 'border-red-500')}>
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
                    {errors.supplier_id && <p className="mt-1 text-sm text-red-600">{errors.supplier_id}</p>}
                </div>

                <div>
                    <Label htmlFor="jenis_pengadaan">Jenis Pengadaan *</Label>
                    <Select value={data.jenis_pengadaan} onValueChange={(value) => setData('jenis_pengadaan', value)}>
                        <SelectTrigger className="mt-1">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="rop">Reorder Point</SelectItem>
                            <SelectItem value="pesanan">Berdasarkan Pesanan</SelectItem>
                        </SelectContent>
                    </Select>
                    {errors.jenis_pengadaan && <p className="mt-1 text-sm text-red-600">{errors.jenis_pengadaan}</p>}
                </div>

                <div>
                    <Label htmlFor="tanggal_pengadaan">Tanggal Pengadaan *</Label>
                    <Input
                        id="tanggal_pengadaan"
                        type="date"
                        value={data.tanggal_pengadaan}
                        onChange={(e) => setData('tanggal_pengadaan', e.target.value)}
                        className={cn('mt-1', errors.tanggal_pengadaan && 'border-red-500')}
                    />
                    {errors.tanggal_pengadaan && <p className="mt-1 text-sm text-red-600">{errors.tanggal_pengadaan}</p>}
                </div>

                <div>
                    <Label htmlFor="tanggal_dibutuhkan">Tanggal Dibutuhkan *</Label>
                    <Input
                        id="tanggal_dibutuhkan"
                        type="date"
                        value={data.tanggal_dibutuhkan}
                        onChange={(e) => setData('tanggal_dibutuhkan', e.target.value)}
                        className={cn('mt-1', errors.tanggal_dibutuhkan && 'border-red-500')}
                    />
                    {errors.tanggal_dibutuhkan && <p className="mt-1 text-sm text-red-600">{errors.tanggal_dibutuhkan}</p>}
                </div>

                <div>
                    <Label htmlFor="prioritas">Prioritas *</Label>
                    <Select value={data.prioritas} onValueChange={(value) => setData('prioritas', value)}>
                        <SelectTrigger className="mt-1">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="low">Low</SelectItem>
                            <SelectItem value="normal">Normal</SelectItem>
                            <SelectItem value="high">High</SelectItem>
                            <SelectItem value="urgent">Urgent</SelectItem>
                        </SelectContent>
                    </Select>
                    {errors.prioritas && <p className="mt-1 text-sm text-red-600">{errors.prioritas}</p>}
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6">
                <div>
                    <Label htmlFor="alasan_pengadaan">Alasan Pengadaan</Label>
                    <Textarea
                        id="alasan_pengadaan"
                        value={data.alasan_pengadaan}
                        onChange={(e) => setData('alasan_pengadaan', e.target.value)}
                        className="mt-1"
                        rows={3}
                        placeholder="Jelaskan alasan pengadaan..."
                    />
                    {errors.alasan_pengadaan && <p className="mt-1 text-sm text-red-600">{errors.alasan_pengadaan}</p>}
                </div>

                <div>
                    <Label htmlFor="catatan">Catatan</Label>
                    <Textarea
                        id="catatan"
                        value={data.catatan}
                        onChange={(e) => setData('catatan', e.target.value)}
                        className="mt-1"
                        rows={3}
                        placeholder="Catatan tambahan..."
                    />
                    {errors.catatan && <p className="mt-1 text-sm text-red-600">{errors.catatan}</p>}
                </div>
            </div>

            {/* Items Section */}
            <div className="border-t pt-6">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className={cn(colors.text.primary, 'text-lg font-medium')}>Item Pengadaan</h3>
                    <Button type="button" onClick={addItem} variant="outline" size="sm" className="flex items-center gap-2">
                        <PlusIcon className="h-4 w-4" />
                        Tambah Item
                    </Button>
                </div>

                <div className="space-y-4">
                    {items.map((item, index) => {
                        const itemDetails = getItemDetails(item.item_type, item.item_id);
                        const isCritical = isStockCritical(item.item_type, item.item_id);

                        return (
                            <div key={index} className={cn('rounded-lg border p-4', colors.border.primary, isCritical && 'border-red-300 bg-red-50')}>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                                    <div>
                                        <Label>Tipe Item</Label>
                                        <Select
                                            value={item.item_type}
                                            onValueChange={(value: 'bahan_baku' | 'produk') => updateItem(index, 'item_type', value)}
                                        >
                                            <SelectTrigger className="mt-1">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="bahan_baku">Bahan Baku</SelectItem>
                                                <SelectItem value="produk">Produk</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="md:col-span-2">
                                        <Label>Item</Label>
                                        <Select value={item.item_id} onValueChange={(value) => updateItem(index, 'item_id', value)}>
                                            <SelectTrigger className="mt-1">
                                                <SelectValue placeholder="Pilih Item" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {getItemOptions(item.item_type).map((option) => (
                                                    <SelectItem key={option.value} value={option.value}>
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="flex items-end">
                                        <Button
                                            type="button"
                                            onClick={() => removeItem(index)}
                                            variant="outline"
                                            size="sm"
                                            className="text-red-600 hover:text-red-700"
                                            disabled={items.length === 1}
                                        >
                                            <TrashIcon className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                {/* Item Details Display */}
                                {itemDetails && (
                                    <div className="mt-4 grid grid-cols-2 gap-4 rounded bg-gray-50 p-3 md:grid-cols-5">
                                        <div>
                                            <Label className="text-xs text-gray-600">Stok Saat Ini</Label>
                                            <div className={cn('text-sm font-medium', isCritical ? 'text-red-600' : 'text-gray-900')}>
                                                {itemDetails.stok} {itemDetails.satuan}
                                            </div>
                                        </div>
                                        <div>
                                            <Label className="text-xs text-gray-600">ROP</Label>
                                            <div className="text-sm font-medium text-gray-900">
                                                {itemDetails.rop} {itemDetails.satuan}
                                            </div>
                                        </div>
                                        <div>
                                            <Label className="text-xs text-gray-600">EOQ</Label>
                                            <div className="text-sm font-medium text-blue-600">
                                                {itemDetails.eoq} {itemDetails.satuan}
                                            </div>
                                        </div>
                                        <div>
                                            <Label className="text-xs text-gray-600">Harga Satuan</Label>
                                            <div className="text-sm font-medium text-gray-900">Rp {itemDetails.harga.toLocaleString('id-ID')}</div>
                                        </div>
                                        <div>
                                            <Label className="text-xs text-gray-600">Total</Label>
                                            <div className="text-sm font-bold text-green-600">
                                                Rp {(itemDetails.eoq * itemDetails.harga).toLocaleString('id-ID')}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {isCritical && (
                                    <div className="mt-2 rounded border border-red-200 bg-red-100 p-2 text-sm text-red-700">
                                        ⚠️ Stok kritis! Stok saat ini ({itemDetails?.stok}) sudah di bawah ROP ({itemDetails?.rop})
                                    </div>
                                )}

                                <div className="mt-4">
                                    <Label>Catatan Item</Label>
                                    <Input
                                        value={item.catatan}
                                        onChange={(e) => updateItem(index, 'catatan', e.target.value)}
                                        className="mt-1"
                                        placeholder="Catatan untuk item ini..."
                                    />
                                </div>
                            </div>
                        );
                    })}
                </div>

                {errors.items && <p className="mt-2 text-sm text-red-600">{errors.items}</p>}

                <div className="mt-4 rounded-lg bg-gray-50 p-4">
                    <div className="flex items-center justify-between">
                        <span className="font-medium">Total Estimasi Biaya:</span>
                        <span className="text-lg font-bold">Rp {calculateTotal().toLocaleString('id-ID')}</span>
                    </div>
                </div>
            </div>
        </FormTemplate>
    );
}
