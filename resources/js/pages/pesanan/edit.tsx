import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { colors } from '@/lib/colors';
import { type BreadcrumbItem } from '@/types';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import { Head, Link, useForm } from '@inertiajs/react';
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
}

interface PesananProduk {
    produk_id: string;
    jumlah_produk: number;
    harga_satuan: number;
}

interface Pesanan {
    pesanan_id: string;
    pelanggan_id: string;
    tanggal_pesanan: string;
    status: string;
    catatan?: string;
    total_harga: number;
    pelanggan?: Pelanggan;
    produk?: Array<{
        produk_id: string;
        nama_produk: string;
        harga_jual: number;
        pivot: {
            jumlah_produk: number;
            harga_satuan: number;
        };
    }>;
}

interface Props {
    pesanan: Pesanan;
    pelanggan: Pelanggan[];
    produk: Produk[];
}

export default function Edit({ pesanan, pelanggan, produk }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Pesanan',
            href: '/pesanan',
        },
        {
            title: 'Edit Pesanan',
            href: `/pesanan/${pesanan.pesanan_id}/edit`,
        },
    ];

    const [selectedProducts, setSelectedProducts] = useState<PesananProduk[]>(
        pesanan.produk?.map((p) => ({
            produk_id: p.produk_id,
            jumlah_produk: p.pivot.jumlah_produk,
            harga_satuan: p.pivot.harga_satuan,
        })) || [],
    );

    const { data, setData, put, processing, errors } = useForm({
        pelanggan_id: pesanan.pelanggan_id,
        tanggal_pesanan: pesanan?.tanggal_pesanan?.split('T')[0] || '',
        status: pesanan.status,
        catatan: pesanan.catatan || '',
        products: selectedProducts,
    });

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    const calculateTotal = () => {
        return selectedProducts.reduce((total, item) => {
            return total + item.jumlah_produk * item.harga_satuan;
        }, 0);
    };

    const addProduct = () => {
        setSelectedProducts([...selectedProducts, { produk_id: '', jumlah_produk: 1, harga_satuan: 0 }]);
    };

    const removeProduct = (index: number) => {
        const newProducts = selectedProducts.filter((_, i) => i !== index);
        setSelectedProducts(newProducts);
        setData('products', newProducts);
    };

    const updateProduct = (index: number, field: keyof PesananProduk, value: string | number) => {
        const newProducts = [...selectedProducts];
        if (field === 'produk_id' && typeof value === 'string') {
            const selectedProduk = produk.find((p) => p.produk_id === value);
            newProducts[index] = {
                ...newProducts[index],
                produk_id: value,
                harga_satuan: selectedProduk?.harga_jual || 0,
            };
        } else {
            newProducts[index] = { ...newProducts[index], [field]: value };
        }
        setSelectedProducts(newProducts);
        setData('products', newProducts);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/pesanan/${pesanan.pesanan_id}`, {
            preserveState: false,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Pesanan" />

            <div className="space-y-6">
                <div className={colors.card.base}>
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Edit Pesanan {pesanan.pesanan_id}</h1>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6 p-6">
                        {/* Basic Information */}
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="pelanggan_id" className={colors.label.base}>
                                    Pelanggan
                                </Label>
                                <Select value={data.pelanggan_id} onValueChange={(value) => setData('pelanggan_id', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih pelanggan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {pelanggan.map((customer) => (
                                            <SelectItem key={customer.pelanggan_id} value={customer.pelanggan_id}>
                                                {customer.nama_pelanggan}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.pelanggan_id && <p className="text-sm text-red-600 dark:text-red-400">{errors.pelanggan_id}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="tanggal_pesanan" className={colors.label.base}>
                                    Tanggal Pesanan
                                </Label>
                                <Input
                                    id="tanggal_pesanan"
                                    type="date"
                                    value={data.tanggal_pesanan}
                                    onChange={(e) => setData('tanggal_pesanan', e.target.value)}
                                    className={colors.input.base}
                                />
                                {errors.tanggal_pesanan && <p className="text-sm text-red-600 dark:text-red-400">{errors.tanggal_pesanan}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="status" className={colors.label.base}>
                                    Status
                                </Label>
                                <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pending">Pending</SelectItem>
                                        <SelectItem value="diproses">Diproses</SelectItem>
                                        <SelectItem value="dikirim">Dikirim</SelectItem>
                                        <SelectItem value="selesai">Selesai</SelectItem>
                                        <SelectItem value="dibatalkan">Dibatalkan</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.status && <p className="text-sm text-red-600 dark:text-red-400">{errors.status}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="catatan" className={colors.label.base}>
                                    Catatan
                                </Label>
                                <textarea
                                    id="catatan"
                                    value={data.catatan}
                                    onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('catatan', e.target.value)}
                                    placeholder="Catatan pesanan..."
                                    className={`${colors.input.base} resize-vertical min-h-[100px]`}
                                />
                                {errors.catatan && <p className="text-sm text-red-600 dark:text-red-400">{errors.catatan}</p>}
                            </div>
                        </div>

                        {/* Products Section */}
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Produk Pesanan</h3>
                                <Button type="button" variant="outline" size="sm" onClick={addProduct} className="flex items-center gap-2">
                                    <PlusIcon className="h-4 w-4" />
                                    Tambah Produk
                                </Button>
                            </div>

                            <div className="space-y-4">
                                {selectedProducts.map((item, index) => (
                                    <div
                                        key={index}
                                        className="grid grid-cols-1 gap-4 rounded-lg border border-gray-200 p-4 md:grid-cols-4 dark:border-gray-700"
                                    >
                                        <div className="space-y-2">
                                            <Label className={colors.label.base}>Produk</Label>
                                            <Select value={item.produk_id} onValueChange={(value) => updateProduct(index, 'produk_id', value)}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Pilih produk" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {produk.map((product) => (
                                                        <SelectItem key={product.produk_id} value={product.produk_id}>
                                                            {product.nama_produk} - {formatCurrency(product.harga_jual)}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label className={colors.label.base}>Jumlah</Label>
                                            <Input
                                                type="number"
                                                min="1"
                                                value={item.jumlah_produk}
                                                onChange={(e) => updateProduct(index, 'jumlah_produk', parseInt(e.target.value) || 1)}
                                                className={colors.input.base}
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label className={colors.label.base}>Harga Satuan</Label>
                                            <Input
                                                type="number"
                                                min="0"
                                                value={item.harga_satuan}
                                                onChange={(e) => updateProduct(index, 'harga_satuan', parseFloat(e.target.value) || 0)}
                                                className={colors.input.base}
                                            />
                                        </div>

                                        <div className="flex flex-col space-y-2">
                                            <Label className={colors.label.base}>Subtotal</Label>
                                            <div className="flex items-center justify-between">
                                                <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                    {formatCurrency(item.jumlah_produk * item.harga_satuan)}
                                                </span>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => removeProduct(index)}
                                                    className="text-red-600 hover:text-red-700"
                                                >
                                                    <TrashIcon className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {errors.products && <p className="text-sm text-red-600 dark:text-red-400">{errors.products}</p>}

                            {/* Total */}
                            <div className="flex justify-end rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                <div className="text-right">
                                    <Label className={colors.label.base}>Total Pesanan</Label>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">{formatCurrency(calculateTotal())}</p>
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
                            <Link href="/pesanan">
                                <Button variant="outline" type="button">
                                    Batal
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Memperbarui...' : 'Perbarui Pesanan'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
