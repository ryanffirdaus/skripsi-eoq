<?php

namespace Database\Seeders;

use App\Models\Pesanan;
use App\Models\Pelanggan;
use App\Models\Produk;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PesananSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $pelanggan = Pelanggan::all();
        $produk = Produk::all();

        // Create 100 pesanan records
        for ($i = 0; $i < 100; $i++) {
            $pesanan = Pesanan::factory()->create([
                'pelanggan_id' => $pelanggan->random()->pelanggan_id,
                'total_harga' => 0, // Will be updated by model event
            ]);

            // Each pesanan has 1-5 different produk
            $randomProduk = $produk->random(rand(1, 5));

            foreach ($randomProduk as $p) {
                $jumlah = rand(1, 10);
                $hargaSatuan = $p->harga_jual * $faker->randomFloat(2, 0.8, 1.2); // ±20% variation

                // Create PesananDetail records instead of using pivot
                \App\Models\PesananDetail::create([
                    'pesanan_id' => $pesanan->pesanan_id,
                    'produk_id' => $p->produk_id,
                    'jumlah_produk' => $jumlah,
                    'harga_satuan' => $hargaSatuan,
                    // subtotal will be auto-calculated by model
                ]);
            }
            // total_harga will be auto-updated by PesananDetail model event
        }
    }
}
