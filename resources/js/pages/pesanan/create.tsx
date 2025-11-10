import FormTemplate from '@/components/form/form-template';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { MinusIcon, PlusIcon } from '@heroicons/react/24/outline';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

interface Pelanggan {
    pelanggan_id: string;
    nama_pelanggan: string;
    email_pelanggan: string;
}

interface Produk {
    produk_id: string;
    nama_produk: string;
    harga_jual: number;
    satuan_produk: string;
    stok_produk: number;
}

interface ProdukItem {
    produk_id: string;
    jumlah_produk: number;
    harga_satuan: number;
}

interface Props {
    pelanggan: Pelanggan[];
    produk: Produk[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pesanan',
        href: '/pesanan',
    },
    {
        title: 'Tambah Pesanan',
        href: '/pesanan/create',
    },
];

export default function Create({ pelanggan, produk }: Props) {
    const [produkItems, setProdukItems] = useState<ProdukItem[]>([{ produk_id: '', jumlah_produk: 1, harga_satuan: 0 }]);

    const { data, setData, post, processing, errors } = useForm({
        pelanggan_id: '',
        tanggal_pemesanan: new Date().toISOString().split('T')[0],
        catatan: '',
        produk: produkItems,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Filter out empty produk items
        const validProduk = produkItems.filter((item) => item.produk_id && item.jumlah_produk > 0 && item.harga_satuan >= 0);

        // Update the form data before submission
        setData('produk', validProduk);

        post('/pesanan');
    };

    const addProdukItem = () => {
        const newItem: ProdukItem = { produk_id: '', jumlah_produk: 1, harga_satuan: 0 };
        const newItems = [...produkItems, newItem];
        setProdukItems(newItems);
        setData('produk', newItems);
    };

    const removeProdukItem = (index: number) => {
        if (produkItems.length > 1) {
            const newItems = produkItems.filter((_, i) => i !== index);
            setProdukItems(newItems);
            setData('produk', newItems);
        }
    };

    const updateProdukItem = (index: number, field: keyof ProdukItem, value: string | number) => {
        const newItems = [...produkItems];

        if (field === 'produk_id') {
            newItems[index][field] = value as string;
            // Auto-fill harga_satuan from produk data
            const selectedProduk = produk.find((p) => p.produk_id === value);
            if (selectedProduk) {
                newItems[index].harga_satuan = selectedProduk.harga_jual;
            }
        } else {
            newItems[index][field] = value as number;
        }

        setProdukItems(newItems);
        setData('produk', newItems);
    };

    const calculateTotal = () => {
        return produkItems.reduce((total, item) => {
            return total + item.jumlah_produk * item.harga_satuan;
        }, 0);
    };

    return (
        <FormTemplate title="Tambah Pesanan" breadcrumbs={breadcrumbs} backUrl="/pesanan" onSubmit={handleSubmit} processing={processing}>
            {/* Basic Info */}
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                        Pelanggan <span className="text-red-500">*</span>
                    </label>
                    <select
                        value={data.pelanggan_id}
                        onChange={(e) => setData('pelanggan_id', e.target.value)}
                        className={cn(colors.input.base, errors.pelanggan_id && colors.input.error)}
                        required
                    >
                        <option value="">Pilih Pelanggan</option>
                        {pelanggan.map((p) => (
                            <option key={p.pelanggan_id} value={p.pelanggan_id}>
                                {p.nama_pelanggan} ({p.email_pelanggan})
                            </option>
                        ))}
                    </select>
                    {errors.pelanggan_id && <p className={colors.text.error}>{errors.pelanggan_id}</p>}
                </div>

                <div>
                    <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                        Tanggal Pemesanan <span className="text-red-500">*</span>
                    </label>
                    <input
                        type="date"
                        value={data.tanggal_pemesanan}
                        onChange={(e) => setData('tanggal_pemesanan', e.target.value)}
                        className={cn(colors.input.base, errors.tanggal_pemesanan && colors.input.error)}
                        required
                    />
                    {errors.tanggal_pemesanan && <p className={colors.text.error}>{errors.tanggal_pemesanan}</p>}
                </div>

                <div>
                    <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>Catatan</label>
                    <textarea
                        value={data.catatan}
                        onChange={(e) => setData('catatan', e.target.value)}
                        className={cn(colors.input.base, errors.catatan && colors.input.error)}
                        placeholder="Masukkan catatan terkait pelanggan"
                        rows={3}
                    />
                    {errors.catatan && <p className={colors.text.error}>{errors.catatan}</p>}
                </div>
            </div>

            {/* Produk Items Section */}
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h3 className="text-lg font-medium text-gray-900">Produk</h3>
                    <Button type="button" onClick={addProdukItem} variant="outline" size="sm" className="flex items-center gap-2">
                        <PlusIcon className="h-4 w-4" />
                        Tambah Produk
                    </Button>
                </div>

                {produkItems.map((item, index) => (
                    <div key={index} className={cn('rounded-lg border p-4', colors.border.primary)}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                                    Produk <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={item.produk_id}
                                    onChange={(e) => updateProdukItem(index, 'produk_id', e.target.value)}
                                    className={cn(colors.input.base, errors[`produk.${index}.produk_id`] && colors.input.error)}
                                    required
                                >
                                    <option value="">Pilih Produk</option>
                                    {produk.map((p) => (
                                        <option key={p.produk_id} value={p.produk_id}>
                                            {p.nama_produk} (Stok: {p.stok_produk} {p.satuan_produk})
                                        </option>
                                    ))}
                                </select>
                                {errors[`produk.${index}.produk_id`] && <p className={colors.text.error}>{errors[`produk.${index}.produk_id`]}</p>}
                            </div>

                            <div>
                                <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                                    Jumlah <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    value={item.jumlah_produk}
                                    onChange={(e) => updateProdukItem(index, 'jumlah_produk', parseInt(e.target.value) || 0)}
                                    className={cn(colors.input.base, errors[`produk.${index}.jumlah_produk`] && colors.input.error)}
                                    required
                                />
                                {errors[`produk.${index}.jumlah_produk`] && (
                                    <p className={colors.text.error}>{errors[`produk.${index}.jumlah_produk`]}</p>
                                )}
                            </div>

                            <div>
                                <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                                    Harga Satuan <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={item.harga_satuan}
                                    onChange={(e) => updateProdukItem(index, 'harga_satuan', parseFloat(e.target.value) || 0)}
                                    className={cn(colors.input.base, errors[`produk.${index}.harga_satuan`] && colors.input.error)}
                                    required
                                />
                                {errors[`produk.${index}.harga_satuan`] && (
                                    <p className={colors.text.error}>{errors[`produk.${index}.harga_satuan`]}</p>
                                )}
                            </div>

                            <div className="flex items-end">
                                <div className="flex-1">
                                    <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>Subtotal</label>
                                    <div className={cn('rounded-md border bg-gray-50 px-3 py-2', colors.border.primary)}>
                                        Rp {(item.jumlah_produk * item.harga_satuan).toLocaleString('id-ID')}
                                    </div>
                                </div>
                                {produkItems.length > 1 && (
                                    <Button type="button" onClick={() => removeProdukItem(index)} variant="destructive" size="sm" className="ml-2">
                                        <MinusIcon className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                ))}

                {/* Total Section */}
                <div className={cn('rounded-lg border bg-gray-50 p-4', colors.border.primary)}>
                    <div className="flex items-center justify-between text-lg font-semibold">
                        <span>Total Harga:</span>
                        <span>Rp {calculateTotal().toLocaleString('id-ID')}</span>
                    </div>
                </div>

                {errors.produk && (
                    <Alert variant="destructive">
                        <AlertDescription>{errors.produk}</AlertDescription>
                    </Alert>
                )}
            </div>
        </FormTemplate>
    );
}
