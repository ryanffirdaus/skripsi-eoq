import { FormField, Select, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { Input } from '@/components/ui/input';
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
    tanggal_pembelian: string;
    display_text: string;
    metode_pembayaran: 'tunai' | 'transfer' | 'termin';
    termin_pembayaran?: string;
    jumlah_dp?: number;
    total_dibayar: number;
    sisa_pembayaran: number;
    is_dp_paid: boolean;
}

interface Props {
    pembelians: Pembelian[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transaksi Pembayaran', href: '/transaksi-pembayaran' },
    { title: 'Tambah Transaksi Pembayaran', href: '#' },
];

export default function Create({ pembelians }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        pembelian_id: '',
        jenis_pembayaran: 'pelunasan' as 'dp' | 'termin' | 'pelunasan',
        tanggal_pembayaran: new Date().toISOString().split('T')[0],
        jumlah_pembayaran: '',
        catatan: '',
        bukti_pembayaran: null as File | null,
    });

    // --- LOGIC ---

    const selectedPembelian = React.useMemo(() => {
        return pembelians.find((p) => p.pembelian_id === data.pembelian_id);
    }, [data.pembelian_id, pembelians]);

    React.useEffect(() => {
        if (selectedPembelian) {
            // Auto-suggest amount based on jenis_pembayaran
            if (data.jenis_pembayaran === 'dp' && selectedPembelian.jumlah_dp) {
                setData('jumlah_pembayaran', selectedPembelian.jumlah_dp.toString());
            } else if (data.jenis_pembayaran === 'pelunasan') {
                setData('jumlah_pembayaran', selectedPembelian.sisa_pembayaran.toString());
            } else {
                setData('jumlah_pembayaran', '');
            }
        } else {
            setData('jumlah_pembayaran', '');
        }
    }, [selectedPembelian, data.jenis_pembayaran, setData]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/transaksi-pembayaran');
    };

    const pembelianOptions = [
        ...pembelians.map((p) => ({
            value: p.pembelian_id,
            label: p.display_text,
        })),
    ];

    // --- RENDER ---

    return (
        <FormTemplate
            title="Tambah Pembayaran"
            breadcrumbs={breadcrumbs}
            backUrl="/transaksi-pembayaran"
            onSubmit={handleSubmit}
            processing={processing}
            processingText="Menyimpan..."
        >
            <Head title="Tambah Pembayaran" />

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div className="md:col-span-1">
                    <FormField id="pembelian_id" label="Pembelian" error={errors.pembelian_id} required>
                        <Select
                            id="pembelian_id"
                            value={data.pembelian_id}
                            onChange={(e) => setData('pembelian_id', e.target.value)}
                            options={pembelianOptions}
                            placeholder="Pilih PO yang akan dibayar"
                            error={errors.pembelian_id}
                        />
                    </FormField>
                </div>

                {selectedPembelian && (
                    <div className="md:col-span-2">
                        <div className="rounded-lg border bg-gray-50 p-4">
                            <h3 className={cn(colors.text.primary, 'mb-3 text-sm font-medium')}>Detail Purchase Order</h3>
                            <div className="grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                                <div>
                                    <span className="text-gray-600">No. PO:</span>
                                    <p className="font-medium">{selectedPembelian.pembelian_id}</p>
                                </div>
                                <div>
                                    <span className="text-gray-600">Pemasok:</span>
                                    <p className="font-medium">{selectedPembelian.pemasok_nama}</p>
                                </div>
                                <div>
                                    <span className="text-gray-600">Tanggal PO:</span>
                                    <p className="font-medium">{selectedPembelian.tanggal_pembelian}</p>
                                </div>
                                <div>
                                    <span className="text-gray-600">Metode:</span>
                                    <p className="font-medium capitalize">{selectedPembelian.metode_pembayaran}</p>
                                </div>
                            </div>
                            <div className="mt-3 grid grid-cols-2 gap-3 border-t pt-3 text-sm md:grid-cols-4">
                                <div>
                                    <span className="text-gray-600">Total Biaya:</span>
                                    <p className="font-semibold text-green-600">{formatCurrency(selectedPembelian.total_biaya)}</p>
                                </div>
                                <div>
                                    <span className="text-gray-600">Total Dibayar:</span>
                                    <p className="font-semibold text-blue-600">{formatCurrency(selectedPembelian.total_dibayar)}</p>
                                </div>
                                <div>
                                    <span className="text-gray-600">Sisa:</span>
                                    <p className="font-semibold text-orange-600">{formatCurrency(selectedPembelian.sisa_pembayaran)}</p>
                                </div>
                                <div>
                                    <span className="text-gray-600">DP:</span>
                                    <p className="font-semibold text-purple-600">
                                        {selectedPembelian.jumlah_dp && selectedPembelian.jumlah_dp > 0
                                            ? formatCurrency(selectedPembelian.jumlah_dp)
                                            : '-'}
                                    </p>
                                </div>
                            </div>
                            {selectedPembelian.metode_pembayaran === 'termin' && !selectedPembelian.is_dp_paid && (
                                <div className="mt-3 rounded-lg bg-yellow-50 p-2 text-xs text-yellow-800">
                                    ⚠️ Peringatan: DP belum dibayar. Pastikan DP dibayar sebelum melakukan pembayaran termin atau pelunasan.
                                </div>
                            )}
                        </div>
                    </div>
                )}

                <div className="md:col-span-1">
                    <FormField id="jenis_pembayaran" label="Jenis Pembayaran" error={errors.jenis_pembayaran} required>
                        <Select
                            id="jenis_pembayaran"
                            value={data.jenis_pembayaran}
                            onChange={(e) => setData('jenis_pembayaran', e.target.value as 'dp' | 'termin' | 'pelunasan')}
                            options={[
                                { value: 'dp', label: 'Down Payment (DP)' },
                                { value: 'termin', label: 'Pembayaran Termin' },
                                { value: 'pelunasan', label: 'Pelunasan' },
                            ]}
                            error={errors.jenis_pembayaran}
                            disabled={!data.pembelian_id}
                        />
                    </FormField>
                    {data.jenis_pembayaran === 'dp' && (
                        <p className="mt-1 text-xs text-blue-600">DP harus sesuai dengan jumlah yang tercatat di PO</p>
                    )}
                    {data.jenis_pembayaran === 'termin' && (
                        <p className="mt-1 text-xs text-purple-600">Pembayaran bertahap sesuai termin yang disepakati</p>
                    )}
                    {data.jenis_pembayaran === 'pelunasan' && <p className="mt-1 text-xs text-green-600">Pembayaran sisa yang belum dibayar</p>}
                </div>

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
                    <FormField id="bukti_pembayaran" label="Bukti Pembayaran" error={errors.bukti_pembayaran} required>
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
                        <p className="mt-1 text-sm text-gray-500">Upload bukti pembayaran (JPG, PNG, PDF, max 2MB) - WAJIB</p>
                    </FormField>
                </div>

                <div className="md:col-span-2">
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
