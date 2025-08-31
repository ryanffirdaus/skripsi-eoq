<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\BahanBaku;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 20 produk records
        $produk = Produk::factory(20)->create();

        // Attach random bahan baku to each produk
        $bahanBakus = BahanBaku::all();

        foreach ($produk as $p) {
            // Each produk uses 3-8 different bahan baku
            $randomBahanBakus = $bahanBakus->random(rand(3, 8));

            foreach ($randomBahanBakus as $bahanBaku) {
                $p->bahanBaku()->attach($bahanBaku->bahan_baku_id, [
                    'jumlah_bahan_baku' => rand(1, 10)
                ]);
            }
        }
    }
}
