import { InformationCircleIcon } from '@heroicons/react/24/outline';
import { Head, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import React, { useEffect, useState } from 'react';
import FormTemplate from '../../components/form/form-template';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../components/ui/select';
import { Textarea } from '../../components/ui/textarea';
import { colors } from '../../lib/colors';
import { cn } from '../../lib/utils';
import { BreadcrumbItem } from '../../types';

// --- INTERFACES ---
interface PembelianOption {
    pembelian_id: string;
    display_text: string;
}

interface ItemPenerimaan {
    pembelian_detail_id: string;
    item_id: string;
    nama_item: string;
    satuan: string;
    qty_dipesan: number;
    qty_diterima_sebelumnya: number;
    qty_sisa: number;
    qty_diterima: number;
}

interface Props {
    pembelians: PembelianOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Penerimaan Bahan Baku', href: '/penerimaan-bahan-baku' },
    { title: 'Tambah Penerimaan Bahan Baku', href: '#' },
];

export default function Create({ pembelians = [] }: Props) {
    const [items, setItems] = useState<ItemPenerimaan[]>([]);
    const [isFetchingDetails, setIsFetchingDetails] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        pembelian_id: '',
        nomor_surat_jalan: '',
        tanggal_penerimaan: new Date().toISOString().split('T')[0],
        catatan: '',
        items: [] as ItemPenerimaan[],
    });

    useEffect(() => {
        setData('items', items);
    }, [items]);

    const handlePembelianChange = async (pembelianId: string) => {
        setData('pembelian_id', pembelianId);
        if (!pembelianId) {
            setItems([]);
            return;
        }

        setIsFetchingDetails(true);
        try {
            const response = await fetch(`/penerimaan/pembelian/${pembelianId}/details`);
            if (!response.ok) throw new Error('Gagal memuat detail pembelian.');
            const fetchedItems: Omit<ItemPenerimaan, 'qty_diterima'>[] = await response.json();

            const itemsWithQty = fetchedItems.map((item) => ({
                ...item,
                qty_diterima: 0,
            }));
            setItems(itemsWithQty);
        } catch (error) {
            console.error(error);
            alert('Terjadi kesalahan saat mengambil detail item PO.');
            setItems([]);
        } finally {
            setIsFetchingDetails(false);
        }
    };

    const updateItemQty = (index: number, value: number) => {
        const newItems = [...items];
        const item = newItems[index];

        // Pastikan tidak melebihi sisa dan tidak negatif
        const newValue = Math.max(0, Math.min(value, item.qty_sisa));
        newItems[index] = { ...item, qty_diterima: newValue };

        setItems(newItems);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const itemsToSubmit = items.filter((item) => item.qty_diterima > 0);

        if (itemsToSubmit.length === 0) {
            alert('Harap masukkan kuantitas diterima minimal untuk satu item.');
            return;
        }

        setData('items', itemsToSubmit);
        post('/penerimaan-bahan-baku', {
            onSuccess: () => {
                reset();
                setItems([]);
            },
            preserveState: (page) => Object.keys(page.props.errors).length > 0,
        });
    };

    return (
        <FormTemplate
            title="Tambah Penerimaan Bahan Baku"
            backUrl="/penerimaan-bahan-baku"
            onSubmit={handleSubmit}
            processing={processing}
            breadcrumbs={breadcrumbs}
            processingText="Menyimpan..."
        >
            <Head title="Tambah Penerimaan Bahan Baku" />

            <div className="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div className="flex items-start">
                    <div className="flex-shrink-0">
                        <InformationCircleIcon className="h-5 w-5 text-blue-400" />
                    </div>
                    <div className="ml-3">
                        <h3 className="text-sm font-medium text-blue-800">Alur Pencatatan Penerimaan Bahan Baku</h3>
                        <div className="mt-2 text-sm text-blue-700">
                            <p>1. Pilih Purchase Order (PO) yang barangnya telah tiba.</p>
                            <p>2. Masukkan kuantitas yang diterima (kondisi baik).</p>
                            <p>3. Kuantitas diterima tidak dapat melebihi sisa kuantitas pada PO.</p>
                            <p>4. Setelah disimpan, stok akan bertambah sesuai kuantitas yang diterima.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <Label htmlFor="pembelian_id">Purchase Order (PO) Terkait *</Label>
                    <Select value={data.pembelian_id} onValueChange={handlePembelianChange}>
                        <SelectTrigger className={cn('mt-1', errors.pembelian_id && 'border-red-500')}>
                            <SelectValue placeholder="Pilih No. PO..." />
                        </SelectTrigger>
                        <SelectContent>
                            {pembelians &&
                                Array.isArray(pembelians) &&
                                pembelians.map((p) => (
                                    <SelectItem key={p.pembelian_id} value={p.pembelian_id}>
                                        {p.display_text}
                                    </SelectItem>
                                ))}
                        </SelectContent>
                    </Select>
                    {errors.pembelian_id && <p className="mt-1 text-sm text-red-600">{errors.pembelian_id}</p>}
                </div>
                <div>
                    <Label htmlFor="nomor_surat_jalan">Nomor Surat Jalan (Pemasok) *</Label>
                    <Input
                        id="nomor_surat_jalan"
                        value={data.nomor_surat_jalan || ''}
                        onChange={(e) => setData('nomor_surat_jalan', e.target.value)}
                        className={cn('mt-1', errors.nomor_surat_jalan && 'border-red-500')}
                        placeholder="Contoh: SJ-2024-123"
                    />
                    {errors.nomor_surat_jalan && <p className="mt-1 text-sm text-red-600">{errors.nomor_surat_jalan}</p>}
                </div>
                <div className="md:col-span-2">
                    <Label htmlFor="tanggal_penerimaan">Tanggal Penerimaan *</Label>
                    <Input
                        id="tanggal_penerimaan"
                        type="date"
                        value={data.tanggal_penerimaan || ''}
                        onChange={(e) => setData('tanggal_penerimaan', e.target.value)}
                        className={cn('mt-1', errors.tanggal_penerimaan && 'border-red-500')}
                    />
                    {errors.tanggal_penerimaan && <p className="mt-1 text-sm text-red-600">{errors.tanggal_penerimaan}</p>}
                </div>
                <div className="md:col-span-2">
                    <Label htmlFor="catatan">Catatan</Label>
                    <Textarea
                        id="catatan"
                        value={data.catatan || ''}
                        onChange={(e) => setData('catatan', e.target.value)}
                        className="mt-1"
                        rows={3}
                        placeholder="Catatan tambahan mengenai penerimaan (opsional)..."
                    />
                </div>
            </div>

            <div className="mt-8 border-t pt-6">
                <h3 className={cn(colors.text.primary, 'text-lg font-medium')}>Item yang Diproses</h3>

                {isFetchingDetails && (
                    <div className="mt-4 flex items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-8 text-center">
                        <Loader2 className="mr-2 h-6 w-6 animate-spin text-gray-500" />
                        <p className="text-gray-600">Memuat detail item...</p>
                    </div>
                )}

                {!isFetchingDetails && items.length > 0 && (
                    <div className="mt-4 space-y-4">
                        {items.map((item, index) => (
                            <div key={item.pembelian_detail_id} className="rounded-lg border bg-white p-4">
                                <div className="grid grid-cols-2 items-end gap-x-4 gap-y-2 md:grid-cols-9">
                                    <div className="col-span-2 md:col-span-4">
                                        <Label>Nama Item</Label>
                                        <p className="font-medium">{item.nama_item}</p>
                                    </div>
                                    <div className="md:col-span-2">
                                        <Label>Sisa di PO</Label>
                                        <p className="font-semibold text-blue-600">{`${item.qty_sisa} ${item.satuan}`}</p>
                                    </div>
                                    <div className="col-span-2 md:col-span-3">
                                        <Label htmlFor={`qty-diterima-${index}`}>Qty Diterima *</Label>
                                        <Input
                                            id={`qty-diterima-${index}`}
                                            type="number"
                                            value={item.qty_diterima}
                                            onChange={(e) => updateItemQty(index, parseInt(e.target.value, 10) || 0)}
                                            min="0"
                                            max={item.qty_sisa}
                                            className={cn('mt-1 font-semibold', errors[`items.${index}.qty_diterima`] && 'border-red-500')}
                                        />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
                {errors.items && <p className="mt-4 text-sm text-red-600">{errors.items}</p>}
            </div>
        </FormTemplate>
    );
}
