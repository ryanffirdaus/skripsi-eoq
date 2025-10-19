import { InfoSection } from '@/components/info-section';
import ShowPageTemplate from '@/components/templates/show-page-template';
import TimestampSection from '@/components/timestamp-section';
import { formatCurrency, formatDate } from '@/lib/formatters';
import { type BreadcrumbItem } from '@/types';

interface Pelanggan {
    nama_pelanggan: string;
    alamat_pelanggan: string;
    kota_pelanggan: string;
    telepon_pelanggan: string;
}

interface Pesanan {
    pesanan_id: string;
    tanggal_pesanan: string;
    total_harga: number;
    status: string;
    pelanggan: Pelanggan;
}

interface User {
    user_id: string;
    nama_lengkap: string;
}

interface Pengiriman {
    pengiriman_id: string;
    pesanan_id: string;
    nomor_resi?: string;
    kurir: string;
    jenis_layanan: string;
    biaya_pengiriman: number;
    estimasi_hari: number;
    status: string;
    status_label: string;
    tanggal_kirim?: string;
    tanggal_diterima?: string;
    catatan?: string;
    pesanan: Pesanan;
    created_by?: string;
    updated_by?: string;
    created_at?: string;
    updated_at?: string;
    createdBy?: User;
    updatedBy?: User;
}

interface Props {
    pengiriman: Pengiriman;
    permissions?: {
        canEdit?: boolean;
        canDelete?: boolean;
    };
}

const getStatusBadge = (status: string, status_label: string) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        dikirim: 'bg-blue-100 text-blue-800 border-blue-200',
        selesai: 'bg-green-100 text-green-800 border-green-200',
        dibatalkan: 'bg-red-100 text-red-800 border-red-200',
    };
    return {
        label: status_label,
        color: colors[status as keyof typeof colors] || colors.pending,
    };
};

export default function Show({ pengiriman, permissions = {} }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pengiriman', href: '/pengiriman' },
        { title: `Detail ${pengiriman.pengiriman_id}`, href: `/pengiriman/${pengiriman.pengiriman_id}` },
    ];

    // Actions - hanya tampil jika ada permission
    const actions = [
        ...(permissions.canEdit
            ? [
                  {
                      label: 'Edit Pengiriman',
                      href: `/pengiriman/${pengiriman.pengiriman_id}/edit`,
                      variant: 'outline' as const,
                  },
              ]
            : []),
        {
            label: 'Kembali',
            href: '/pengiriman',
            variant: 'outline' as const,
        },
    ];

    const pengirimanInfo = [
        {
            label: 'ID Pengiriman',
            value: pengiriman.pengiriman_id,
        },
        {
            label: 'Status',
            value: (
                <span
                    className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium ${getStatusBadge(pengiriman.status, pengiriman.status_label).color}`}
                >
                    {pengiriman.status_label}
                </span>
            ),
        },
        {
            label: 'Kurir',
            value: pengiriman.kurir,
        },
        {
            label: 'Jenis Layanan',
            value: pengiriman.jenis_layanan,
        },
        ...(pengiriman.nomor_resi
            ? [
                  {
                      label: 'Nomor Resi',
                      value: <span className="font-mono font-semibold">{pengiriman.nomor_resi}</span>,
                  },
              ]
            : []),
        {
            label: 'Biaya Pengiriman',
            value: <span className="text-lg font-semibold text-green-600">{formatCurrency(pengiriman.biaya_pengiriman)}</span>,
        },
        {
            label: 'Estimasi Pengiriman',
            value: `${pengiriman.estimasi_hari} hari`,
        },
        ...(pengiriman.catatan
            ? [
                  {
                      label: 'Catatan',
                      value: pengiriman.catatan,
                  },
              ]
            : []),
    ];

    const pesananInfo = [
        {
            label: 'ID Pesanan',
            value: pengiriman.pesanan.pesanan_id,
        },
        {
            label: 'Tanggal Pesanan',
            value: formatDate(pengiriman.pesanan.tanggal_pesanan, {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }),
        },
        {
            label: 'Total Harga',
            value: <span className="text-lg font-semibold text-green-600">{formatCurrency(pengiriman.pesanan.total_harga)}</span>,
        },
        {
            label: 'Nama Pelanggan',
            value: pengiriman.pesanan.pelanggan.nama_pelanggan,
        },
        {
            label: 'Alamat Pelanggan',
            value: `${pengiriman.pesanan.pelanggan.alamat_pelanggan}, ${pengiriman.pesanan.pelanggan.kota_pelanggan}`,
        },
        {
            label: 'Nomor Telepon',
            value: pengiriman.pesanan.pelanggan.telepon_pelanggan,
        },
    ];

    const timelineInfo = [
        ...(pengiriman.tanggal_diterima
            ? [
                  {
                      label: 'Tanggal Diterima',
                      value: formatDate(pengiriman.tanggal_diterima, {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric',
                      }),
                  },
              ]
            : []),
        ...(pengiriman.tanggal_kirim
            ? [
                  {
                      label: 'Tanggal Kirim',
                      value: formatDate(pengiriman.tanggal_kirim, {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric',
                      }),
                  },
              ]
            : []),
        {
            label: 'Pengiriman Dibuat',
            value: formatDate(pengiriman.created_at!, {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }),
        },
    ];

    return (
        <ShowPageTemplate
            title={pengiriman.pengiriman_id}
            pageTitle={`Detail Pengiriman ${pengiriman.pengiriman_id}`}
            breadcrumbs={breadcrumbs}
            subtitle={`Kurir: ${pengiriman.kurir}`}
            badge={getStatusBadge(pengiriman.status, pengiriman.status_label)}
            actions={actions}
        >
            <div className="space-y-8">
                <InfoSection title="Informasi Pengiriman" items={pengirimanInfo} />

                <InfoSection title="Informasi Pesanan" items={pesananInfo} />

                <InfoSection title="Timeline Pengiriman" items={timelineInfo} />

                <TimestampSection
                    createdAt={pengiriman.created_at || ''}
                    updatedAt={pengiriman.updated_at || ''}
                    createdBy={pengiriman.createdBy?.nama_lengkap}
                    updatedBy={pengiriman.updatedBy?.nama_lengkap}
                />
            </div>
        </ShowPageTemplate>
    );
}
