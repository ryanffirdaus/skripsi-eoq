import { FormField, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { colors } from '@/lib/colors';
import { formatCurrency } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

// --- INTERFACES ---
interface Pembelian {
    pembelian_id: string;
    pemasok_nama: string;
    total_biaya: number;
    display_text: string;
}

interface Transaksi {
    transaksi_pembayaran_id: string;
    pembelian_id: string;
    pemasok_nama: string;
    jenis_pembayaran: string;
    tanggal_pembayaran: string;
    total_pembayaran: number;
    bukti_pembayaran?: string;
    catatan?: string;
}

interface Props {
    transaksi: Transaksi;
    pembelians: Pembelian[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transaksi Pembayaran', href: '/transaksi-pembayaran' },
    { title: 'Edit', href: '#' },
];

export default function Edit({ transaksi }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        jenis_pembayaran: transaksi.jenis_pembayaran,
        tanggal_pembayaran: transaksi.tanggal_pembayaran,
        jumlah_pembayaran: (transaksi.total_pembayaran ?? 0).toString(),
        bukti_pembayaran: null as File | null,
        catatan: transaksi.catatan || '',
        _method: 'PUT',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/transaksi-pembayaran/${transaksi.transaksi_pembayaran_id}`);
    };

    return (
        <FormTemplate
            title="Edit Pembayaran"
            breadcrumbs={breadcrumbs}
            backUrl="/transaksi-pembayaran"
            onSubmit={handleSubmit}
            processing={processing}
            processingText="Menyimpan..."
        >
            <Head title={`Edit Pembayaran ${transaksi.transaksi_pembayaran_id}`} />

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                {/* Read-only PO Info */}
                <div className="md:col-span-1">
                    <div className="rounded-lg border bg-gray-50 p-4">
                        <h3 className={cn(colors.text.primary, 'mb-2 text-sm font-medium')}>Pembelian</h3>
                        <div className="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span className="text-gray-600">No. PO:</span>
                                <span className="ml-2 font-medium">{transaksi.pembelian_id}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <FormField id="jenis_pembayaran" label="Jenis Pembayaran" error={errors.jenis_pembayaran} required>
                    <Select value={data.jenis_pembayaran} onValueChange={(value) => setData('jenis_pembayaran', value)}>
                        <SelectTrigger className={cn(errors.jenis_pembayaran && 'border-red-500')}>
                            <SelectValue placeholder="Pilih Jenis Pembayaran" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="dp">Down Payment (DP)</SelectItem>
                            <SelectItem value="termin">Termin</SelectItem>
                            <SelectItem value="pelunasan">Pelunasan</SelectItem>
                        </SelectContent>
                    </Select>
                </FormField>

                <FormField id="tanggal_pembayaran" label="Tanggal Pembayaran" error={errors.tanggal_pembayaran} required>
                    <TextInput
                        id="tanggal_pembayaran"
                        type="date"
                        value={data.tanggal_pembayaran}
                        onChange={(e) => setData('tanggal_pembayaran', e.target.value)}
                        error={errors.tanggal_pembayaran}
                    />
                </FormField>

                <FormField id="jumlah_pembayaran" label="Jumlah Pembayaran" error={errors.jumlah_pembayaran} required>
                    <TextInput
                        id="jumlah_pembayaran"
                        type="number"
                        value={data.jumlah_pembayaran}
                        onChange={(e) => setData('jumlah_pembayaran', e.target.value)}
                        placeholder="0"
                        step="0.01"
                        min="0"
                        error={errors.jumlah_pembayaran}
                    />
                    {data.jumlah_pembayaran && parseFloat(data.jumlah_pembayaran) > 0 && (
                        <p className="mt-1 text-sm text-gray-600">= {formatCurrency(parseFloat(data.jumlah_pembayaran))}</p>
                    )}
                </FormField>

                <div className="md:col-span-1">
                    <FormField id="bukti_pembayaran" label="Bukti Pembayaran" error={errors.bukti_pembayaran}>
                        {transaksi.bukti_pembayaran && !data.bukti_pembayaran && (
                            <div className="mb-2 rounded-md bg-blue-50 p-3 text-sm">
                                <div className="mb-2 flex items-center justify-between">
                                    <span className="font-medium text-blue-900">Bukti Pembayaran Saat Ini:</span>
                                    <a
                                        href={transaksi.bukti_pembayaran}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="rounded bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700"
                                    >
                                        Lihat Bukti
                                    </a>
                                </div>
                                <div className="text-xs text-blue-700">{transaksi.bukti_pembayaran.split('/').pop()}</div>
                            </div>
                        )}
                        {data.bukti_pembayaran && (
                            <div className="mb-2 rounded-md bg-green-50 p-2 text-sm">
                                <span className="text-green-700">File baru dipilih: </span>
                                <span className="font-medium text-green-900">{data.bukti_pembayaran.name}</span>
                            </div>
                        )}
                        <Input
                            id="bukti_pembayaran"
                            type="file"
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                                if (e.target.files && e.target.files[0]) {
                                    setData('bukti_pembayaran', e.target.files[0]);
                                }
                            }}
                            accept="image/*,.pdf"
                            className={cn(errors.bukti_pembayaran && 'border-red-500')}
                        />
                        <p className="mt-1 text-sm text-gray-500">Upload bukti pembayaran baru (JPG, PNG, PDF, max 2MB) - opsional</p>
                    </FormField>
                </div>
                <div className="md:col-span-1"></div>

                <div className="md:col-span-1">
                    <FormField id="catatan" label="Catatan" error={errors.catatan}>
                        <TextArea
                            id="catatan"
                            value={data.catatan}
                            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('catatan', e.target.value)}
                            rows={3}
                            placeholder="Catatan tambahan untuk pembayaran ini..."
                        />
                    </FormField>
                </div>
            </div>
        </FormTemplate>
    );
}
