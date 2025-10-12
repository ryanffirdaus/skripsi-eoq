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

interface Pemasok {
    pemasok_id: string;
    nama_pemasok: string;
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
    harga_satuan: string;
    total_harga: string;
    catatan?: string;
    pemasok: {
        pemasok_id: string;
        nama_pemasok: string;
    } | null;
}

interface Pengadaan {
    pengadaan_id: string;
    jenis_pengadaan: string;
    pesanan_id?: string;
    tanggal_pengadaan: string;
    catatan?: string;
    detail: PengadaanDetail[];
}

interface Props {
    pengadaan: Pengadaan;
    pemasoks: Pemasok[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengadaan', href: '/pengadaan' },
    { title: 'Edit Pengadaan', href: '#' },
];

export default function Edit({ pengadaan, pemasoks }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        tanggal_pengadaan: pengadaan.tanggal_pengadaan,
        catatan: pengadaan.catatan || '',
        details: pengadaan.detail.map((item) => ({
            pengadaan_detail_id: item.pengadaan_detail_id,
            pemasok_id: item.pemasok?.pemasok_id || '',
        })),
    });

    const handleDetailChange = (index: number, field: string, value: string) => {
        const updatedDetails = [...data.details];
        updatedDetails[index] = { ...updatedDetails[index], [field]: value };
        setData('details', updatedDetails);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/pengadaan/${pengadaan.pengadaan_id}`);
    };

    const calculateTotal = () => {
        return pengadaan.detail.reduce((total, item) => {
            const hargaAsNumber = parseFloat(item.total_harga);

            return total + (isNaN(hargaAsNumber) ? 0 : hargaAsNumber);
        }, 0);
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
                    </div>{' '}
                    <div className="ml-3">
                        <h3 className="text-sm font-medium text-blue-800">Informasi Edit Pengadaan</h3>
                        <div className="mt-2 text-sm text-blue-700">
                            <p>• Anda dapat mengubah tanggal, catatan, dan pemasok untuk setiap item bahan baku.</p>
                            <p>• Kuantitas dan jenis item tidak dapat diubah setelah pengadaan dibuat.</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Basic Information */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
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
                <h3 className={cn(colors.text.primary, 'mb-4 text-lg font-medium')}>Item Pengadaan</h3>

                <div className="space-y-4">
                    {pengadaan.detail.map((item, index) => (
                        <div key={item.pengadaan_detail_id} className={cn('rounded-lg border bg-gray-50 p-4', colors.border.primary)}>
                            {/* Main grid for item layout */}
                            <div className="grid grid-cols-1 gap-x-4 gap-y-2 md:grid-cols-6">
                                <div className="md:col-span-2">
                                    <Label className="text-sm font-medium text-gray-700">Nama Item</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">{item.nama_item}</div>
                                </div>

                                {/* Pemasok Dropdown */}
                                <div className="md:col-span-2">
                                    <Label className="text-sm font-medium text-gray-700">Pemasok</Label>
                                    {item.item_type === 'bahan_baku' ? (
                                        <>
                                            <Select
                                                value={data.details[index].pemasok_id || ''}
                                                onValueChange={(value) => handleDetailChange(index, 'pemasok_id', value)}
                                            >
                                                <SelectTrigger
                                                    className={cn('mt-1 bg-white', errors[`details.${index}.pemasok_id`] && 'border-red-500')}
                                                >
                                                    <SelectValue placeholder="Pilih Pemasok (Opsional)" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {pemasoks.map((pemasok) => (
                                                        <SelectItem key={pemasok.pemasok_id} value={pemasok.pemasok_id}>
                                                            {pemasok.nama_pemasok}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors[`details.${index}.pemasok_id`] && (
                                                <p className="mt-1 text-sm text-red-600">{errors[`details.${index}.pemasok_id`]}</p>
                                            )}
                                        </>
                                    ) : (
                                        <div className="mt-1 rounded border bg-gray-200 p-2 text-sm text-gray-600">- (Produk Internal)</div>
                                    )}
                                </div>

                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Qty Diminta</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">
                                        {item.qty_diminta} {item.satuan}
                                    </div>
                                </div>

                                {/* This div was misplaced. Now it's inside the main grid. */}
                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Harga Satuan</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">Rp {item.harga_satuan}</div>
                                </div>
                            </div>{' '}
                            {/* End of the main grid */}
                            {item.catatan && (
                                <div className="mt-4">
                                    <Label className="text-sm font-medium text-gray-700">Catatan Item</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">{item.catatan}</div>
                                </div>
                            )}
                            {/* Qty progress display */}
                            {(item.qty_disetujui != null || item.qty_diterima != null) && (
                                <div className="mt-4 grid grid-cols-3 gap-4">
                                    <div>
                                        <Label className="text-sm font-medium text-gray-700">Qty Disetujui</Label>
                                        <div className="mt-1 rounded border border-blue-200 bg-blue-50 p-2 text-sm">
                                            {item.qty_disetujui ? item.qty_disetujui : '-'} {item.satuan}
                                        </div>
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium text-gray-700">Qty Diterima</Label>
                                        <div className="mt-1 rounded border border-green-200 bg-green-50 p-2 text-sm">
                                            {item.qty_diterima ? item.qty_diterima : '-'} {item.satuan}
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
                        </div> // This is the closing div for the item card
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
