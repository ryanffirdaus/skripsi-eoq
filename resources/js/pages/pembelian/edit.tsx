import FormTemplate from '@/components/form/form-template';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { colors } from '@/lib/colors';
import { formatCurrency } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { AlertTriangleIcon } from 'lucide-react';
import React from 'react';

// --- INTERFACES ---
interface Pemasok {
    pemasok_id: string;
    nama_pemasok: string;
}

interface PembelianDetail {
    pembelian_detail_id: string;
    nama_item: string;
    satuan: string;
    qty_dipesan: number;
    harga_satuan: number;
}

interface Pembelian {
    pembelian_id: string;
    pengadaan_id?: string;
    pemasok_id: string;
    tanggal_pembelian: string;
    tanggal_kirim_diharapkan?: string;
    total_biaya: number;
    status: string;
    catatan?: string;
    metode_pembayaran: string;
    termin_pembayaran?: string;
    jumlah_dp?: number;
    can_be_edited: boolean;
    detail: PembelianDetail[];
}

interface StatusOption {
    value: string;
    label: string;
}

interface Props {
    pembelian: Pembelian;
    pemasoks: Pemasok[];
    statusOptions: StatusOption[];
    auth?: {
        user: {
            user_id: string;
            role_id: string;
        };
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pembelian', href: '/pembelian' },
    { title: 'Edit Pembelian', href: '#' },
];

export default function Edit({ pembelian, pemasoks, statusOptions, auth }: Props) {
    const isAdmin = auth?.user?.role_id === 'R01';
    const canEdit = isAdmin || pembelian.can_be_edited;

    const { data, setData, put, processing, errors } = useForm({
        status: pembelian.status,
        pemasok_id: pembelian.pemasok_id,
        tanggal_pembelian: pembelian.tanggal_pembelian,
        tanggal_kirim_diharapkan: pembelian.tanggal_kirim_diharapkan || '',
        catatan: pembelian.catatan || '',
        metode_pembayaran: pembelian.metode_pembayaran,
        termin_pembayaran: pembelian.termin_pembayaran || '',
        jumlah_dp: pembelian.jumlah_dp || 0,
        items: pembelian.detail,
    });

    const updateItem = (index: number, field: keyof PembelianDetail, value: string | number) => {
        setData(
            'items',
            data.items.map((item, i) => (i === index ? { ...item, [field]: value } : item)),
        );
    };

    const calculateTotal = () => {
        return data.items.reduce((total, item) => {
            return total + (Number(item.qty_dipesan) || 0) * (Number(item.harga_satuan) || 0);
        }, 0);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/pembelian/${pembelian.pembelian_id}`);
    };

    return (
        <FormTemplate
            title="Edit Pembelian"
            breadcrumbs={breadcrumbs}
            backUrl="/pembelian"
            onSubmit={handleSubmit}
            processing={processing}
            processingText="Menyimpan..."
        >
            <Head title={`Edit PO ${pembelian.pembelian_id}`} />

            {!canEdit && (
                <div className="mb-6 flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                    <AlertTriangleIcon className="h-5 w-5 flex-shrink-0 text-yellow-500" />
                    <div>
                        <h3 className="text-sm font-semibold text-yellow-800">Mode Baca-Saja</h3>
                        <p className="mt-1 text-sm text-yellow-700">
                            Purchase order ini tidak dapat diubah karena statusnya sudah <strong>{pembelian.status}</strong>.
                        </p>
                    </div>
                </div>
            )}

            {/* Header PO */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <Label>Nomor PO</Label>
                    <Input value={pembelian.pembelian_id} disabled className="mt-1 bg-gray-100" />
                </div>
                <div>
                    <Label>Berdasarkan Pengadaan</Label>
                    <Input value={pembelian.pengadaan_id || '-'} disabled className="mt-1 bg-gray-100" />
                </div>
                <div>
                    <Label htmlFor="pemasok_id">Pemasok *</Label>
                    <Select value={data.pemasok_id} onValueChange={(value) => setData('pemasok_id', value)} disabled={!pembelian.can_be_edited}>
                        <SelectTrigger className={cn('mt-1', errors.pemasok_id && 'border-red-500')}>
                            <SelectValue placeholder="Pilih Pemasok" />
                        </SelectTrigger>
                        <SelectContent>
                            {pemasoks &&
                                Array.isArray(pemasoks) &&
                                pemasoks.map((s) => (
                                    <SelectItem key={s.pemasok_id} value={s.pemasok_id}>
                                        {s.nama_pemasok}
                                    </SelectItem>
                                ))}
                        </SelectContent>
                    </Select>
                    {errors.pemasok_id && <p className="mt-1 text-sm text-red-600">{errors.pemasok_id}</p>}
                </div>

                <div>
                    <Label htmlFor="status">Status Pembelian *</Label>
                    <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                        <SelectTrigger className={cn('mt-1', errors.status && 'border-red-500')}>
                            <SelectValue placeholder="Pilih Status" />
                        </SelectTrigger>
                        <SelectContent>
                            {statusOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.status && <p className="mt-1 text-sm text-red-600">{errors.status}</p>}
                </div>

                <div>
                    <Label htmlFor="tanggal_pembelian">Tanggal Pembelian *</Label>
                    <Input
                        id="tanggal_pembelian"
                        type="date"
                        value={data.tanggal_pembelian}
                        onChange={(e) => setData('tanggal_pembelian', e.target.value)}
                        className={cn('mt-1', errors.tanggal_pembelian && 'border-red-500')}
                        disabled={!pembelian.can_be_edited}
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
                        disabled={!pembelian.can_be_edited}
                    />
                    {errors.tanggal_kirim_diharapkan && <p className="mt-1 text-sm text-red-600">{errors.tanggal_kirim_diharapkan}</p>}
                </div>
                <div>
                    <Label htmlFor="metode_pembayaran">Metode Pembayaran *</Label>
                    <Select
                        value={data.metode_pembayaran}
                        onValueChange={(value) => setData('metode_pembayaran', value)}
                        disabled={!pembelian.can_be_edited}
                    >
                        <SelectTrigger className={cn('mt-1', errors.metode_pembayaran && 'border-red-500')}>
                            <SelectValue placeholder="Pilih Metode Pembayaran" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="tunai">Tunai</SelectItem>
                            <SelectItem value="transfer">Transfer</SelectItem>
                            <SelectItem value="termin">Termin</SelectItem>
                        </SelectContent>
                    </Select>
                    {errors.metode_pembayaran && <p className="mt-1 text-sm text-red-600">{errors.metode_pembayaran}</p>}
                </div>
                {data.metode_pembayaran === 'termin' && (
                    <>
                        <div>
                            <Label htmlFor="termin_pembayaran">Termin Pembayaran</Label>
                            <Input
                                id="termin_pembayaran"
                                type="text"
                                value={data.termin_pembayaran}
                                onChange={(e) => setData('termin_pembayaran', e.target.value)}
                                className="mt-1"
                                placeholder="e.g., 30/70"
                                disabled={!pembelian.can_be_edited}
                            />
                        </div>
                        <div>
                            <Label htmlFor="jumlah_dp">Jumlah DP *</Label>
                            <Input
                                id="jumlah_dp"
                                type="number"
                                value={data.jumlah_dp}
                                onChange={(e) => setData('jumlah_dp', parseFloat(e.target.value) || 0)}
                                className={cn('mt-1', errors.jumlah_dp && 'border-red-500')}
                                min="0"
                                disabled={!pembelian.can_be_edited}
                            />
                            {errors.jumlah_dp && <p className="mt-1 text-sm text-red-600">{errors.jumlah_dp}</p>}
                        </div>
                    </>
                )}
                <div className="md:col-span-2">
                    <Label htmlFor="catatan">Catatan</Label>
                    <Textarea
                        id="catatan"
                        value={data.catatan}
                        onChange={(e) => setData('catatan', e.target.value)}
                        className="mt-1"
                        rows={3}
                        placeholder="Catatan tambahan untuk pemasok..."
                        disabled={!pembelian.can_be_edited}
                    />
                </div>
            </div>

            {/* Detail Item Pembelian */}
            <div className="mt-8 border-t pt-6">
                <h3 className={cn(colors.text.primary, 'text-lg font-medium')}>Item Purchase Order</h3>
                <div className="mt-4 space-y-4">
                    {data.items.map((item, index) => (
                        <div key={item.pembelian_detail_id} className="rounded-lg border bg-white p-4">
                            <div className="grid grid-cols-1 items-start gap-4 md:grid-cols-12">
                                <div className="md:col-span-6">
                                    <p className="font-medium">{item.nama_item}</p>
                                    <p className="text-sm text-gray-500">Satuan: {item.satuan}</p>
                                </div>
                                <div className="md:col-span-2">
                                    <Label htmlFor={`harga-${index}`}>Harga Satuan</Label>
                                    <Input
                                        id={`harga-${index}`}
                                        type="number"
                                        value={item.harga_satuan}
                                        onChange={(e) => updateItem(index, 'harga_satuan', parseFloat(e.target.value) || 0)}
                                        className="mt-1"
                                        disabled={!pembelian.can_be_edited}
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <Label htmlFor={`qty-${index}`}>Qty Dipesan</Label>
                                    <Input
                                        id={`qty-${index}`}
                                        type="number"
                                        value={item.qty_dipesan}
                                        onChange={(e) => updateItem(index, 'qty_dipesan', parseInt(e.target.value, 10) || 0)}
                                        min="1"
                                        className="mt-1"
                                        disabled={!pembelian.can_be_edited}
                                    />
                                </div>
                                <div className="text-right md:col-span-2">
                                    <Label>Subtotal</Label>
                                    <p className="mt-1 font-medium">{formatCurrency((item.qty_dipesan || 0) * (item.harga_satuan || 0))}</p>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="mt-6 rounded-lg bg-gray-50 p-4">
                    <div className="flex items-center justify-between">
                        <span className="font-medium">Total Biaya PO:</span>
                        <span className="text-xl font-bold">{formatCurrency(calculateTotal())}</span>
                    </div>
                </div>
            </div>
        </FormTemplate>
    );
}
