import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { useState } from 'react';

interface Pengiriman {
    pengiriman_id: string;
    pesanan_id: string;
    nomor_resi?: string;
    kurir: string;
    biaya_pengiriman: number;
    estimasi_hari: number;
    status: string;
    catatan?: string;
    tanggal_kirim?: string | null;
    tanggal_diterima?: string | null;
}

interface Props {
    pengiriman: Pengiriman;
}

export default function Edit({ pengiriman }: Props) {
    const [formData, setFormData] = useState({
        nomor_resi: pengiriman.nomor_resi || '',
        kurir: pengiriman.kurir,
        biaya_pengiriman: pengiriman.biaya_pengiriman.toString(),
        estimasi_hari: pengiriman.estimasi_hari.toString(),
        status: pengiriman.status,
        tanggal_kirim: pengiriman.tanggal_kirim ?? '',
        tanggal_diterima: pengiriman.tanggal_diterima ?? '',
        catatan: pengiriman.catatan || '',
    });

    const [loading, setLoading] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);

        router.put(
            `/pengiriman/${pengiriman.pengiriman_id}`,
            {
                ...formData,
                biaya_pengiriman: parseFloat(formData.biaya_pengiriman),
                estimasi_hari: parseInt(formData.estimasi_hari),
                tanggal_kirim: formData.tanggal_kirim || null,
                tanggal_diterima: formData.tanggal_diterima || null,
            },
            {
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleInputChange = (field: string, value: string) => {
        setFormData((prev) => ({
            ...prev,
            [field]: value,
        }));
    };

    return (
        <AppLayout>
            <Head title="Edit Pengiriman" />

            <div className="container mx-auto py-6">
                <div className="mb-6 flex items-center gap-4">
                    <Button variant="outline" size="sm" onClick={() => router.visit('/pengiriman')}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Kembali
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold">Edit Pengiriman</h1>
                        <p className="text-muted-foreground">ID: {pengiriman.pengiriman_id}</p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Form Edit Pengiriman</CardTitle>
                        <CardDescription>Perbarui data pengiriman pesanan</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="kurir">Kurir *</Label>
                                    <Input
                                        id="kurir"
                                        value={formData.kurir}
                                        onChange={(e) => handleInputChange('kurir', e.target.value)}
                                        placeholder="Contoh: JNE, TIKI, POS Indonesia"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="biaya_pengiriman">Biaya Pengiriman *</Label>
                                    <Input
                                        id="biaya_pengiriman"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={formData.biaya_pengiriman}
                                        onChange={(e) => handleInputChange('biaya_pengiriman', e.target.value)}
                                        placeholder="0"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="estimasi_hari">Estimasi Hari *</Label>
                                    <Input
                                        id="estimasi_hari"
                                        type="number"
                                        min="1"
                                        value={formData.estimasi_hari}
                                        onChange={(e) => handleInputChange('estimasi_hari', e.target.value)}
                                        placeholder="1"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="nomor_resi">Nomor Resi</Label>
                                    <Input
                                        id="nomor_resi"
                                        value={formData.nomor_resi}
                                        onChange={(e) => handleInputChange('nomor_resi', e.target.value)}
                                        placeholder="Masukkan nomor resi jika ada"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="status">Status *</Label>
                                    <Select value={formData.status} onValueChange={(value) => handleInputChange('status', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pending">Menunggu Dikirim</SelectItem>
                                            <SelectItem value="dikirim">Dikirim</SelectItem>
                                            <SelectItem value="selesai">Selesai</SelectItem>
                                            <SelectItem value="dibatalkan">Dibatalkan</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {formData.status === 'dikirim' && (
                                    <div className="space-y-2">
                                        <Label htmlFor="tanggal_kirim">Tanggal Kirim</Label>
                                        <Input
                                            id="tanggal_kirim"
                                            type="date"
                                            value={formData.tanggal_kirim ?? ''}
                                            onChange={(e) => handleInputChange('tanggal_kirim', e.target.value)}
                                        />
                                    </div>
                                )}

                                {formData.status === 'selesai' && (
                                    <div className="space-y-2">
                                        <Label htmlFor="tanggal_diterima">Tanggal Diterima</Label>
                                        <Input
                                            id="tanggal_diterima"
                                            type="date"
                                            value={formData.tanggal_diterima ?? ''}
                                            onChange={(e) => handleInputChange('tanggal_diterima', e.target.value)}
                                        />
                                    </div>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="catatan">Catatan</Label>
                                <Textarea
                                    id="catatan"
                                    value={formData.catatan}
                                    onChange={(e) => handleInputChange('catatan', e.target.value)}
                                    placeholder="Catatan tambahan tentang pengiriman"
                                    className="min-h-[100px]"
                                />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={loading} className="flex items-center gap-2">
                                    <Save className="h-4 w-4" />
                                    {loading ? 'Menyimpan...' : 'Simpan Perubahan'}
                                </Button>
                                <Button type="button" variant="outline" onClick={() => router.visit('/pengiriman')} disabled={loading}>
                                    Batal
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
