import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { ExclamationCircleIcon } from '@heroicons/react/24/outline';
import { Head, Link, useForm } from '@inertiajs/react';

interface User {
    user_id: string;
    nama_lengkap: string;
}

interface PengadaanDetail {
    pengadaan_detail_id: string;
    nama_item: string;
    qty_diminta: number;
    qty_disetujui: number;
    satuan: string;
}

interface Props {
    pengadaanDetails: PengadaanDetail[];
    workers: User[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Penugasan Produksi',
        href: '/penugasan-produksi',
    },
    {
        title: 'Create',
        href: '/penugasan-produksi/create',
    },
];

export default function Create({ pengadaanDetails, workers }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        pengadaan_detail_id: '',
        user_id: '',
        jumlah_produksi: '',
        deadline: new Date().toISOString().split('T')[0],
        catatan: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/penugasan-produksi');
    };

    // Get max quantity for selected pengadaan detail
    const selectedDetail = pengadaanDetails.find((p) => p.pengadaan_detail_id === data.pengadaan_detail_id);
    const maxQty = selectedDetail?.qty_disetujui ?? selectedDetail?.qty_diminta ?? 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buat Penugasan Produksi" />

            <div className={colors.card.base}>
                <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h1 className="text-xl font-semibold text-gray-900 dark:text-white">Buat Penugasan Produksi</h1>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6 p-6">
                    {/* Info Box */}
                    {pengadaanDetails.length === 0 && (
                        <Alert>
                            <ExclamationCircleIcon className="h-4 w-4" />
                            <AlertDescription>
                                Tidak ada pengadaan detail yang tersedia. Pastikan ada pengadaan yang sudah disetujui keuangan.
                            </AlertDescription>
                        </Alert>
                    )}

                    {workers.length === 0 && (
                        <Alert>
                            <ExclamationCircleIcon className="h-4 w-4" />
                            <AlertDescription>Tidak ada worker (Staf RnD) yang tersedia.</AlertDescription>
                        </Alert>
                    )}

                    {/* Pengadaan Detail Selection */}
                    <div>
                        <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                            Item Produksi <span className="text-red-500">*</span>
                        </label>
                        <select
                            value={data.pengadaan_detail_id}
                            onChange={(e) => setData('pengadaan_detail_id', e.target.value)}
                            className={cn(colors.input.base, errors.pengadaan_detail_id && colors.input.error)}
                            required
                            disabled={pengadaanDetails.length === 0}
                        >
                            <option value="">Pilih Item Produksi</option>
                            {pengadaanDetails.map((detail) => (
                                <option key={detail.pengadaan_detail_id} value={detail.pengadaan_detail_id}>
                                    {detail.nama_item} (Minta: {detail.qty_diminta}, Disetujui: {detail.qty_disetujui} {detail.satuan})
                                </option>
                            ))}
                        </select>
                        {errors.pengadaan_detail_id && <p className={colors.text.error}>{errors.pengadaan_detail_id}</p>}
                    </div>

                    {/* Worker Selection */}
                    <div>
                        <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                            Pilih Worker (Staf RnD) <span className="text-red-500">*</span>
                        </label>
                        <select
                            value={data.user_id}
                            onChange={(e) => setData('user_id', e.target.value)}
                            className={cn(colors.input.base, errors.user_id && colors.input.error)}
                            required
                            disabled={workers.length === 0}
                        >
                            <option value="">Pilih Worker</option>
                            {workers.map((worker) => (
                                <option key={worker.user_id} value={worker.user_id}>
                                    {worker.nama_lengkap}
                                </option>
                            ))}
                        </select>
                        {errors.user_id && <p className={colors.text.error}>{errors.user_id}</p>}
                    </div>

                    {/* Jumlah Produksi */}
                    <div>
                        <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                            Jumlah Produksi
                            {maxQty > 0 && <span className="text-gray-500"> (Max: {maxQty})</span>}
                            <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            min="1"
                            max={maxQty || undefined}
                            value={data.jumlah_produksi}
                            onChange={(e) => setData('jumlah_produksi', e.target.value)}
                            className={cn(colors.input.base, errors.jumlah_produksi && colors.input.error)}
                            placeholder="Enter jumlah produksi"
                            required
                        />
                        {errors.jumlah_produksi && <p className={colors.text.error}>{errors.jumlah_produksi}</p>}
                        {maxQty > 0 && parseInt(data.jumlah_produksi) > maxQty && (
                            <p className={colors.text.error}>Jumlah tidak boleh melebihi {maxQty}</p>
                        )}
                    </div>

                    {/* Deadline */}
                    <div>
                        <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                            Deadline <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            value={data.deadline}
                            onChange={(e) => setData('deadline', e.target.value)}
                            className={cn(colors.input.base, errors.deadline && colors.input.error)}
                            required
                        />
                        {errors.deadline && <p className={colors.text.error}>{errors.deadline}</p>}
                    </div>

                    {/* Catatan */}
                    <div>
                        <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>Catatan</label>
                        <textarea
                            value={data.catatan}
                            onChange={(e) => setData('catatan', e.target.value)}
                            className={cn(colors.input.base, errors.catatan && colors.input.error)}
                            placeholder="Masukkan catatan (opsional)"
                            rows={4}
                        />
                        {errors.catatan && <p className={colors.text.error}>{errors.catatan}</p>}
                    </div>

                    {/* Actions */}
                    <div className="flex gap-3 pt-4">
                        <Button type="submit" disabled={processing || pengadaanDetails.length === 0 || workers.length === 0}>
                            {processing ? 'Creating...' : 'Buat Penugasan'}
                        </Button>
                        <Link href="/penugasan-produksi">
                            <Button type="button" variant="outline">
                                Batal
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
