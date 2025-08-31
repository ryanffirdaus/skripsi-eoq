<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Produk>
 */
class ProdukFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $iotProducts = [
            'Smart Home Security System',
            'IoT Weather Monitoring Station',
            'Smart Plant Watering System',
            'Home Automation Controller',
            'Smart Door Lock System',
            'IoT Temperature Monitor',
            'Smart Lighting Controller',
            'GPS Vehicle Tracker',
            'Smart Irrigation System',
            'Home Energy Monitor',
            'Smart Smoke Detector',
            'IoT Air Quality Monitor',
            'Smart Pet Feeder',
            'Automated Greenhouse Controller',
            'Smart Parking Sensor',
            'IoT Fire Alarm System',
            'Smart Water Level Monitor',
            'Home Security Camera System',
            'Smart Motion Detector',
            'IoT Environmental Sensor',
        ];

        $locations = ['Workshop A', 'Workshop B', 'Assembly Line 1', 'Assembly Line 2', 'Quality Control'];
        $units = ['unit', 'set', 'kit'];

        $nama_produk = $this->faker->randomElement($iotProducts);
        $stok_produk = $this->faker->numberBetween(5, 100);
        $hpp_produk = $this->faker->numberBetween(200000, 2000000);
        $harga_jual = $hpp_produk * $this->faker->randomFloat(2, 1.2, 2.5); // Markup 20% - 150%

        $permintaan_harian_rata2 = $this->faker->numberBetween(1, 5);
        $permintaan_harian_max = $permintaan_harian_rata2 + $this->faker->numberBetween(1, 3);
        $waktu_tunggu_rata2 = $this->faker->numberBetween(5, 10);
        $waktu_tunggu_max = $waktu_tunggu_rata2 + $this->faker->numberBetween(2, 5);
        $permintaan_tahunan = $permintaan_harian_rata2 * 365;
        $biaya_pemesanan = $this->faker->numberBetween(100000, 500000);
        $biaya_penyimpanan = $hpp_produk * 0.25; // 25% dari HPP

        // Hitung EOQ: √((2 * D * S) / H)
        $eoq = sqrt((2 * $permintaan_tahunan * $biaya_pemesanan) / $biaya_penyimpanan);

        // Hitung ROP: (d * L) + SS
        $safety_stock = $permintaan_harian_max * $waktu_tunggu_max - $permintaan_harian_rata2 * $waktu_tunggu_rata2;
        $rop = ($permintaan_harian_rata2 * $waktu_tunggu_rata2) + $safety_stock;

        return [
            'nama_produk' => $nama_produk,
            'stok_produk' => $stok_produk,
            'satuan_produk' => $this->faker->randomElement($units),
            'lokasi_produk' => $this->faker->randomElement($locations),
            'hpp_produk' => $hpp_produk,
            'harga_jual' => $harga_jual,
            'permintaan_harian_rata2_produk' => $permintaan_harian_rata2,
            'permintaan_harian_maksimum_produk' => $permintaan_harian_max,
            'waktu_tunggu_rata2_produk' => $waktu_tunggu_rata2,
            'waktu_tunggu_maksimum_produk' => $waktu_tunggu_max,
            'permintaan_tahunan' => $permintaan_tahunan,
            'biaya_pemesanan_produk' => $biaya_pemesanan,
            'biaya_penyimpanan_produk' => $biaya_penyimpanan,
            'safety_stock_produk' => max(0, $safety_stock),
            'rop_produk' => max(0, $rop),
            'eoq_produk' => max(1, round($eoq)),
        ];
    }
}
