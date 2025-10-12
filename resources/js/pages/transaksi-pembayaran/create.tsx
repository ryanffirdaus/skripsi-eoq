import { Head, useForm } from '@inertiajs/react';

import { FormTemplate } from '@/components/form/form-template';
import { AppLayout } from '@/layouts/app-layout';

export default function Create({ pembelian }) {
    const { data, setData, post, errors, processing } = useForm({
        pembelian_id: '',
        tanggal_pembayaran: new Date().toISOString().slice(0, 10),
        total_pembayaran: '',
        bukti_pembayaran: null,
        deskripsi: '',
    });

    const formFields = [
        {
            name: 'pembelian_id',
            label: 'Purchase Order (PO)',
            type: 'select',
            options: pembelian.map((p) => ({ value: p.pembelian_id, label: `${p.nomor_po} - ${p.pemasok.nama}` })),
            onChange: (e) => {
                const selectedPembelian = pembelian.find((p) => p.pembelian_id === e.target.value);
                if (selectedPembelian) {
                    setData({
                        ...data,
                        pembelian_id: e.target.value,
                        total_pembayaran: selectedPembelian.total_harga,
                    });
                }
            },
        },
        { name: 'tanggal_pembayaran', label: 'Tanggal Pembayaran', type: 'date' },
        { name: 'total_pembayaran', label: 'Total Pembayaran', type: 'number' },
        { name: 'bukti_pembayaran', label: 'Bukti Pembayaran', type: 'file' },
        { name: 'deskripsi', label: 'Deskripsi', type: 'textarea' },
    ];

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('transaksi-pembayaran.store'));
    };

    return (
        <AppLayout>
            <Head title="Catat Pembayaran Baru" />
            <FormTemplate
                title="Catat Pembayaran Baru"
                data={data}
                setData={setData}
                errors={errors}
                formFields={formFields}
                handleSubmit={handleSubmit}
                processing={processing}
            />
        </AppLayout>
    );
}
