import FormTemplate from '@/components/form/form-template';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

interface Supplier {
    supplier_id: string;
    nama_supplier: string;
    kontak_person: string;
    telepon: string;
}

interface PengadaanDetail {
    pengadaan_detail_id: string;
    item_type: 'bahan_baku' | 'produk';
    item_id: string;
    nama_item: string;
    satuan: string;
    qty_diminta: number;
    qty_disetujui?: number;
    qty_diterima?: number;
    harga_satuan: number;
    total_harga: number;
    catatan?: string;
}

interface Pengadaan {
    pengadaan_id: string;
    supplier_id: string;
    jenis_pengadaan: string;
    pesanan_id?: string;
    tanggal_pengadaan: string;
    tanggal_dibutuhkan: string;
    prioritas: string;
    alasan_pengadaan?: string;
    catatan?: string;
    detail: PengadaanDetail[];
}

interface Props {
    pengadaan: Pengadaan;
    suppliers: Supplier[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengadaan', href: '/pengadaan' },
    { title: 'Edit Pengadaan', href: '#' },
];

export default function Edit({ pengadaan, suppliers }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        supplier_id: pengadaan.supplier_id,
        tanggal_pengadaan: pengadaan.tanggal_pengadaan,
        tanggal_dibutuhkan: pengadaan.tanggal_dibutuhkan,
        prioritas: pengadaan.prioritas,
        alasan_pengadaan: pengadaan.alasan_pengadaan || '',
        catatan: pengadaan.catatan || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/pengadaan/${pengadaan.pengadaan_id}`);
    };

    const calculateTotal = () => {
        return pengadaan.detail.reduce((total, item) => total + item.total_harga, 0);
    };

    const getPrioritasLabel = (prioritas: string) => {
        const labels: Record<string, string> = {
            low: 'Low',
            normal: 'Normal',
            high: 'High',
            urgent: 'Urgent',
        };
        return labels[prioritas] || prioritas;
    };

    const getPrioritasColor = (prioritas: string) => {
        const colors: Record<string, string> = {
            low: 'bg-blue-100 text-blue-800',
            normal: 'bg-green-100 text-green-800',
            high: 'bg-yellow-100 text-yellow-800',
            urgent: 'bg-red-100 text-red-800',
        };
        return colors[prioritas] || 'bg-gray-100 text-gray-800';
    };

    return (
        <FormTemplate
            title={`Edit Pengadaan ${pengadaan.pengadaan_id}`}
            breadcrumbs={breadcrumbs}
            backUrl="/pengadaan"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Update Pengadaan"
            processingText="Mengupdate..."
        >
            <Head title={`Edit Pengadaan ${pengadaan.pengadaan_id}`} />

            {/* Information Notice */}
            <div className="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div className="flex items-start">
                    <div className="flex-shrink-0">
                        <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                fillRule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clipRule="evenodd"
                            />
                        </svg>
                    </div>
                    <div className="ml-3">
                        <h3 className="text-sm font-medium text-blue-800">Informasi Edit Pengadaan</h3>
                        <div className="mt-2 text-sm text-blue-700">
                            <p>• Item pengadaan tidak dapat diubah setelah pengadaan dibuat</p>
                            <p>• Hanya informasi dasar yang dapat diedit (supplier, tanggal, prioritas, catatan)</p>
                            <p>
                                • Jenis pengadaan: <strong>{pengadaan.jenis_pengadaan.toUpperCase()}</strong>
                            </p>
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

            {/* Items Section - Read Only */}
            <div className="border-t pt-6">
                <h3 className={cn(colors.text.primary, 'mb-4 text-lg font-medium')}>Item Pengadaan (Read Only)</h3>

                <div className="space-y-4">
                    {pengadaan.detail.map((item, index) => (
                        <div key={index} className={cn('rounded-lg border bg-gray-50 p-4', colors.border.primary)}>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-6">
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Tipe Item</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">
                                        {item.item_type === 'bahan_baku' ? 'Bahan Baku' : 'Produk'}
                                    </div>
                                </div>

                                <div className="md:col-span-2">
                                    <Label className="text-sm font-medium text-gray-700">Nama Item</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">{item.nama_item}</div>
                                </div>

                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Qty Diminta</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">
                                        {item.qty_diminta} {item.satuan}
                                    </div>
                                </div>

                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Harga Satuan</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">Rp {item.harga_satuan.toLocaleString('id-ID')}</div>
                                </div>

                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Total</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm font-medium">
                                        Rp {item.total_harga.toLocaleString('id-ID')}
                                    </div>
                                </div>
                            </div>

                            {item.catatan && (
                                <div className="mt-4">
                                    <Label className="text-sm font-medium text-gray-700">Catatan Item</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">{item.catatan}</div>
                                </div>
                            )}

                            {/* Show qty progress if approved/received quantities exist */}
                            {(item.qty_disetujui || item.qty_diterima) && (
                                <div className="mt-4 grid grid-cols-3 gap-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-700">Qty Disetujui</Label>
                                        <div className="mt-1 rounded border border-blue-200 bg-blue-50 p-2 text-sm">
                                            {item.qty_disetujui || 0} {item.satuan}
                                        </div>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-700">Qty Diterima</Label>
                                        <div className="mt-1 rounded border border-green-200 bg-green-50 p-2 text-sm">
                                            {item.qty_diterima || 0} {item.satuan}
                                        </div>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-700">Outstanding</Label>
                                        <div className="mt-1 rounded border border-yellow-200 bg-yellow-50 p-2 text-sm">
                                            {(item.qty_disetujui || item.qty_diminta) - (item.qty_diterima || 0)} {item.satuan}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>

                <div className="mt-4 rounded-lg bg-gray-50 p-4">
                    <div className="flex items-center justify-between">
                        <span className="font-medium">Total Biaya Pengadaan:</span>
                        <span className="text-lg font-bold">Rp {calculateTotal().toLocaleString('id-ID')}</span>
                    </div>
                </div>
            </div>

            {/* Current Status Info */}
            <div className="border-t pt-6">
                <h3 className={cn(colors.text.primary, 'mb-4 text-lg font-medium')}>Informasi Status</h3>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <Label className="text-sm font-medium text-gray-700">Jenis Pengadaan</Label>
                        <div className="mt-1 rounded border bg-white p-2 text-sm">{pengadaan.jenis_pengadaan.toUpperCase()}</div>
                    </div>
                    <div>
                        <Label className="text-sm font-medium text-gray-700">Prioritas Saat Ini</Label>
                        <div className="mt-1">
                            <span className={cn('rounded-full px-2 py-1 text-xs font-medium', getPrioritasColor(pengadaan.prioritas))}>
                                {getPrioritasLabel(pengadaan.prioritas)}
                            </span>
                        </div>
                    </div>
                    {pengadaan.pesanan_id && (
                        <div>
                            <Label className="text-sm font-medium text-gray-700">ID Pesanan Terkait</Label>
                            <div className="mt-1 rounded border bg-white p-2 text-sm">{pengadaan.pesanan_id}</div>
                        </div>
                    )}
                </div>
            </div>
        </FormTemplate>
    );
}
