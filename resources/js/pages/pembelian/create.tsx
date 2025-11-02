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
interface Pemasok {
    pemasok_id: string;
    nama_pemasok: string;
}

interface PengadaanDetail {
    pengadaan_detail_id: string;
    pemasok_id: string;
    pemasok_nama: string;
    jenis_barang: 'bahan_baku' | 'produk';
    barang_id: string;
    nama_item: string;
    satuan: string;
    qty_disetujui: number;
    harga_satuan: number;
    total_harga: number;
}

interface Pengadaan {
    pengadaan_id: string;
    jenis_pengadaan: string;
    tanggal_pengadaan?: string;
    status: string;
    display_text: string;
    detail: PengadaanDetail[];
}

interface ItemPembelian {
    pengadaan_detail_id: string;
    jenis_barang: 'bahan_baku' | 'produk';
    barang_id: string;
    nama_item: string;
    satuan: string;
    qty_dipesan: number;
    harga_satuan: number;
}

interface Props {
    pengadaans: Pengadaan[]; // Daftar pengadaan yang sudah disetujui (finance_approved)
    pemasoks: Pemasok[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pembelian', href: '/pembelian' },
    { title: 'Tambah Pembelian', href: '#' },
];

export default function Create({ pengadaans, pemasoks }: Props) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        pengadaan_id: '',
        pemasok_id: '',
        tanggal_pembelian: new Date().toISOString().split('T')[0],
        tanggal_kirim_diharapkan: '',
        metode_pembayaran: 'tunai' as 'tunai' | 'transfer' | 'termin',
        termin_pembayaran: '',
        jumlah_dp: '',
        catatan: '',
        items: [] as ItemPembelian[],
    });

    // --- LOGIC ---

    const selectedPengadaan = React.useMemo(() => {
        return pengadaans.find((p) => p.pengadaan_id === data.pengadaan_id);
    }, [data.pengadaan_id, pengadaans]);

    const availablePemasoks = React.useMemo(() => {
        if (!selectedPengadaan || !selectedPengadaan.detail || !Array.isArray(selectedPengadaan.detail)) return [];
        if (!pemasoks || !Array.isArray(pemasoks)) return [];
        const pemasokIds = new Set(selectedPengadaan.detail.map((d) => d.pemasok_id));
        return pemasoks.filter((s) => pemasokIds.has(s.pemasok_id));
    }, [selectedPengadaan, pemasoks]);

    React.useEffect(() => {
        if (selectedPengadaan && selectedPengadaan.detail && Array.isArray(selectedPengadaan.detail) && data.pemasok_id) {
            const itemsForPemasok = selectedPengadaan.detail
                .filter((d) => d.pemasok_id === data.pemasok_id)
                .map((d) => ({
                    pengadaan_detail_id: d.pengadaan_detail_id,
                    jenis_barang: d.jenis_barang,
                    barang_id: d.barang_id,
                    nama_item: d.nama_item,
                    satuan: d.satuan,
                    qty_dipesan: d.qty_disetujui,
                    harga_satuan: d.harga_satuan,
                }));
            setData('items', itemsForPemasok);
        } else {
            setData('items', []);
        }
    }, [data.pengadaan_id, data.pemasok_id, selectedPengadaan]);

    const handlePengadaanChange = (pengadaanId: string) => {
        setData((prevData) => ({
            ...prevData,
            pengadaan_id: pengadaanId,
            pemasok_id: '', // Reset pemasok
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
            title="Tambah Pembelian"
            breadcrumbs={breadcrumbs}
            backUrl="/pembelian"
            onSubmit={handleSubmit}
            processing={processing}
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
                            <p>2. Pilih Pemasok yang akan dituju untuk PO ini.</p>
                            <p>3. Sistem akan otomatis memuat item yang relevan untuk Pemasok tersebut.</p>
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
                    <Label htmlFor="pemasok_id">Pemasok *</Label>
                    <Select value={data.pemasok_id} onValueChange={(value) => setData('pemasok_id', value)} disabled={!data.pengadaan_id}>
                        <SelectTrigger className={cn('mt-1', errors.pemasok_id && 'border-red-500')}>
                            <SelectValue placeholder={data.pengadaan_id ? 'Pilih Pemasok...' : 'Pilih Pengadaan Terlebih Dahulu'} />
                        </SelectTrigger>
                        <SelectContent>
                            {availablePemasoks.map((s) => (
                                <SelectItem key={s.pemasok_id} value={s.pemasok_id}>
                                    {s.nama_pemasok}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.pemasok_id && <p className="mt-1 text-sm text-red-600">{errors.pemasok_id}</p>}
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
            </div>

            {/* Payment Section */}
            <div className="mt-8 border-t pt-6">
                <h3 className={cn(colors.text.primary, 'mb-4 text-lg font-medium')}>Informasi Pembayaran</h3>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <Label htmlFor="metode_pembayaran">Metode Pembayaran *</Label>
                        <Select
                            value={data.metode_pembayaran}
                            onValueChange={(value: 'tunai' | 'transfer' | 'termin') => setData('metode_pembayaran', value)}
                        >
                            <SelectTrigger className={cn('mt-1', errors.metode_pembayaran && 'border-red-500')}>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="tunai">Tunai</SelectItem>
                                <SelectItem value="transfer">Transfer</SelectItem>
                                <SelectItem value="termin">Termin</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.metode_pembayaran && <p className="mt-1 text-sm text-red-600">{errors.metode_pembayaran}</p>}
                        <p className="mt-1 text-xs text-gray-500">
                            {data.metode_pembayaran === 'termin'
                                ? 'Pembayaran bertahap sesuai kesepakatan dengan pemasok'
                                : data.metode_pembayaran === 'tunai'
                                  ? 'Pembayaran tunai langsung'
                                  : 'Pembayaran melalui transfer bank'}
                        </p>
                    </div>

                    {data.metode_pembayaran === 'termin' && (
                        <>
                            <div>
                                <Label htmlFor="jumlah_dp">Jumlah Down Payment (DP)</Label>
                                <Input
                                    id="jumlah_dp"
                                    type="number"
                                    step="0.01"
                                    value={data.jumlah_dp}
                                    onChange={(e) => setData('jumlah_dp', e.target.value)}
                                    className={cn('mt-1', errors.jumlah_dp && 'border-red-500')}
                                    placeholder="Masukkan jumlah DP jika ada"
                                />
                                {errors.jumlah_dp && <p className="mt-1 text-sm text-red-600">{errors.jumlah_dp}</p>}
                                <p className="mt-1 text-xs text-gray-500">Opsional, kosongkan jika tidak ada DP</p>
                            </div>

                            <div className="md:col-span-2">
                                <Label htmlFor="termin_pembayaran">Ketentuan Termin Pembayaran *</Label>
                                <Textarea
                                    id="termin_pembayaran"
                                    value={data.termin_pembayaran}
                                    onChange={(e) => setData('termin_pembayaran', e.target.value)}
                                    className={cn('mt-1', errors.termin_pembayaran && 'border-red-500')}
                                    rows={3}
                                    placeholder="Contoh: 30% DP, 40% setelah pengiriman 50%, 30% setelah barang diterima lengkap"
                                />
                                {errors.termin_pembayaran && <p className="mt-1 text-sm text-red-600">{errors.termin_pembayaran}</p>}
                            </div>
                        </>
                    )}
                </div>
            </div>

            {/* Notes Section */}
            <div className="mt-6">
                <Label htmlFor="catatan">Catatan</Label>
                <Textarea
                    id="catatan"
                    value={data.catatan}
                    onChange={(e) => setData('catatan', e.target.value)}
                    className="mt-1"
                    rows={3}
                    placeholder="Catatan tambahan untuk pemasok..."
                />
            </div>

            {/* Detail Item Pembelian */}
            <div className="mt-8 border-t pt-6">
                <h3 className={cn(colors.text.primary, 'text-lg font-medium')}>Item Purchase Order</h3>

                {data.items.length === 0 ? (
                    <div className="mt-4 rounded-lg border-2 border-dashed border-gray-300 p-8 text-center">
                        <p className="text-sm text-gray-500">Pilih Pengadaan dan Pemasok untuk memuat item.</p>
                    </div>
                ) : (
                    <div className="mt-4 space-y-4">
                        {data.items.map((item, index) => (
                            <div key={item.pengadaan_detail_id} className="rounded-lg border bg-white p-4">
                                <div className="grid grid-cols-1 items-start gap-4 md:grid-cols-12">
                                    <div className="md:col-span-6">
                                        <p className="font-medium">{item.nama_item}</p>
                                        <p className="text-sm text-gray-500">{item.jenis_barang === 'bahan_baku' ? 'Bahan Baku' : 'Produk'}</p>
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
