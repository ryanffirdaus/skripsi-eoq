import FormTemplate from '@/components/form/form-template';
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
}

interface PesananProduk {
    produk_id: string;
    jumlah_produk: number;
    harga_satuan: number;
}

interface PesananDetail {
    pesanan_detail_id: string;
    produk_id: string;
    jumlah_produk: number;
    harga_satuan: number;
    subtotal: number;
    produk: {
        produk_id: string;
        nama_produk: string;
        harga_jual: number;
    };
}

interface Pesanan {
    pesanan_id: string;
    pelanggan_id: string;
    tanggal_pemesanan: string;
    status: string;
    catatan?: string;
    total_harga: number;
    pelanggan?: Pelanggan;
    detail: PesananDetail[];
}

interface Props {
    pesanan: Pesanan;
    pelanggan: Pelanggan[];
    produk: Produk[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pesanan',
        href: '/pesanan',
    },
    {
        title: 'Ubah Pesanan',
        href: '#',
    },
];

export default function Edit({ pesanan, pelanggan, produk }: Props) {
    const [selectedProducts, setSelectedProducts] = useState<PesananProduk[]>(
        pesanan.detail?.map((d) => ({
            produk_id: d.produk_id,
            jumlah_produk: d.jumlah_produk,
            harga_satuan: d.harga_satuan,
        })) || [],
    );

    const { data, setData, put, processing, errors } = useForm({
        pelanggan_id: pesanan.pelanggan_id,
        tanggal_pemesanan: pesanan?.tanggal_pemesanan?.split('T')[0] || '',
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
        const newProducts = [...selectedProducts, { produk_id: '', jumlah_produk: 1, harga_satuan: 0 }];
        setSelectedProducts(newProducts);
        setData('products', newProducts);
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

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(`/pesanan/${pesanan.pesanan_id}`);
    }

    const statusOptions = [
        { value: 'menunggu', label: 'Menunggu' },
        { value: 'dikonfirmasi', label: 'Dikonfirmasi' },
        { value: 'siap', label: 'Siap' },
        { value: 'diproses', label: 'Diproses' },
        { value: 'dikirim', label: 'Dikirim' },
        { value: 'diterima', label: 'Diterima' },
        { value: 'selesai', label: 'Selesai' },
        { value: 'dibatalkan', label: 'Dibatalkan' },
    ];

    const pelangganOptions = pelanggan.map((p) => ({
        value: p.pelanggan_id,
        label: p.nama_pelanggan,
    }));

    const produkOptions = produk.map((p) => ({
        value: p.produk_id,
        label: `${p.nama_produk} - ${formatCurrency(p.harga_jual)}`,
    }));

    return (
        <FormTemplate
            title={`Ubah Pesanan: ${pesanan.pesanan_id}`}
            breadcrumbs={breadcrumbs}
            backUrl="/pesanan"
            onSubmit={handleSubmit}
            processing={processing}
            submitText="Simpan"
            processingText="Memperbarui..."
        >
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
                        {pelangganOptions.map((p) => (
                            <option key={p.value} value={p.value}>
                                {p.label}
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
                    <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                        Status <span className="text-red-500">*</span>
                    </label>
                    <select
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
                        className={cn(colors.input.base, errors.status && colors.input.error)}
                        required
                    >
                        <option value="">Pilih Status</option>
                        {statusOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    {errors.status && <p className={colors.text.error}>{errors.status}</p>}
                </div>

                <div>
                    <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>Catatan</label>
                    <textarea
                        value={data.catatan}
                        onChange={(e) => setData('catatan', e.target.value)}
                        className={cn(colors.input.base, errors.catatan && colors.input.error)}
                        placeholder="Masukkan catatan tambahan..."
                        rows={3}
                    />
                    {errors.catatan && <p className={colors.text.error}>{errors.catatan}</p>}
                </div>
            </div>

            {/* Products Section */}
            <div className="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                <div className="flex items-center justify-between">
                    <h3 className="text-lg font-medium text-gray-900 dark:text-white">Produk Pesanan</h3>
                    <Button type="button" onClick={addProduct} variant="outline" size="sm" className="flex items-center gap-2">
                        <PlusIcon className="h-4 w-4" />
                        Tambah Produk
                    </Button>
                </div>

                {selectedProducts.map((item, index) => (
                    <div key={index} className={cn('rounded-lg border p-4', colors.border.primary)}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                                    Produk <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={item.produk_id}
                                    onChange={(e) => updateProduct(index, 'produk_id', e.target.value)}
                                    className={cn(colors.input.base, errors[`products.${index}.produk_id`] && colors.input.error)}
                                    required
                                >
                                    <option value="">Pilih Produk</option>
                                    {produkOptions.map((p) => (
                                        <option key={p.value} value={p.value}>
                                            {p.label}
                                        </option>
                                    ))}
                                </select>
                                {errors[`products.${index}.produk_id`] && (
                                    <p className={colors.text.error}>{errors[`products.${index}.produk_id`]}</p>
                                )}
                            </div>

                            <div>
                                <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>
                                    Jumlah <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    value={item.jumlah_produk}
                                    onChange={(e) => updateProduct(index, 'jumlah_produk', parseInt(e.target.value) || 0)}
                                    className={cn(colors.input.base, errors[`products.${index}.jumlah_produk`] && colors.input.error)}
                                    required
                                />
                                {errors[`products.${index}.jumlah_produk`] && (
                                    <p className={colors.text.error}>{errors[`products.${index}.jumlah_produk`]}</p>
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
                                    onChange={(e) => updateProduct(index, 'harga_satuan', parseFloat(e.target.value) || 0)}
                                    className={cn(colors.input.base, errors[`products.${index}.harga_satuan`] && colors.input.error)}
                                    required
                                />
                                {errors[`products.${index}.harga_satuan`] && (
                                    <p className={colors.text.error}>{errors[`products.${index}.harga_satuan`]}</p>
                                )}
                            </div>

                            <div className="flex items-end">
                                <div className="flex-1">
                                    <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>Subtotal</label>
                                    <div className={cn('rounded-md border bg-gray-50 px-3 py-2', colors.border.primary)}>
                                        {formatCurrency(item.jumlah_produk * item.harga_satuan)}
                                    </div>
                                </div>
                                {selectedProducts.length > 1 && (
                                    <Button type="button" onClick={() => removeProduct(index)} variant="destructive" size="sm" className="ml-2">
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
                        <span>{formatCurrency(calculateTotal())}</span>
                    </div>
                </div>
            </div>
        </FormTemplate>
    );
}
