import { InfoSection } from '@/components/info-section';
import ShowPageTemplate from '@/components/templates/show-page-template';
import TimestampSection from '@/components/timestamp-section';
import { formatCurrency, formatDate } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';
import { AlertTriangle } from 'lucide-react';

interface Pemasok {
    pemasok_id: string;
    nama_pemasok: string;
    alamat?: string;
    telepon?: string;
}

interface BahanBaku {
    bahan_baku_id: string;
    nama: string;
    satuan: string;
}

interface Pembelian {
    pembelian_id: string;
    nomor_po: string;
    pemasok: Pemasok;
}

interface User {
    user_id: string;
    nama_lengkap: string;
}

interface ReturItem {
    retur_id: string;
    nomor_retur: string;
    qty_retur: number;
    status: string;
}

interface PenerimaanDetail {
    penerimaan_detail_id: string;
    bahan_baku_id: string;
    bahan_baku: BahanBaku;
    qty_dipesan: number;
    qty_received: number;
    qty_rejected: number;
    harga_satuan: number;
    subtotal: number;
    catatan_qc?: string;
    retur_items: ReturItem[];
}

interface PenerimaanBahanBaku {
    penerimaan_id: string;
    nomor_dokumen: string;
    pembelian_id?: string;
    pembelian?: Pembelian;
    pemasok_id: string;
    pemasok?: Pemasok;
    tanggal_penerimaan: string;
    status: 'pending' | 'partial' | 'complete' | 'returned';
    total_item: number;
    total_qty_diterima: number;
    total_qty_rejected: number;
    catatan?: string;
    received_by: string;
    receivedBy?: User;
    checked_by?: string;
    checkedBy?: User;
    details?: PenerimaanDetail[];
    created_at: string;
    updated_at: string;
}

interface ShowProps {
    penerimaan: PenerimaanBahanBaku;
    permissions?: {
        canEdit?: boolean;
        canDelete?: boolean;
    };
}

const getStatusBadge = (status: string) => {
    const badges = {
        pending: { label: 'Pending', color: 'bg-yellow-100 text-yellow-800 border-yellow-200' },
        partial: { label: 'Sebagian', color: 'bg-blue-100 text-blue-800 border-blue-200' },
        complete: { label: 'Selesai', color: 'bg-green-100 text-green-800 border-green-200' },
        returned: { label: 'Diretur', color: 'bg-red-100 text-red-800 border-red-200' },
    };
    return badges[status as keyof typeof badges] || badges.pending;
};

