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
    pemasok_id: string;
    jenis_barang: 'bahan_baku' | 'produk';
    barang_id: string;
    nama_item: string;
    satuan: string;
    qty_diminta: number;
    harga_satuan: string;
    catatan?: string;
}

interface StatusOption {
    value: string;
    label: string;
}

interface Pengadaan {
    pengadaan_id: string;
    jenis_pengadaan: string;
    pesanan_id?: string;
    status: string;
    catatan?: string;
    total_biaya: number;
    detail: PengadaanDetail[];
}

interface Props {
    pengadaan: Pengadaan;
    pemasoks: Pemasok[];
    statusOptions: StatusOption[];
    auth?: {
        user: {
            user_id: string;
            nama_lengkap: string;
            role_id: string;
        };
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengadaan', href: '/pengadaan' },
    { title: 'Edit Pengadaan', href: '#' },
];

export default function Edit({ pengadaan, pemasoks, statusOptions, auth }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        status: pengadaan.status,
        catatan: pengadaan.catatan || '',
        details: pengadaan.detail.map((item) => ({
            pengadaan_detail_id: item.pengadaan_detail_id,
            pemasok_id: item.pemasok_id || '',
            qty_diminta: item.qty_diminta.toString(),
            harga_satuan: item.harga_satuan || '',
        })),
    });

    const handleDetailChange = (index: number, field: string, value: string) => {
        const updatedDetails = [...data.details];
        updatedDetails[index] = { ...updatedDetails[index], [field]: value };
        setData('details', updatedDetails);
    };

    // Authorization helper functions
    const canEditSupplier = (): boolean => {
        const userRole = auth?.user?.role_id;
        // R01 (Admin) dapat edit status apapun
        // R04 (Staf Pengadaan) and R09 (Manajer Pengadaan) hanya saat menunggu_alokasi_pemasok
        if (userRole === 'R01') {
            return true; // Admin bisa edit di status apapun
        }
        const isPengadaanStaff = userRole === 'R04' || userRole === 'R09';
        const isCorrectStatus = pengadaan.status === 'menunggu_alokasi_pemasok';
        return isPengadaanStaff && isCorrectStatus;
    };

    const canEditPrice = (): boolean => {
        const userRole = auth?.user?.role_id;
        // R01 (Admin) dapat edit status apapun
        if (userRole === 'R01') {
            return true; // Admin bisa edit di status apapun
        }
        // Staf/Manajer Gudang (R02, R07), Staf/Manajer Pengadaan (R04, R09)
        const isAuthorizedRole = ['R02', 'R04', 'R07', 'R09'].includes(userRole || '');
        // Only when status is draft or menunggu_alokasi_pemasok
        const isEditableStatus = pengadaan.status === 'draft' || pengadaan.status === 'menunggu_alokasi_pemasok';
        return isAuthorizedRole && isEditableStatus;
    };

    // Check apakah harga masih bisa diedit (hanya untuk status pending, ditolak_procurement, ditolak_finance)
    const isPriceEditable = canEditPrice();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/pengadaan/${pengadaan.pengadaan_id}`);
    };

    const calculateTotal = () => {
        return data.details.reduce((total, detail, index) => {
            const qty = parseFloat(detail.qty_diminta) || 0;
            const harga = parseFloat(detail.harga_satuan) || 0;
            return total + (qty * harga);
        }, 0);
    };

    return (
        <FormTemplate
            title={`Edit Pengadaan ${pengadaan.pengadaan_id}`}
            breadcrumbs={breadcrumbs}
            backUrl="/pengadaan"
            onSubmit={handleSubmit}
            processing={processing}
            processingText="Mengupdate..."
        >
            <Head title={`Edit Pengadaan ${pengadaan.pengadaan_id}`} />

            {/* Basic Information */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <Label htmlFor="status">Status Pengadaan</Label>
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
                <div className="space-y-4">
                    {pengadaan.detail.map((item, index) => (
                        <div key={item.pengadaan_detail_id} className={cn('rounded-lg border bg-gray-50 p-4', colors.border.primary)}>
                            {/* Main grid for item layout */}
                            <div className="grid grid-cols-1 gap-x-4 gap-y-2 md:grid-cols-6">
                                <div className="md:col-span-2">
                                    <Label className="text-sm font-medium text-gray-700">Nama Barang</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">{item.nama_item}</div>
                                </div>

                                {/* Pemasok Dropdown */}
                                <div className="md:col-span-2">
                                    <Label className="text-sm font-medium text-gray-700">Pemasok</Label>
                                    {item.jenis_barang === 'bahan_baku' ? (
                                        <>
                                            {canEditSupplier() ? (
                                                <>
                                                    <Select
                                                        value={data.details[index].pemasok_id || ''}
                                                        onValueChange={(value) => handleDetailChange(index, 'pemasok_id', value)}
                                                    >
                                                        <SelectTrigger
                                                            className={cn('mt-1 bg-white', errors[`details.${index}.pemasok_id`] && 'border-red-500')}
                                                        >
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
                                                    {errors[`details.${index}.pemasok_id`] && (
                                                        <p className="mt-1 text-sm text-red-600">{errors[`details.${index}.pemasok_id`]}</p>
                                                    )}
                                                </>
                                            ) : (
                                                <div
                                                    className={cn(
                                                        'mt-1 rounded border p-2 text-sm',
                                                        data.details[index].pemasok_id ? 'bg-white' : 'bg-gray-100 text-gray-600',
                                                    )}
                                                >
                                                    {data.details[index].pemasok_id
                                                        ? pemasoks.find((p) => p.pemasok_id === data.details[index].pemasok_id)?.nama_pemasok
                                                        : '- (Menunggu alokasi)'}
                                                </div>
                                            )}
                                        </>
                                    ) : (
                                        <div className="mt-1 rounded border bg-gray-200 p-2 text-sm text-gray-600">- (Produk Internal)</div>
                                    )}
                                </div>

                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Jumlah Diminta</Label>
                                    {isPriceEditable ? (
                                        <>
                                            <div className="flex items-center gap-2 mt-1">
                                                <Input
                                                    type="number"
                                                    min="1"
                                                    value={data.details[index].qty_diminta}
                                                    onChange={(e) => handleDetailChange(index, 'qty_diminta', e.target.value)}
                                                    className={cn('w-24', errors[`details.${index}.qty_diminta`] && 'border-red-500')}
                                                    placeholder="Qty"
                                                />
                                                <span className="text-sm text-gray-600">{item.satuan}</span>
                                            </div>
                                            {errors[`details.${index}.qty_diminta`] && (
                                                <p className="mt-1 text-sm text-red-600">{errors[`details.${index}.qty_diminta`]}</p>
                                            )}
                                            <p className="mt-1 text-xs text-gray-500">Rekomendasi sistem: {item.qty_diminta}</p>
                                        </>
                                    ) : (
                                        <div className="mt-1 rounded border bg-white p-2 text-sm">
                                            {data.details[index].qty_diminta} {item.satuan}
                                        </div>
                                    )}
                                </div>

                                <div>
                                    <Label className="text-sm font-medium text-gray-700">Harga Satuan</Label>
                                    {item.jenis_barang === 'bahan_baku' && isPriceEditable ? (
                                        <>
                                            <Input
                                                type="number"
                                                value={data.details[index].harga_satuan}
                                                onChange={(e) => handleDetailChange(index, 'harga_satuan', e.target.value)}
                                                className={cn('mt-1', errors[`details.${index}.harga_satuan`] && 'border-red-500')}
                                                placeholder="Masukkan harga satuan"
                                            />
                                            {errors[`details.${index}.harga_satuan`] && (
                                                <p className="mt-1 text-sm text-red-600">{errors[`details.${index}.harga_satuan`]}</p>
                                            )}
                                        </>
                                    ) : (
                                        <div className="mt-1 rounded border bg-white p-2 text-sm">
                                            Rp {parseFloat(item.harga_satuan).toLocaleString('id-ID')}
                                        </div>
                                    )}
                                </div>
                            </div>
                            {item.catatan && (
                                <div className="mt-4">
                                    <Label className="text-sm font-medium text-gray-700">Catatan</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">{item.catatan}</div>
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
        </FormTemplate>
    );
}
