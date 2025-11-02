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
    { title: 'Ubah Pengadaan', href: '#' },
];

export default function Edit({ pengadaan, pemasoks, statusOptions, auth }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        status: pengadaan.status,
        catatan: pengadaan.catatan || '',
        details: pengadaan.detail.map((item) => ({
            pengadaan_detail_id: item.pengadaan_detail_id,
            pemasok_id: item.pemasok_id || '',
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
        // R04 (Staf Pengadaan) and R09 (Manajer Pengadaan) hanya saat disetujui_gudang
        if (userRole === 'R01') {
            return true; // Admin bisa edit di status apapun
        }
        const isPengadaanStaff = userRole === 'R04' || userRole === 'R09';
        const isCorrectStatus = pengadaan.status === 'disetujui_gudang';
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
        // Only when status is pending or disetujui_gudang
        const isEditableStatus = pengadaan.status === 'pending' || pengadaan.status === 'disetujui_gudang';
        return isAuthorizedRole && isEditableStatus;
    };

    // Check apakah harga masih bisa diedit (hanya untuk status pending, ditolak_procurement, ditolak_finance)
    const isPriceEditable = canEditPrice();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/pengadaan/${pengadaan.pengadaan_id}`);
    };

    const calculateTotal = () => {
        return pengadaan.detail.reduce((total, item) => {
            const hargaAsNumber = parseFloat(item.harga_satuan) * item.qty_diminta;

            return total + (isNaN(hargaAsNumber) ? 0 : hargaAsNumber);
        }, 0);
    };

    return (
        <FormTemplate
            title={`Ubah Pengadaan ${pengadaan.pengadaan_id}`}
            breadcrumbs={breadcrumbs}
            backUrl="/pengadaan"
            onSubmit={handleSubmit}
            processing={processing}
            processingText="Mengupdate..."
        >
            <Head title={`Ubah Pengadaan ${pengadaan.pengadaan_id}`} />

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
                            <p>• Anda dapat mengubah status, catatan, dan pemasok untuk setiap item bahan baku.</p>
                            <p>• Harga satuan hanya dapat diubah saat status: Pending, Ditolak Procurement, atau Ditolak Finance.</p>
                            <p>• Kuantitas dan jenis item tidak dapat diubah setelah pengadaan dibuat.</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Basic Information */}
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <Label htmlFor="status">Status Pengadaan *</Label>
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
                                    <Label className="text-sm font-medium text-gray-700">Kuantitas</Label>
                                    <div className="mt-1 rounded border bg-white p-2 text-sm">
                                        {item.qty_diminta} {item.satuan}
                                    </div>
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
                                    <Label className="text-sm font-medium text-gray-700">Catatan Item</Label>
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