export default function Show({ penerimaan, permissions = {} }: ShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Penerimaan Bahan Baku',
            href: '/penerimaan-bahan-baku',
        },
        {
            title: `Detail ${penerimaan.nomor_dokumen}`,
            href: `/penerimaan-bahan-baku/${penerimaan.penerimaan_id}`,
        },
    ];

    // Actions - hanya tampil jika ada permission
    const actions = [
        ...(permissions.canEdit
            ? [
                  {
                      label: 'Edit Penerimaan',
                      href: `/penerimaan-bahan-baku/${penerimaan.penerimaan_id}/edit`,
                      variant: 'outline' as const,
                  },
              ]
            : []),
        {
            label: 'Kembali',
            href: '/penerimaan-bahan-baku',
            variant: 'outline' as const,
        },
    ];

    const statusBadge = getStatusBadge(penerimaan.status);
    const totalNilaiPenerimaan = penerimaan.details?.reduce((sum, detail) => sum + (detail.subtotal || 0), 0) || 0;

    const informasiUmum = [
        {
            label: 'No. Dokumen',
            value: penerimaan.nomor_dokumen,
        },
        ...(penerimaan.pembelian
            ? [
                  {
                      label: 'No. Pembelian',
                      value: penerimaan.pembelian.nomor_po,
                  },
              ]
            : []),
        {
            label: 'Tanggal Penerimaan',
            value: formatDate(penerimaan.tanggal_penerimaan, {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }),
        },
        {
            label: 'Diterima Oleh',
            value: penerimaan.receivedBy?.nama_lengkap || '-',
        },
    ];

    const informasiPemasok = [
        {
            label: 'Nama Pemasok',
            value: penerimaan.pemasok?.nama_pemasok || '-',
        },
        ...(penerimaan.pemasok?.alamat
            ? [
                  {
                      label: 'Alamat',
                      value: penerimaan.pemasok.alamat,
                  },
              ]
            : []),
        ...(penerimaan.pemasok?.telepon
            ? [
                  {
                      label: 'Telepon',
                      value: penerimaan.pemasok.telepon,
                  },
              ]
            : []),
    ];

    const ringkasanItems = [
        {
            label: 'Total Item',
            value: <span className="text-2xl font-bold text-blue-600">{penerimaan.total_item}</span>,
        },
        {
            label: 'Qty Diterima',
            value: <span className="text-2xl font-bold text-green-600">{penerimaan.total_qty_diterima}</span>,
        },
        {
            label: 'Qty Ditolak',
            value: <span className="text-2xl font-bold text-red-600">{penerimaan.total_qty_rejected}</span>,
        },
        {
            label: 'Total Nilai',
            value: <span className="text-2xl font-bold text-gray-900">{formatCurrency(totalNilaiPenerimaan)}</span>,
        },
    ];

    return (
        <ShowPageTemplate
            title={penerimaan.nomor_dokumen}
            pageTitle={`Detail Penerimaan Bahan Baku ${penerimaan.nomor_dokumen}`}
            breadcrumbs={breadcrumbs}
            subtitle={`Pemasok: ${penerimaan.pemasok?.nama_pemasok || '-'}`}
            badge={statusBadge}
            actions={actions}
        >
            <div className="space-y-8">
                <InfoSection title="Informasi Umum" items={informasiUmum} />

                <InfoSection title="Informasi Pemasok" items={informasiPemasok} />

                {/* Ringkasan Penerimaan */}
                <div className="space-y-4">
                    <h2 className="text-xl font-semibold">Ringkasan Penerimaan</h2>
                    <div className="grid grid-cols-2 gap-6 rounded-lg border bg-white p-6 md:grid-cols-4 dark:bg-gray-950">
                        {ringkasanItems.map((item) => (
                            <div key={item.label} className="text-center">
                                <div>{item.value}</div>
                                <p className="mt-2 text-sm text-gray-500">{item.label}</p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Detail Items Table */}
                {penerimaan.details && penerimaan.details.length > 0 && (
                    <div className="space-y-4">
                        <h2 className="text-xl font-semibold">Detail Item Diterima</h2>
                        <div className="overflow-x-auto rounded-lg border bg-white dark:bg-gray-950">
                            <table className="w-full border-collapse">
                                <thead>
                                    <tr className="border-b bg-gray-50 dark:bg-gray-900">
                                        <th className="p-3 text-left font-medium">Bahan Baku</th>
                                        <th className="p-3 text-left font-medium">Satuan</th>
                                        <th className="p-3 text-right font-medium">Qty Dipesan</th>
                                        <th className="p-3 text-right font-medium">Qty Diterima</th>
                                        <th className="p-3 text-right font-medium">Qty Ditolak</th>
                                        <th className="p-3 text-right font-medium">Harga Satuan</th>
                                        <th className="p-3 text-right font-medium">Subtotal</th>
                                        <th className="p-3 text-left font-medium">Catatan QC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {penerimaan.details.map((detail) => (
                                        <tr key={detail.penerimaan_detail_id} className="border-b hover:bg-gray-50 dark:hover:bg-gray-900">
                                            <td className="p-3 font-medium">{detail.bahan_baku.nama}</td>
                                            <td className="p-3">{detail.bahan_baku.satuan}</td>
                                            <td className="p-3 text-right">{detail.qty_dipesan.toLocaleString()}</td>
                                            <td className="p-3 text-right font-medium text-green-600">{detail.qty_received.toLocaleString()}</td>
                                            <td className="p-3 text-right font-medium text-red-600">{detail.qty_rejected.toLocaleString()}</td>
                                            <td className="p-3 text-right">{formatCurrency(detail.harga_satuan)}</td>
                                            <td className="p-3 text-right font-medium">{formatCurrency(detail.subtotal)}</td>
                                            <td className="p-3">
                                                {detail.catatan_qc && (
                                                    <div className="flex items-center gap-1">
                                                        <AlertTriangle className="h-4 w-4 text-yellow-500" />
                                                        <span className="text-sm">{detail.catatan_qc}</span>
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Catatan */}
                {penerimaan.catatan && (
                    <div className="space-y-4">
                        <h2 className="text-xl font-semibold">Catatan</h2>
                        <div className="rounded-lg border bg-white p-6 dark:bg-gray-950">
                            <p className="text-gray-700 dark:text-gray-300">{penerimaan.catatan}</p>
                        </div>
                    </div>
                )}

                <TimestampSection
                    createdAt={penerimaan.created_at}
                    updatedAt={penerimaan.updated_at}
                    createdBy={penerimaan.receivedBy?.nama_lengkap}
                    updatedBy={penerimaan.checkedBy?.nama_lengkap}
                />
            </div>
        </ShowPageTemplate>
    );
}
