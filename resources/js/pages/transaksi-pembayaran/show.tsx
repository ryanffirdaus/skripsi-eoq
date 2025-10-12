import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { colors } from '@/lib/colors';
import { formatCurrency, formatDate } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { ArrowLeftIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import { Head, Link } from '@inertiajs/react';

// --- INTERFACES ---
interface PembelianDetail {
    nama_item: string;
    item_type: 'bahan_baku' | 'produk';
    satuan: string;
    qty_dipesan: number;
    harga_satuan: number;
    subtotal: number;
}

interface Transaksi {
    transaksi_pembayaran_id: string;
    pembelian: {
        pembelian_id: string;
        nomor_po: string;
        tanggal_pembelian: string;
        total_biaya: number;
        status: string;
        catatan?: string;
        pemasok: {
            nama_pemasok: string;
            telepon: string;
            email: string;
            alamat: string;
        };
        detail: PembelianDetail[];
    };
    tanggal_pembayaran: string;
    total_pembayaran: number;
    bukti_pembayaran?: string;
    deskripsi?: string;
    created_at: string;
}

interface Props {
    transaksi: Transaksi;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transaksi Pembayaran', href: '/transaksi-pembayaran' },
    { title: 'Detail', href: '#' },
];

export default function Show({ transaksi }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Transaksi ${transaksi.transaksi_pembayaran_id}`} />

            {/* Header */}
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className={cn(colors.text.primary, 'text-2xl font-bold')}>Detail Transaksi Pembayaran</h1>
                    <p className="mt-1 text-sm text-gray-600">
                        ID: <span className="font-medium">{transaksi.transaksi_pembayaran_id}</span>
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <Link href="/transaksi-pembayaran">
                        <Button variant="outline" size="sm">
                            <ArrowLeftIcon className="mr-2 h-4 w-4" />
                            Kembali
                        </Button>
                    </Link>
                    <Link href={`/transaksi-pembayaran/${transaksi.transaksi_pembayaran_id}/edit`}>
                        <Button size="sm">Edit</Button>
                    </Link>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Informasi Pembayaran */}
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Informasi Pembayaran</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm text-gray-600">Tanggal Pembayaran</p>
                                <p className="font-medium">{formatDate(transaksi.tanggal_pembayaran)}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Total Pembayaran</p>
                                <p className="text-lg font-bold text-green-600">{formatCurrency(transaksi.total_pembayaran)}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Dicatat Pada</p>
                                <p className="font-medium">{transaksi.created_at}</p>
                            </div>
                        </div>

                        {transaksi.deskripsi && (
                            <div>
                                <p className="text-sm text-gray-600">Deskripsi / Catatan</p>
                                <p className="mt-1 rounded-md bg-gray-50 p-3 text-sm">{transaksi.deskripsi}</p>
                            </div>
                        )}

                        {transaksi.bukti_pembayaran && (
                            <div>
                                <p className="mb-2 text-sm text-gray-600">Bukti Pembayaran</p>
                                <a
                                    href={transaksi.bukti_pembayaran}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-2 rounded-md border bg-white px-4 py-2 text-sm font-medium hover:bg-gray-50"
                                >
                                    <DocumentTextIcon className="h-5 w-5" />
                                    Lihat Bukti Pembayaran
                                </a>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Informasi Pemasok */}
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Pemasok</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div>
                            <p className="text-sm text-gray-600">Nama Pemasok</p>
                            <p className="font-medium">{transaksi.pembelian.pemasok.nama_pemasok}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600">Telepon</p>
                            <p className="font-medium">{transaksi.pembelian.pemasok.telepon}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600">Email</p>
                            <p className="font-medium">{transaksi.pembelian.pemasok.email}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600">Alamat</p>
                            <p className="text-sm">{transaksi.pembelian.pemasok.alamat}</p>
                        </div>
                    </CardContent>
                </Card>

                {/* Informasi Purchase Order */}
                <Card className="lg:col-span-3">
                    <CardHeader>
                        <CardTitle>Informasi Purchase Order</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div>
                                <p className="text-sm text-gray-600">No. PO</p>
                                <p className="font-medium">{transaksi.pembelian.nomor_po}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Tanggal PO</p>
                                <p className="font-medium">{formatDate(transaksi.pembelian.tanggal_pembelian)}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Total PO</p>
                                <p className="font-medium">{formatCurrency(transaksi.pembelian.total_biaya)}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Status PO</p>
                                <Badge variant="default">{transaksi.pembelian.status}</Badge>
                            </div>
                        </div>

                        {transaksi.pembelian.catatan && (
                            <div>
                                <p className="text-sm text-gray-600">Catatan PO</p>
                                <p className="mt-1 rounded-md bg-gray-50 p-3 text-sm">{transaksi.pembelian.catatan}</p>
                            </div>
                        )}

                        {/* Detail Item PO */}
                        <div>
                            <p className="mb-3 text-sm font-medium text-gray-900">Detail Item Purchase Order</p>
                            <div className="overflow-hidden rounded-lg border">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Item</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase">Tipe</th>
                                            <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Qty</th>
                                            <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Harga Satuan</th>
                                            <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {transaksi.pembelian.detail.map((item, index) => (
                                            <tr key={index}>
                                                <td className="px-4 py-3 text-sm">{item.nama_item}</td>
                                                <td className="px-4 py-3 text-sm">
                                                    <Badge variant="outline">{item.item_type === 'bahan_baku' ? 'Bahan Baku' : 'Produk'}</Badge>
                                                </td>
                                                <td className="px-4 py-3 text-right text-sm">
                                                    {item.qty_dipesan} {item.satuan}
                                                </td>
                                                <td className="px-4 py-3 text-right text-sm">{formatCurrency(item.harga_satuan)}</td>
                                                <td className="px-4 py-3 text-right text-sm font-medium">{formatCurrency(item.subtotal)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-gray-50">
                                        <tr>
                                            <td colSpan={4} className="px-4 py-3 text-right text-sm font-medium">
                                                Total PO:
                                            </td>
                                            <td className="px-4 py-3 text-right text-lg font-bold">
                                                {formatCurrency(transaksi.pembelian.total_biaya)}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
