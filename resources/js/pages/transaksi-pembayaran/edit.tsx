import { FormField, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { colors } from '@/lib/colors';
import { formatCurrency } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { InformationCircleIcon } from '@heroicons/react/24/outline';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

// --- INTERFACES ---
interface Pembelian {
    pembelian_id: string;
    nomor_po: string;
    pemasok_nama: string;
    total_biaya: number;
    display_text: string;
}

interface Transaksi {
    transaksi_pembayaran_id: string;
    pembelian_id: string;
    nomor_po: string;
    pemasok_nama: string;
    jenis_pembayaran: string;
    tanggal_pembayaran: string;
    jumlah_pembayaran: number;
    metode_pembayaran: string;
    bukti_pembayaran?: string;
    deskripsi?: string;
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
    const { data, setData, put, processing, errors } = useForm({
        jenis_pembayaran: transaksi.jenis_pembayaran,
        tanggal_pembayaran: transaksi.tanggal_pembayaran,
        jumlah_pembayaran: transaksi.jumlah_pembayaran.toString(),
        metode_pembayaran: transaksi.metode_pembayaran,
        bukti_pembayaran: null as File | null,
        deskripsi: transaksi.deskripsi || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/transaksi-pembayaran/${transaksi.transaksi_pembayaran_id}`);
    };

    return (
        <FormTemplate
            title="Edit Transaksi Pembayaran"
            breadcrumbs={breadcrumbs}
            backUrl="/transaksi-pembayaran"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Simpan Perubahan"
            processingText="Menyimpan..."
        >
            <Head title={`Edit Transaksi ${transaksi.transaksi_pembayaran_id}`} />

            <div className="mb-6 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <InformationCircleIcon className="h-5 w-5 flex-shrink-0 text-blue-400" />
                <div>
                    <p className="text-sm text-blue-700">
                        <strong>Informasi:</strong> Anda dapat mengubah tanggal, total pembayaran, atau mengganti bukti pembayaran. Purchase Order
                        tidak dapat diubah.
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                {/* Read-only PO Info */}
                <div className="md:col-span-2">
                    <div className="rounded-lg border bg-gray-50 p-4">
                        <h3 className={cn(colors.text.primary, 'mb-2 text-sm font-medium')}>Purchase Order</h3>
                        <div className="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span className="text-gray-600">No. PO:</span>
                                <span className="ml-2 font-medium">{transaksi.nomor_po}</span>
                            </div>
                            <div>
                                <span className="text-gray-600">Pemasok:</span>
                                <span className="ml-2 font-medium">{transaksi.pemasok_nama}</span>
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

                <FormField id="metode_pembayaran" label="Metode Pembayaran" error={errors.metode_pembayaran} required>
                    <Select value={data.metode_pembayaran} onValueChange={(value) => setData('metode_pembayaran', value)}>
                        <SelectTrigger className={cn(errors.metode_pembayaran && 'border-red-500')}>
                            <SelectValue placeholder="Pilih Metode Pembayaran" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="tunai">Tunai</SelectItem>
                            <SelectItem value="transfer">Transfer</SelectItem>
                        </SelectContent>
                    </Select>
                </FormField>

                <div className="md:col-span-2">
                    <FormField id="bukti_pembayaran" label="Bukti Pembayaran" error={errors.bukti_pembayaran}>
                        {transaksi.bukti_pembayaran && !data.bukti_pembayaran && (
                            <div className="mb-2 flex items-center gap-2 rounded-md bg-gray-100 p-2 text-sm">
                                <span className="text-gray-600">Bukti saat ini:</span>
                                <a
                                    href={transaksi.bukti_pembayaran}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="font-medium text-blue-600 hover:underline"
                                >
                                    Lihat bukti
                                </a>
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

                <div className="md:col-span-2">
                    <FormField id="deskripsi" label="Deskripsi / Catatan" error={errors.deskripsi}>
                        <TextArea
                            id="deskripsi"
                            value={data.deskripsi}
                            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('deskripsi', e.target.value)}
                            rows={3}
                            placeholder="Catatan tambahan untuk pembayaran ini..."
                        />
                    </FormField>
                </div>
            </div>
        </FormTemplate>
    );
}
