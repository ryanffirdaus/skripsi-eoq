<?php

namespace Database\Factories;

use App\Models\PesananDetail;
use App\Models\Pesanan;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;

class PesananDetailFactory extends Factory
{
    protected $model = PesananDetail::class;

    public function definition(): array
    {
        $produk = Produk::inRandomOrder()->first();
        $jumlahProduk = $this->faker->numberBetween(1, 20);
        $hargaSatuan = $produk ? $produk->hpp_produk : $this->faker->numberBetween(10000, 500000);

        return [
            // pesanan_detail_id akan di-generate oleh model
            // pesanan_id akan diisi dari seeder
            'produk_id' => $produk ? $produk->produk_id : null,
            'jumlah_produk' => $jumlahProduk,
            'harga_satuan' => $hargaSatuan,
            // subtotal akan di-calculate otomatis oleh model
        ];
    }
}
