import FormTemplate from '@/components/form/form-template';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { colors } from '@/lib/colors';
import { formatCurrency } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { InformationCircleIcon, TrashIcon } from '@heroicons/react/24/outline';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

// --- INTERFACES ---
interface Supplier {
    supplier_id: string;
    nama_supplier: string;
}

interface PengadaanDetail {
    pengadaan_detail_id: string;
    supplier_id: string;
    supplier_nama: string;
    item_type: 'bahan_baku' | 'produk';
    item_id: string;
    nama_item: string;
    satuan: string;
    qty_disetujui: number;
    harga_satuan: number;
    total_harga: number;
}

interface Pengadaan {
    pengadaan_id: string;
    jenis_pengadaan: string;
    tanggal_pengadaan: string;
    status: string;
    display_text: string;
    detail: PengadaanDetail[];
}

interface ItemPembelian {
    pengadaan_detail_id: string;
    item_type: 'bahan_baku' | 'produk';
    item_id: string;
    nama_item: string;
    satuan: string;
    qty_dipesan: number;
    harga_satuan: number;
}

interface Props {
    pengadaans: Pengadaan[]; // Daftar pengadaan yang sudah disetujui (finance_approved)
    suppliers: Supplier[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pembelian', href: '/pembelian' },
    { title: 'Buat Purchase Order', href: '#' },
];

export default function Create({ pengadaans, suppliers }: Props) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        pengadaan_id: '',
        supplier_id: '',
        nomor_po: '',
        tanggal_pembelian: new Date().toISOString().split('T')[0],
        tanggal_kirim_diharapkan: '',
        catatan: '',
        items: [] as ItemPembelian[],
    });

    // --- LOGIC ---

    const selectedPengadaan = React.useMemo(() => {
        return pengadaans.find((p) => p.pengadaan_id === data.pengadaan_id);
    }, [data.pengadaan_id, pengadaans]);

    const availableSuppliers = React.useMemo(() => {
        if (!selectedPengadaan) return [];
        const supplierIds = new Set(selectedPengadaan.detail.map((d) => d.supplier_id));
        return suppliers.filter((s) => supplierIds.has(s.supplier_id));
    }, [selectedPengadaan, suppliers]);

    React.useEffect(() => {
        if (selectedPengadaan && data.supplier_id) {
            const itemsForSupplier = selectedPengadaan.detail
                .filter((d) => d.supplier_id === data.supplier_id)
                .map((d) => ({
                    pengadaan_detail_id: d.pengadaan_detail_id,
                    item_type: d.item_type,
                    item_id: d.item_id,
                    nama_item: d.nama_item,
                    satuan: d.satuan,
                    qty_dipesan: d.qty_disetujui,
                    harga_satuan: d.harga_satuan,
                }));
            setData('items', itemsForSupplier);
        } else {
            setData('items', []);
        }
    }, [data.pengadaan_id, data.supplier_id, selectedPengadaan]);

    const handlePengadaanChange = (pengadaanId: string) => {
        setData((prevData) => ({
            ...prevData,
            pengadaan_id: pengadaanId,
            supplier_id: '', // Reset supplier
            items: [],
        }));
        clearErrors();
    };

    const updateItemQty = (index: number, qty: number) => {
        setData(
            'items',
            data.items.map((item, i) => (i === index ? { ...item, qty_dipesan: qty } : item)),
        );
    };

    const removeItem = (index: number) => {
        setData(
            'items',
            data.items.filter((_, i) => i !== index),
        );
    };

    const calculateTotal = () => {
        return data.items.reduce((total, item) => {
            return total + (item.qty_dipesan || 0) * (item.harga_satuan || 0);
        }, 0);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/pembelian', {
            onSuccess: () => reset(),
        });
    };

    // --- RENDER ---

    return (
        <FormTemplate
            title="Buat Purchase Order (PO) Baru"
            breadcrumbs={breadcrumbs}
            backUrl="/pembelian"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Simpan Purchase Order"
            processingText="Menyimpan..."
        >
            <Head title="Buat PO Baru" />

            <div className="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div className="flex items-start">
                    <div className="flex-shrink-0">
                        <InformationCircleIcon className="h-5 w-5 text-blue-400" />
                    </div>
                    <div className="ml-3">
                        <h3 className="text-sm font-medium text-blue-800">Alur Pembuatan PO</h3>
                        <div className="mt-2 text-sm text-blue-700">
                            <p>1. Pilih Pengadaan yang sudah disetujui oleh Keuangan.</p>
                            <p>2. Pilih Supplier yang akan dituju untuk PO ini.</p>
                            <p>3. Sistem akan otomatis memuat item yang relevan untuk Supplier tersebut.</p>
                            <p>4. Lengkapi detail PO lainnya dan simpan.</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Header PO */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <Label htmlFor="pengadaan_id">Pilih Pengadaan (Disetujui Keuangan) *</Label>
                    <Select value={data.pengadaan_id} onValueChange={handlePengadaanChange}>
                        <SelectTrigger className={cn('mt-1', errors.pengadaan_id && 'border-red-500')}>
                            <SelectValue placeholder="Pilih ID Pengadaan..." />
                        </SelectTrigger>
                        <SelectContent>
                            {pengadaans.map((p) => (
                                <SelectItem key={p.pengadaan_id} value={p.pengadaan_id}>
                                    {p.display_text}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.pengadaan_id && <p className="mt-1 text-sm text-red-600">{errors.pengadaan_id}</p>}
                </div>

                <div>
                    <Label htmlFor="supplier_id">Supplier *</Label>
                    <Select value={data.supplier_id} onValueChange={(value) => setData('supplier_id', value)} disabled={!data.pengadaan_id}>
                        <SelectTrigger className={cn('mt-1', errors.supplier_id && 'border-red-500')}>
                            <SelectValue placeholder={data.pengadaan_id ? 'Pilih Supplier...' : 'Pilih Pengadaan Dulu'} />
                        </SelectTrigger>
                        <SelectContent>
                            {availableSuppliers.map((s) => (
                                <SelectItem key={s.supplier_id} value={s.supplier_id}>
                                    {s.nama_supplier}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.supplier_id && <p className="mt-1 text-sm text-red-600">{errors.supplier_id}</p>}
                </div>

                <div>
                    <Label htmlFor="nomor_po">Nomor PO (Opsional)</Label>
                    <Input
                        id="nomor_po"
                        value={data.nomor_po}
                        onChange={(e) => setData('nomor_po', e.target.value)}
                        className="mt-1"
                        placeholder="Otomatis jika kosong"
                    />
                    {errors.nomor_po && <p className="mt-1 text-sm text-red-600">{errors.nomor_po}</p>}
                </div>

                <div>
                    <Label htmlFor="tanggal_pembelian">Tanggal Pembelian *</Label>
                    <Input
                        id="tanggal_pembelian"
                        type="date"
                        value={data.tanggal_pembelian}
                        onChange={(e) => setData('tanggal_pembelian', e.target.value)}
                        className={cn('mt-1', errors.tanggal_pembelian && 'border-red-500')}
                    />
                    {errors.tanggal_pembelian && <p className="mt-1 text-sm text-red-600">{errors.tanggal_pembelian}</p>}
                </div>

                <div className="md:col-span-2">
                    <Label htmlFor="tanggal_kirim_diharapkan">Tanggal Kirim Diharapkan</Label>
                    <Input
                        id="tanggal_kirim_diharapkan"
                        type="date"
                        value={data.tanggal_kirim_diharapkan}
                        onChange={(e) => setData('tanggal_kirim_diharapkan', e.target.value)}
                        className="mt-1"
                    />
                    {errors.tanggal_kirim_diharapkan && <p className="mt-1 text-sm text-red-600">{errors.tanggal_kirim_diharapkan}</p>}
                </div>

                <div className="md:col-span-2">
                    <Label htmlFor="catatan">Catatan</Label>
                    <Textarea
                        id="catatan"
                        value={data.catatan}
                        onChange={(e) => setData('catatan', e.target.value)}
                        className="mt-1"
                        rows={3}
                        placeholder="Catatan tambahan untuk supplier..."
                    />
                </div>
            </div>

            {/* Detail Item Pembelian */}
            <div className="mt-8 border-t pt-6">
                <h3 className={cn(colors.text.primary, 'text-lg font-medium')}>Item Purchase Order</h3>

                {data.items.length === 0 ? (
                    <div className="mt-4 rounded-lg border-2 border-dashed border-gray-300 p-8 text-center">
                        <p className="text-sm text-gray-500">Pilih Pengadaan dan Supplier untuk memuat item.</p>
                    </div>
                ) : (
                    <div className="mt-4 space-y-4">
                        {data.items.map((item, index) => (
                            <div key={item.pengadaan_detail_id} className="rounded-lg border bg-white p-4">
                                <div className="grid grid-cols-1 items-start gap-4 md:grid-cols-12">
                                    <div className="md:col-span-6">
                                        <p className="font-medium">{item.nama_item}</p>
                                        <p className="text-sm text-gray-500">{item.item_type === 'bahan_baku' ? 'Bahan Baku' : 'Produk'}</p>
                                    </div>
                                    <div className="md:col-span-2">
                                        <Label>Harga Satuan</Label>
                                        <p className="mt-1 text-sm">{formatCurrency(item.harga_satuan)}</p>
                                    </div>
                                    <div className="md:col-span-3">
                                        <Label htmlFor={`qty-${index}`}>Qty Dipesan ({item.satuan})</Label>
                                        <div className="mt-1 flex items-center gap-2">
                                            <Input
                                                id={`qty-${index}`}
                                                type="number"
                                                value={item.qty_dipesan}
                                                onChange={(e) => updateItemQty(index, parseInt(e.target.value, 10) || 0)}
                                                min="1"
                                                className={cn(errors[`items.${index}.qty_dipesan`] && 'border-red-500')}
                                            />
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
                                    <div className="text-right md:col-span-1">
                                        <Label>Subtotal</Label>
                                        <p className="mt-1 font-medium">{formatCurrency(item.qty_dipesan * item.harga_satuan)}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {errors.items && <p className="mt-2 text-sm text-red-600">{errors.items}</p>}

                {data.items.length > 0 && (
                    <div className="mt-6 rounded-lg bg-gray-50 p-4">
                        <div className="flex items-center justify-between">
                            <span className="font-medium">Total Biaya PO:</span>
                            <span className="text-xl font-bold">{formatCurrency(calculateTotal())}</span>
                        </div>
                        <div className="mt-2 text-sm text-gray-600">Total {data.items.length} item akan dipesan.</div>
                    </div>
                )}
            </div>
        </FormTemplate>
    );
}
