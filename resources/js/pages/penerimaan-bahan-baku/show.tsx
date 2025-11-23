import ShowPageTemplate from '@/components/templates/show-page-template';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { Package, Truck } from 'lucide-react';

interface Item {
    nama_item: string;
    satuan: string;
    qty_dipesan: number;
    harga_satuan: number;
    total_harga: number;
}

interface Pembelian {
    pembelian_id: string;
    tanggal_pembelian: string;
    pemasok_nama: string;
}

interface Penerimaan {
    penerimaan_id: string;
    pembelian_detail_id: string;
    qty_diterima: number;
    pembelian: Pembelian;
    item: Item;
    penerima: {
        nama_penerima: string;
        email_penerima: string;
    };
    created_at: string;
    updated_at: string;
}

interface Props {
    penerimaan: Penerimaan;
}

export default function Show({ penerimaan }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Penerimaan Bahan Baku', href: '/penerimaan-bahan-baku' },
        { title: `Detail ${penerimaan.penerimaan_id}`, href: '#' },
    ];

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const actions = [
        {
            label: 'Kembali',
            href: '/penerimaan-bahan-baku',
            variant: 'outline' as const,
        },
    ];

    return (
        <ShowPageTemplate
            title={penerimaan.penerimaan_id}
            pageTitle={`Detail Penerimaan Bahan Baku`}
            breadcrumbs={breadcrumbs}
            subtitle={`Dari: ${penerimaan.pembelian.pemasok_nama}`}
            actions={actions}
        >
            <div className="space-y-6">
                {/* Informasi Penerimaan */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Truck className="h-5 w-5" />
                            Informasi Penerimaan
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <p className="text-sm font-medium text-gray-500">No. Penerimaan</p>
                                <p className="mt-1 text-lg font-semibold">{penerimaan.penerimaan_id}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-500">Tanggal Penerimaan</p>
                                <p className="mt-1 text-lg font-semibold">{formatDate(penerimaan.created_at)}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-500">No. Pembelian</p>
                                <p className="mt-1 text-lg font-semibold">{penerimaan.pembelian.pembelian_id}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-500">Tanggal Pembelian</p>
                                <p className="mt-1 text-lg font-semibold">{formatDate(penerimaan.pembelian.tanggal_pembelian)}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-500">Nama Pemasok</p>
                                <p className="mt-1 text-lg font-semibold">{penerimaan.pembelian.pemasok_nama}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-500">Nama Penerima</p>
                                <p className="mt-1 text-lg font-semibold">{penerimaan.penerima.nama_penerima}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Detail Item */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Package className="h-5 w-5" />
                            Detail Barang
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Nama Barang</p>
                                    <p className="mt-1 text-lg font-semibold">{penerimaan.item.nama_item}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Satuan</p>
                                    <p className="mt-1 text-lg font-semibold">{penerimaan.item.satuan}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Qty Dipesan</p>
                                    <p className="mt-1 text-lg font-semibold">{penerimaan.item.qty_dipesan}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Qty Diterima</p>
                                    <p className="mt-1 text-lg font-semibold text-green-600">{penerimaan.qty_diterima}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Harga Satuan</p>
                                    <p className="mt-1 text-lg font-semibold">{formatCurrency(penerimaan.item.harga_satuan)}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Total Harga</p>
                                    <p className="mt-1 text-lg font-semibold">{formatCurrency(penerimaan.item.total_harga)}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </ShowPageTemplate>
    );
}
