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
        static $counter = 1;

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
        $hpp_produk = $this->faker->numberBetween(50000, 500000);
        $harga_jual = $hpp_produk * $this->faker->randomFloat(2, 1.2, 1.8);

        return [
            'produk_id' => 'PP' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'nama_produk' => $nama_produk,
            'stok_produk' => $stok_produk,
            'satuan_produk' => $this->faker->randomElement($units),
            'lokasi_produk' => $this->faker->randomElement($locations),
            'hpp_produk' => $hpp_produk,
            'harga_jual' => $harga_jual,
        ];
    }
}
