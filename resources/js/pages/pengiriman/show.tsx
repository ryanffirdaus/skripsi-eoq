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
    qr_code?: string;
    tracking_url?: string;
    tracking_identifier?: string;
    uses_resi?: boolean;
    pesanan: Pesanan;
    dibuat_oleh?: string;
    diubah_oleh?: string;
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
        menunggu: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        dalam_perjalanan: 'bg-blue-100 text-blue-800 border-blue-200',
        diterima: 'bg-green-100 text-green-800 border-green-200',
        dikirim: 'bg-indigo-100 text-indigo-800 border-indigo-200',
        selesai: 'bg-teal-100 text-teal-800 border-teal-200',
        dibatalkan: 'bg-red-100 text-red-800 border-red-200',
    };
    return {
        label: status_label,
        color: colors[status as keyof typeof colors] || colors.menunggu,
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

    // Download QR Code (SVG)
    const downloadQRCode = () => {
        if (!pengiriman.qr_code || !pengiriman.tracking_identifier) return;
        
        const blob = new Blob([pengiriman.qr_code], { type: 'image/svg+xml' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `QR-${pengiriman.tracking_identifier}.svg`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    };

    // Print QR Code
    const printQRCode = () => {
        if (!pengiriman.qr_code) return;
        
        const printWindow = window.open('', '', 'width=600,height=600');
        if (!printWindow) return;
        
        printWindow.document.write(`
            <html>
                <head>
                    <title>QR Code - ${pengiriman.nomor_resi}</title>
                    <style>
                        body {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            padding: 20px;
                            font-family: Arial, sans-serif;
                        }
                        img {
                            max-width: 300px;
                            margin: 20px auto;
                        }
                        .info {
                            text-align: center;
                            margin-top: 20px;
                        }
                    </style>
                </head>
                <body>
                    <h2>QR Code Tracking</h2>
                    <div style="text-align: center;">
                        ${pengiriman.qr_code}
                    </div>
                    <div class="info">
                        <p><strong>${pengiriman.uses_resi ? 'Nomor Resi' : 'ID Pengiriman'}:</strong> ${pengiriman.tracking_identifier}</p>
                        <p><strong>Kurir:</strong> ${pengiriman.kurir}</p>
                        <p><strong>ID Pengiriman:</strong> ${pengiriman.pengiriman_id}</p>
                        <p style="font-size: 12px; color: #666;">Scan QR code untuk tracking status pengiriman</p>
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    };

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

                {/* QR Code Section */}
                {pengiriman.qr_code && pengiriman.tracking_identifier && (
                    <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900">QR Code Tracking</h3>
                        <div className="flex flex-col items-center gap-6 md:flex-row md:items-start md:justify-between">
                            {/* QR Code Display */}
                            <div className="flex flex-col items-center gap-4">
                                <div className="rounded-lg border-2 border-gray-300 bg-white p-4">
                                    <div 
                                        className="w-64 h-64 flex items-center justify-center"
                                        dangerouslySetInnerHTML={{ __html: pengiriman.qr_code }}
                                    />
                                </div>
                                <p className="text-center text-sm text-gray-600">
                                    Scan QR code untuk tracking pengiriman
                                </p>
                            </div>

                            {/* QR Info & Actions */}
                            <div className="flex-1 space-y-4">
                                <div className="space-y-2">
                                    <div>
                                        <span className="text-sm font-medium text-gray-500">
                                            {pengiriman.uses_resi ? 'Nomor Resi:' : 'ID Pengiriman:'}
                                        </span>
                                        <p className="font-mono text-lg font-semibold">{pengiriman.tracking_identifier}</p>
                                        {!pengiriman.uses_resi && (
                                            <p className="text-xs text-amber-600 mt-1">
                                                ⚠️ Nomor resi belum diisi, menggunakan ID pengiriman
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <span className="text-sm font-medium text-gray-500">Tracking URL:</span>
                                        <p className="break-all text-sm text-blue-600">{pengiriman.tracking_url}</p>
                                    </div>
                                </div>

                                {/* Action Buttons */}
                                <div className="flex flex-col gap-2 sm:flex-row">
                                    <button
                                        onClick={downloadQRCode}
                                        className="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    >
                                        <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a 3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Download QR Code
                                    </button>
                                    <button
                                        onClick={printQRCode}
                                        className="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    >
                                        <svg className="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        Print QR Code
                                    </button>
                                </div>

                                <div className="rounded-md bg-blue-50 p-3">
                                    <p className="text-xs text-blue-800">
                                        <strong>Catatan:</strong> QR code ini dapat digunakan untuk tracking pengiriman secara publik tanpa perlu login.
                                        Share QR code ini kepada pelanggan untuk tracking mandiri.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

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
