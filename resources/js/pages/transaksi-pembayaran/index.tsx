import { Head } from '@inertiajs/react';

import { TBody } from '@/components/table/t-body';
import { TFoot } from '@/components/table/t-foot';
import { THead } from '@/components/table/t-head';
import { TableTemplate } from '@/components/table/table-template';
import { AppLayout } from '@/layouts/app-layout';

export default function Index({ transaksiPembayaran }) {
    const columns = [
        { key: 'pembelian.nomor_po', label: 'Nomor PO' },
        { key: 'tanggal_pembayaran', label: 'Tgl. Pembayaran', type: 'date' },
        { key: 'total_pembayaran', label: 'Total', type: 'currency' },
        { key: 'pembelian.pemasok.nama', label: 'Pemasok' },
    ];

    return (
        <AppLayout>
            <Head title="Transaksi Pembayaran" />

            <TableTemplate title="Transaksi Pembayaran" addRoute={route('transaksi-pembayaran.create')} addLabel="Catat Pembayaran">
                <THead columns={columns} />
                <TBody data={transaksiPembayaran.data} columns={columns} showRoute={'transaksi-pembayaran.show'} showKey="transaksi_pembayaran_id" />
                <TFoot colSpan={columns.length} data={transaksiPembayaran} />
            </TableTemplate>
        </AppLayout>
    );
}
