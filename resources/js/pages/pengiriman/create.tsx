import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Head, Link, useForm } from '@inertiajs/react';
import { ChangeEvent, FormEvent } from 'react';

interface Pesanan {
    pesanan_id: string;
    pelanggan_id: string;
    total_harga: number;
    pelanggan: {
        nama_pelanggan: string;
        alamat_pelanggan: string;
        kota_pelanggan: string;
        telepon_pelanggan: string;
    };
}

interface Props {
    pesanan: Pesanan[];
}

interface FormData {
    pesanan_id: string;
    nomor_resi: string;
    kurir: string;
    jenis_layanan: string;
    biaya_pengiriman: string;
    estimasi_hari: string;
    catatan: string;
}

const kurirOptions = [
    { value: 'JNE', label: 'JNE' },
    { value: 'J&T', label: 'J&T' },
    { value: 'TIKI', label: 'TIKI' },
    { value: 'POS Indonesia', label: 'POS Indonesia' },
    { value: 'SiCepat', label: 'SiCepat' },
    { value: 'AnterAja', label: 'AnterAja' },
];

const jenisLayananOptions: Record<string, string[]> = {
    JNE: ['REG', 'YES', 'OKE'],
    'J&T': ['EZ', 'REG', 'SUPER'],
    TIKI: ['REG', 'ECO', 'ONS'],
    'POS Indonesia': ['REGULER', 'EXPRESS'],
    SiCepat: ['REG', 'BEST'],
    AnterAja: ['REGULER', 'SAME DAY'],
};

export default function Create({ pesanan }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        pesanan_id: '',
        nomor_resi: '',
        kurir: '',
        jenis_layanan: '',
        biaya_pengiriman: '',
        estimasi_hari: '1',
        catatan: '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/pengiriman');
    };

    const handleKurirChange = (value: string) => {
        setData({
            ...data,
            kurir: value,
            jenis_layanan: '', // Reset jenis layanan when kurir changes
        });
    };

    const selectedPesanan = pesanan.find((p) => p.pesanan_id === data.pesanan_id);
    const availableLayanan = data.kurir ? jenisLayananOptions[data.kurir] || [] : [];

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pengiriman', href: '/pengiriman' },
                { title: 'Buat Pengiriman', href: '/pengiriman/create' },
            ]}
        >
            <Head title="Buat Pengiriman" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Buat Pengiriman</h1>
                    <p className="text-muted-foreground">Buat pengiriman baru untuk pesanan yang sudah dikonfirmasi</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Informasi Pesanan */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Informasi Pesanan</CardTitle>
                                <CardDescription>Pilih pesanan yang akan dikirim</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="pesanan_id">Pesanan</Label>
                                    <Select value={data.pesanan_id} onValueChange={(value) => setData('pesanan_id', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih pesanan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {pesanan.map((pesanan) => (
                                                <SelectItem key={pesanan.pesanan_id} value={pesanan.pesanan_id}>
                                                    <div className="flex flex-col">
                                                        <span>{pesanan.pesanan_id}</span>
                                                        <span className="text-sm text-muted-foreground">
                                                            {pesanan.pelanggan.nama_pelanggan} - Rp {pesanan.total_harga.toLocaleString()}
                                                        </span>
                                                    </div>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.pesanan_id && <p className="text-sm text-destructive">{errors.pesanan_id}</p>}
                                </div>

                                {selectedPesanan && (
                                    <div className="rounded-lg border bg-muted/50 p-3">
                                        <h4 className="mb-2 font-medium">Detail Pesanan</h4>
                                        <div className="space-y-1 text-sm">
                                            <p>
                                                <span className="font-medium">Pelanggan:</span> {selectedPesanan.pelanggan.nama_pelanggan}
                                            </p>
                                            <p>
                                                <span className="font-medium">Total:</span> Rp {selectedPesanan.total_harga.toLocaleString()}
                                            </p>
                                            <p>
                                                <span className="font-medium">Alamat:</span> {selectedPesanan.pelanggan.alamat_pelanggan},{' '}
                                                {selectedPesanan.pelanggan.kota_pelanggan}
                                            </p>
                                            <p>
                                                <span className="font-medium">Telepon:</span> {selectedPesanan.pelanggan.telepon_pelanggan}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Informasi Kurir */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Informasi Pengiriman</CardTitle>
                                <CardDescription>Pilih kurir dan layanan pengiriman</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="kurir">Kurir</Label>
                                    <Select value={data.kurir} onValueChange={handleKurirChange}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih kurir" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {kurirOptions.map((kurir) => (
                                                <SelectItem key={kurir.value} value={kurir.value}>
                                                    {kurir.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.kurir && <p className="text-sm text-destructive">{errors.kurir}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="jenis_layanan">Jenis Layanan</Label>
                                    <Select
                                        value={data.jenis_layanan}
                                        onValueChange={(value) => setData('jenis_layanan', value)}
                                        disabled={!data.kurir}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih jenis layanan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableLayanan.map((layanan) => (
                                                <SelectItem key={layanan} value={layanan}>
                                                    {layanan}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.jenis_layanan && <p className="text-sm text-destructive">{errors.jenis_layanan}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="nomor_resi">Nomor Resi (Opsional)</Label>
                                    <Input
                                        id="nomor_resi"
                                        value={data.nomor_resi}
                                        onChange={(e) => setData('nomor_resi', e.target.value)}
                                        placeholder="Masukkan nomor resi"
                                        className={cn(errors.nomor_resi && 'border-destructive')}
                                    />
                                    {errors.nomor_resi && <p className="text-sm text-destructive">{errors.nomor_resi}</p>}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="biaya_pengiriman">Biaya Pengiriman</Label>
                                        <Input
                                            id="biaya_pengiriman"
                                            type="number"
                                            value={data.biaya_pengiriman}
                                            onChange={(e) => setData('biaya_pengiriman', e.target.value)}
                                            placeholder="0"
                                            className={cn(errors.biaya_pengiriman && 'border-destructive')}
                                        />
                                        {errors.biaya_pengiriman && <p className="text-sm text-destructive">{errors.biaya_pengiriman}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="estimasi_hari">Estimasi (Hari)</Label>
                                        <Input
                                            id="estimasi_hari"
                                            type="number"
                                            value={data.estimasi_hari}
                                            onChange={(e) => setData('estimasi_hari', e.target.value)}
                                            placeholder="1"
                                            min="1"
                                            className={cn(errors.estimasi_hari && 'border-destructive')}
                                        />
                                        {errors.estimasi_hari && <p className="text-sm text-destructive">{errors.estimasi_hari}</p>}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="catatan">Catatan (Opsional)</Label>
                                    <Textarea
                                        id="catatan"
                                        value={data.catatan}
                                        onChange={(e: ChangeEvent<HTMLTextAreaElement>) => setData('catatan', e.target.value)}
                                        placeholder="Catatan khusus untuk pengiriman"
                                        rows={3}
                                        className={cn(errors.catatan && 'border-destructive')}
                                    />
                                    {errors.catatan && <p className="text-sm text-destructive">{errors.catatan}</p>}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex items-center justify-end space-x-4">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/pengiriman">Batal</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan Pengiriman'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
