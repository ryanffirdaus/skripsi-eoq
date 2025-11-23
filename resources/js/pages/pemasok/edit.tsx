import { FormField, TextArea, TextInput } from '@/components/form/form-fields';
import FormTemplate from '@/components/form/form-template';
import { type BreadcrumbItem } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

// Interface untuk data pemasok
interface Pemasok {
    pemasok_id: string;
    nama_pemasok: string;
    narahubung: string | null;
    email: string | null;
    nomor_telepon: string | null;
    alamat: string | null;
    catatan: string | null;
}

interface Props {
    pemasok: Pemasok;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pemasok',
        href: '/pemasok',
    },
    {
        title: 'Edit',
        href: '#',
    },
];

export default function Edit({ pemasok }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        nama_pemasok: pemasok.nama_pemasok || '',
        narahubung: pemasok.narahubung || '',
        email: pemasok.email || '',
        nomor_telepon: pemasok.nomor_telepon || '',
        alamat: pemasok.alamat || '',
        catatan: pemasok.catatan || '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        put(`/pemasok/${pemasok.pemasok_id}`);
    };

    return (
        <FormTemplate
            title={`Edit Pemasok: ${pemasok.nama_pemasok}`}
            breadcrumbs={breadcrumbs}
            backUrl="/pemasok"
            onSubmit={handleSubmit}
            processing={processing}
        >
            {/* Informasi Pemasok */}
            <div className="space-y-6">
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <FormField id="nama_pemasok" label="Nama Pemasok" error={errors.nama_pemasok} required>
                        <TextInput
                            id="nama_pemasok"
                            value={data.nama_pemasok}
                            onChange={(e) => setData('nama_pemasok', e.target.value)}
                            placeholder="Masukkan nama pemasok"
                            error={errors.nama_pemasok}
                        />
                    </FormField>

                    <FormField id="email" label="Email" error={errors.email} required>
                        <TextInput
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="Masukkan alamat email"
                            error={errors.email}
                        />
                    </FormField>

                    <FormField id="narahubung" label="Narahubung" error={errors.narahubung} required>
                        <TextInput
                            id="narahubung"
                            value={data.narahubung}
                            onChange={(e) => setData('narahubung', e.target.value)}
                            placeholder="Masukkan nama narahubung"
                            error={errors.narahubung}
                        />
                    </FormField>

                    <FormField id="nomor_telepon" label="Nomor Telepon" error={errors.nomor_telepon} required>
                        <TextInput
                            id="nomor_telepon"
                            value={data.nomor_telepon}
                            onChange={(e) => setData('nomor_telepon', e.target.value)}
                            placeholder="Masukkan nomor telepon"
                            error={errors.nomor_telepon}
                        />
                    </FormField>
                </div>
            </div>

            {/* Informasi Alamat */}
            <div className="space-y-6">
                <div className="space-y-4">
                    <FormField id="alamat" label="Alamat" error={errors.alamat} required>
                        <TextArea
                            id="alamat"
                            value={data.alamat}
                            onChange={(e) => setData('alamat', e.target.value)}
                            placeholder="Masukkan alamat lengkap"
                            rows={3}
                            error={errors.alamat}
                        />
                    </FormField>
                </div>
            </div>

            {/* Catatan */}
            <div className="space-y-6">
                <FormField id="catatan" label="Catatan" error={errors.catatan}>
                    <TextArea
                        id="catatan"
                        value={data.catatan}
                        onChange={(e) => setData('catatan', e.target.value)}
                        placeholder="Masukkan catatan tambahan (opsional)"
                        rows={3}
                        error={errors.catatan}
                    />
                </FormField>
            </div>
        </FormTemplate>
    );
}
