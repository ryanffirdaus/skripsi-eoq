import { InfoSection } from '@/components/info-section';
import ShowPageTemplate from '@/components/templates/show-page-template';
import TimestampSection from '@/components/timestamp-section';
import { formatCurrency, formatNumber, safeMultiply } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';

interface UserRef {
    user_id: string;
    nama_lengkap: string;
}

interface BahanBaku {
    bahan_baku_id: string;
    nama_bahan: string;
    deskripsi?: string;
    satuan: string;
    stok_saat_ini: number;
    stok_minimum: number;
    safety_stock: number;
    reorder_point: number;
    lead_time: number;
    demand_tahunan: number;
    biaya_pemesanan: number;
    biaya_penyimpanan: number;
    eoq: number;
    harga_per_unit: number;
    created_by_id?: string;
    updated_by_id?: string;
    created_by?: UserRef | null;
    updated_by?: UserRef | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    bahanBaku: BahanBaku;
}

export default function Show({ bahanBaku }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Bahan Baku',
            href: '/bahan-baku',
        },
        {
            title: `Detail ${bahanBaku.nama_bahan}`,
            href: `/bahan-baku/${bahanBaku.bahan_baku_id}`,
        },
    ];

    const actions = [
        {
            label: 'Edit Bahan Baku',
            href: `/bahan-baku/${bahanBaku.bahan_baku_id}/edit`,
            variant: 'outline' as const,
        },
        {
            label: 'Kembali',
            href: '/bahan-baku',
            variant: 'outline' as const,
        },
    ];

    const getStockStatus = () => {
        const stok = bahanBaku.stok_saat_ini || 0;
        const reorderPoint = bahanBaku.reorder_point || 0;
        const stokMin = bahanBaku.stok_minimum || 0;

        if (stok <= reorderPoint) {
            return { label: 'Perlu Reorder', color: 'bg-red-100 text-red-800 border-red-200' };
        } else if (stok <= stokMin) {
            return { label: 'Stok Minimum', color: 'bg-yellow-100 text-yellow-800 border-yellow-200' };
        } else {
            return { label: 'Stok Aman', color: 'bg-green-100 text-green-800 border-green-200' };
        }
    };

    const stockStatus = getStockStatus();
    const totalNilaiStok = safeMultiply(bahanBaku.stok_saat_ini, bahanBaku.harga_per_unit);

    const basicInfo = [
        {
            label: 'Nama Bahan',
            value: <span className="text-lg font-medium">{bahanBaku.nama_bahan}</span>,
        },
        {
            label: 'Deskripsi',
            value: bahanBaku.deskripsi || 'Tidak ada deskripsi',
        },
        {
            label: 'Satuan',
            value: bahanBaku.satuan,
        },
        {
            label: 'Harga per Unit',
            value: <span className="text-lg font-semibold">{formatCurrency(bahanBaku.harga_per_unit)}</span>,
        },
    ];

    const stockInfo = [
        {
            label: 'Stok Saat Ini',
            value: (
                <span className="text-lg font-semibold">
                    {formatNumber(bahanBaku.stok_saat_ini)} {bahanBaku.satuan}
                </span>
            ),
        },
        {
            label: 'Stok Minimum',
            value: `${formatNumber(bahanBaku.stok_minimum)} ${bahanBaku.satuan}`,
        },
        {
            label: 'Safety Stock',
            value: `${formatNumber(bahanBaku.safety_stock)} ${bahanBaku.satuan}`,
        },
        {
            label: 'Reorder Point',
            value: (
                <span className={bahanBaku.stok_saat_ini <= bahanBaku.reorder_point ? 'font-semibold text-red-600' : ''}>
                    {formatNumber(bahanBaku.reorder_point)} {bahanBaku.satuan}
                </span>
            ),
        },
    ];

    const eoqInfo = [
        {
            label: 'Economic Order Quantity (EOQ)',
            value: (
                <span className="text-lg font-semibold text-blue-600 dark:text-blue-400">
                    {formatNumber(bahanBaku.eoq)} {bahanBaku.satuan}
                </span>
            ),
        },
        {
            label: 'Demand Tahunan',
            value: `${formatNumber(bahanBaku.demand_tahunan)} ${bahanBaku.satuan}/tahun`,
        },
        {
            label: 'Lead Time',
            value: `${formatNumber(bahanBaku.lead_time)} hari`,
        },
    ];

    const costInfo = [
        {
            label: 'Biaya Pemesanan',
            value: `${formatCurrency(bahanBaku.biaya_pemesanan)} per pesanan`,
        },
        {
            label: 'Biaya Penyimpanan',
            value: `${formatCurrency(bahanBaku.biaya_penyimpanan)} per unit/tahun`,
        },
        {
            label: 'Total Nilai Stok',
            value: <span className="text-lg font-semibold">{formatCurrency(totalNilaiStok)}</span>,
        },
    ];

    return (
        <ShowPageTemplate
            title={bahanBaku.nama_bahan}
            pageTitle={`Detail Bahan Baku ${bahanBaku.nama_bahan}`}
            breadcrumbs={breadcrumbs}
            subtitle={`ID: ${bahanBaku.bahan_baku_id}`}
            badge={stockStatus}
            actions={actions}
        >
            <div className="space-y-8">
                <InfoSection title="Informasi Dasar" items={basicInfo} />

                <InfoSection title="Informasi Stok" items={stockInfo} />

                <InfoSection title="Analisis EOQ" items={eoqInfo} />

                <InfoSection title="Informasi Biaya" items={costInfo} />

                <TimestampSection
                    createdAt={bahanBaku.created_at}
                    updatedAt={bahanBaku.updated_at}
                    createdBy={bahanBaku.created_by?.nama_lengkap}
                    updatedBy={bahanBaku.updated_by?.nama_lengkap}
                />
            </div>
        </ShowPageTemplate>
    );
}
