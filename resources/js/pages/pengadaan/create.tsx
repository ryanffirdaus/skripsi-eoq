import FormTemplate from '@/components/form/form-template';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { CalculatorIcon, InformationCircleIcon, PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import { Head, useForm } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';

interface Pemasok {
    pemasok_id: string;
    nama_pemasok: string;
    narahubung: string;
    email: string;
    telepon: string;
}

interface PesananProduk {
    produk_id: string;
    nama_produk: string;
    jumlah_produk: number;
    stok_produk: number;
    eoq_produk: number;
    hpp_produk: number;
    satuan_produk: string;
}

interface Pesanan {
    pesanan_id: string;
    pelanggan_id: string;
    pelanggan_nama: string;
    tanggal_pemesanan: string;
    total_harga: number;
    status: string;
    display_text: string;
    produk: PesananProduk[];
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

interface BahanBakuProduk {
    bahan_baku_id: string;
    nama_bahan: string;
    jumlah_bahan_baku: number;
    stok_bahan: number;
    satuan_bahan: string;
    harga_bahan: number;
    eoq_bahan: number;
    rop_bahan: number;
}

interface Produk {
    produk_id: string;
    nama_produk: string;
    satuan: string;
    hpp: number;
    stok_saat_ini: number;
    reorder_point: number;
    eoq: number;
    bahan_baku: BahanBakuProduk[];
}

interface ItemDetail {
    jenis_barang: 'bahan_baku' | 'produk'; // SOURCE OF TRUTH FROM MODEL
    barang_id: string; // SOURCE OF TRUTH FROM MODEL
    pemasok_id?: string;
    nama_item?: string;
    satuan?: string;
    qty_needed?: number;
    qty_diminta: number; // SOURCE OF TRUTH FROM MODEL
    harga_satuan?: number;
    catatan: string;
}

interface ProcurementCalculation {
    success: boolean;
    items: ItemDetail[];
    summary: {
        total_items: number;
        total_cost: number;
    };
}

interface Props {
    pemasoks: Pemasok[];
    pesanan: Pesanan[];
    bahanBaku: BahanBaku[];
    produk: Produk[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengadaan', href: '/pengadaan' },
    { title: 'Tambah Pengadaan', href: '#' },
];

export default function Create({ pemasoks, pesanan, bahanBaku, produk }: Props) {
    const [items, setItems] = useState<ItemDetail[]>([]);
    const [isCalculating, setIsCalculating] = useState(false);
    const [calculationResult, setCalculationResult] = useState<ProcurementCalculation | null>(null);
    const [showManualAdd, setShowManualAdd] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        pesanan_id: '',
        catatan: '',
        items: items,
    });

    useEffect(() => {
        setData('items', items);
    }, [items]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/pengadaan', {
            onSuccess: () => {
                reset();
            },
        });
    };

    const calculateProcurement = async () => {
        if (!data.pesanan_id) {
            alert('Pilih pesanan terlebih dahulu!');
            return;
        }

        setIsCalculating(true);
        try {
            const response = await fetch('/pengadaan/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ pesanan_id: data.pesanan_id }),
                credentials: 'same-origin',
            });

            const result: ProcurementCalculation = await response.json();

            if (result.success) {
                setCalculationResult(result);
                setItems(result.items);
                setShowManualAdd(true);
            } else {
                alert('Gagal menghitung kebutuhan procurement');
            }
        } catch (error) {
            console.error('Error calculating procurement:', error);
            alert('Terjadi kesalahan saat menghitung');
        } finally {
            setIsCalculating(false);
        }
    };

    const addManualItem = () => {
        const newItems = [
            ...items,
            {
                jenis_barang: 'bahan_baku' as const,
                barang_id: '',
                pemasok_id: '',
                qty_diminta: 1,
                catatan: '',
            },
        ];
        setItems(newItems);
    };

    const removeItem = (index: number) => {
        const newItems = items.filter((_, i) => i !== index);
        setItems(newItems);
    };

    const updateItem = (index: number, field: keyof ItemDetail, value: string | number) => {
        const newItems = [...items];
        newItems[index] = { ...newItems[index], [field]: value };

        // Reset barang_id when jenis_barang changes
        if (field === 'jenis_barang') {
            newItems[index].barang_id = '';
            newItems[index].pemasok_id = '';
        }

        // Update item details when barang_id changes
        if (field === 'barang_id' && value) {
            const itemDetails = getItemDetails(newItems[index].jenis_barang, value as string);
            if (itemDetails) {
                newItems[index].nama_item = itemDetails.nama;
                newItems[index].satuan = itemDetails.satuan;
                newItems[index].harga_satuan = itemDetails.harga;
                if (!newItems[index].qty_diminta || newItems[index].qty_diminta === 1) {
                    newItems[index].qty_diminta = itemDetails.eoq;
                }
            }
        }

        setItems(newItems);
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
            const harga = item.harga_satuan || 0;
            const qty = item.qty_diminta || 0;
            return total + harga * qty;
        }, 0);
    };

    const getSelectedPesananDetails = () => {
        return pesanan.find((p) => p.pesanan_id === data.pesanan_id);
    };

    const selectedPesanan = getSelectedPesananDetails();

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
                        <h3 className="text-sm font-medium text-blue-800">Sistem Otomatis Pengadaan</h3>
                        <div className="mt-2 text-sm text-blue-700">
                            <p>• Pilih pesanan dan klik "Hitung Kebutuhan" untuk otomatis menghitung procurement</p>
                            <p>• Sistem akan menghitung kebutuhan produk dan bahan baku berdasarkan stok yang ada</p>
                            <p>• Jumlah procurement = EOQ + kekurangan stok untuk memenuhi pesanan</p>
                            <p>• Anda dapat menambah item manual atau mengubah quantity sesuai kebutuhan</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Basic Information */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <Label htmlFor="pesanan_id">Pesanan *</Label>
                    <div className="mt-1 flex gap-2">
                        <Select value={data.pesanan_id} onValueChange={(value) => setData('pesanan_id', value)}>
                            <SelectTrigger className={cn('flex-1', errors.pesanan_id && 'border-red-500')}>
                                <SelectValue placeholder="Pilih Pesanan" />
                            </SelectTrigger>
                            <SelectContent>
                                {pesanan.map((order) => (
                                    <SelectItem key={order.pesanan_id} value={order.pesanan_id}>
                                        {order.display_text}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button
                            type="button"
                            onClick={calculateProcurement}
                            disabled={!data.pesanan_id || isCalculating}
                            variant="outline"
                            className="flex items-center gap-2"
                        >
                            <CalculatorIcon className="h-4 w-4" />
                            {isCalculating ? 'Menghitung...' : 'Hitung Kebutuhan'}
                        </Button>
                    </div>
                    {errors.pesanan_id && <p className="mt-1 text-sm text-red-600">{errors.pesanan_id}</p>}
                    {selectedPesanan && (
                        <div className="mt-2 rounded bg-gray-50 p-3">
                            <div className="grid grid-cols-2 gap-2 text-xs text-gray-600">
                                <span>
                                    Status: <strong className="text-gray-900 capitalize">{selectedPesanan.status}</strong>
                                </span>
                                <span>
                                    Total: <strong className="text-gray-900">Rp {selectedPesanan.total_harga.toLocaleString('id-ID')}</strong>
                                </span>
                            </div>
                            {selectedPesanan.produk && selectedPesanan.produk.length > 0 && (
                                <div className="mt-2">
                                    <strong className="text-xs text-gray-700">Produk yang dipesan:</strong>
                                    <div className="mt-1 space-y-1">
                                        {selectedPesanan.produk.map((produk) => (
                                            <div key={produk.produk_id} className="flex justify-between text-xs">
                                                <span>{produk.nama_produk}</span>
                                                <span className="font-medium">
                                                    {produk.jumlah_produk} {produk.satuan_produk}
                                                    <span className="ml-2 text-gray-500">(Stok: {produk.stok_produk})</span>
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
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

            {/* Calculation Result Summary */}
            {calculationResult && (
                <div className="rounded-lg border border-green-200 bg-green-50 p-4">
                    <h3 className="font-medium text-green-800">Hasil Perhitungan Otomatis</h3>
                    <div className="mt-2 grid grid-cols-2 gap-4 text-sm text-green-700">
                        <span>
                            Total Item Diperlukan: <strong>{calculationResult.summary.total_items}</strong>
                        </span>
                        <span>
                            Estimasi Total Biaya: <strong>Rp {calculationResult.summary.total_cost.toLocaleString('id-ID')}</strong>
                        </span>
                    </div>
                </div>
            )}

            {/* Items Section */}
            <div className="border-t pt-6">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className={cn(colors.text.primary, 'text-lg font-medium')}>Item Pengadaan</h3>
                    {showManualAdd && (
                        <Button type="button" onClick={addManualItem} variant="outline" size="sm" className="flex items-center gap-2">
                            <PlusIcon className="h-4 w-4" />
                            Tambah Item Manual
                        </Button>
                    )}
                </div>

                {items.length === 0 && !calculationResult && (
                    <div className="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center">
                        <CalculatorIcon className="mx-auto h-12 w-12 text-gray-400" />
                        <h3 className="mt-2 text-sm font-medium text-gray-900">Belum ada item procurement</h3>
                        <p className="mt-1 text-sm text-gray-500">
                            Pilih pesanan dan klik "Hitung Kebutuhan" untuk mendapatkan daftar item yang perlu diprocurement
                        </p>
                    </div>
                )}

                <div className="space-y-4">
                    {items.map((item, index) => {
                        const itemDetails = getItemDetails(item.jenis_barang, item.barang_id); // Selalu ambil dari sumber utama
                        const isCritical = itemDetails ? itemDetails.stok <= itemDetails.rop : false; // Cek stok dari sini
                        const isCalculatedItem = !!item.nama_item;

                        return (
                            <div
                                key={index}
                                className={cn(
                                    'rounded-lg border p-4',
                                    colors.border.primary,
                                    isCritical && 'border-red-300 bg-red-50',
                                    isCalculatedItem && 'border-blue-300 bg-blue-50',
                                )}
                            >
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-6">
                                    <div>
                                        <Label>Tipe Item</Label>
                                        <Select
                                            value={item.jenis_barang}
                                            onValueChange={(value: 'bahan_baku' | 'produk') => updateItem(index, 'jenis_barang', value)}
                                            disabled={isCalculatedItem}
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
                                        {isCalculatedItem ? (
                                            <div className="mt-1 rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                                                {item.nama_item} ({item.satuan})
                                            </div>
                                        ) : (
                                            <Select value={item.barang_id} onValueChange={(value) => updateItem(index, 'barang_id', value)}>
                                                <SelectTrigger className="mt-1">
                                                    <SelectValue placeholder="Pilih Item" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {getItemOptions(item.jenis_barang).map((option) => (
                                                        <SelectItem key={option.value} value={option.value}>
                                                            {option.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        )}
                                    </div>

                                    <div className="md:col-span-2">
                                        <Label>Pemasok *</Label>
                                        {item.jenis_barang === 'bahan_baku' ? (
                                            <>
                                                <Select
                                                    value={item.pemasok_id || ''}
                                                    onValueChange={(value) => updateItem(index, 'pemasok_id', value)}
                                                >
                                                    <SelectTrigger className={cn('mt-1', errors[`items.${index}.pemasok_id`] && 'border-red-500')}>
                                                        <SelectValue placeholder="Pilih Pemasok" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {pemasoks.map((pemasok) => (
                                                            <SelectItem key={pemasok.pemasok_id} value={pemasok.pemasok_id}>
                                                                {pemasok.nama_pemasok}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                {errors[`items.${index}.pemasok_id`] && (
                                                    <p className="mt-1 text-sm text-red-600">{errors[`items.${index}.pemasok_id`]}</p>
                                                )}
                                            </>
                                        ) : (
                                            <div className="mt-1 rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500">
                                                -
                                            </div>
                                        )}
                                    </div>

                                    {/* Qty and Remove Button */}
                                    <div className="flex items-end justify-between gap-2">
                                        <div className="flex-1">
                                            <Label>Qty</Label>
                                            <Input
                                                type="number"
                                                value={item.qty_diminta}
                                                onChange={(e) => updateItem(index, 'qty_diminta', parseInt(e.target.value) || 0)}
                                                className="mt-1"
                                                min="1"
                                            />
                                        </div>
                                        <Button
                                            type="button"
                                            onClick={() => removeItem(index)}
                                            variant="outline"
                                            size="icon"
                                            className="h-9 w-9 text-red-600 hover:text-red-700"
                                        >
                                            <TrashIcon className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                {/* Item Details Display */}
                                {itemDetails && (
                                    <div className="mt-4 grid grid-cols-2 gap-4 rounded bg-white p-3 md:grid-cols-4">
                                        <div>
                                            <Label className="text-xs text-gray-600">Harga Satuan</Label>
                                            <div className="text-sm font-medium text-gray-900">Rp {itemDetails.harga.toLocaleString('id-ID')}</div>
                                        </div>
                                        <div>
                                            <Label className="text-xs text-gray-600">Satuan</Label>
                                            <div className="text-sm font-medium text-gray-900">{itemDetails.satuan}</div>
                                        </div>
                                        <div>
                                            <Label className="text-xs text-gray-600">Qty</Label>
                                            <div className="text-sm font-medium text-blue-600">
                                                {item.qty_diminta} {itemDetails.satuan}
                                            </div>
                                        </div>
                                        <div>
                                            <Label className="text-xs text-gray-600">Total Harga</Label>
                                            <div className="text-sm font-bold text-green-600">
                                                Rp {(item.qty_diminta * itemDetails.harga).toLocaleString('id-ID')}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {isCalculatedItem && (
                                    <div className="mt-2 rounded border border-blue-200 bg-blue-100 p-2 text-sm text-blue-700">
                                        📊 Item hasil perhitungan otomatis
                                    </div>
                                )}

                                {isCritical && (
                                    <div className="mt-2 rounded border border-red-200 bg-red-100 p-2 text-sm text-red-700">
                                        ⚠️ Stok kritis! Perlu segera diprocurement
                                    </div>
                                )}

                                <div className="mt-4">
                                    <Label>Catatan Item</Label>
                                    <Textarea
                                        value={item.catatan}
                                        onChange={(e) => updateItem(index, 'catatan', e.target.value)}
                                        className="mt-1"
                                        placeholder="Catatan untuk item ini..."
                                        rows={isCalculatedItem ? 3 : 2}
                                    />
                                </div>
                            </div>
                        );
                    })}
                </div>

                {errors.items && <p className="mt-2 text-sm text-red-600">{errors.items}</p>}

                {items.length > 0 && (
                    <div className="mt-4 rounded-lg bg-gray-50 p-4">
                        <div className="flex items-center justify-between">
                            <span className="font-medium">Total Estimasi Biaya:</span>
                            <span className="text-lg font-bold">Rp {calculateTotal().toLocaleString('id-ID')}</span>
                        </div>
                        <div className="mt-2 text-sm text-gray-600">Total {items.length} item akan diprocurement</div>
                    </div>
                )}
            </div>
        </FormTemplate>
    );
}
