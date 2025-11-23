<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BahanBaku>
 */
class BahanBakuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 1;

        $iotComponents = [
            'Arduino Uno R3',
            'Raspberry Pi 4',
            'ESP32 DevKit',
            'NodeMCU ESP8266',
            'Sensor DHT22',
            'Sensor Ultrasonik HC-SR04',
            'Servo Motor SG90',
            'LED RGB 5mm',
            'Resistor 220 Ohm',
            'Resistor 10K Ohm',
            'Breadboard 830 Point',
            'Jumper Wire Male-Female',
            'Jumper Wire Male-Male',
            'LCD Display 16x2',
            'OLED Display 0.96"',
            'Sensor PIR Motion',
            'Sensor LDR',
            'Modul Relay 1 Channel',
            'Modul Relay 4 Channel',
            'Sensor Gas MQ-2',
            'Sensor Suhu DS18B20',
            'GPS Module NEO-6M',
            'Bluetooth Module HC-05',
            'WiFi Module ESP01',
            'Buzzer 5V',
        ];

        $locations = ['Gudang A', 'Gudang B', 'Rak 1', 'Rak 2', 'Storage Room'];
        $units = ['pcs', 'set', 'pack', 'box'];

        $nama_bahan = $this->faker->randomElement($iotComponents);
        $stok_bahan = $this->faker->numberBetween(10, 500);
        $harga_bahan = $this->faker->numberBetween(5000, 500000);
        $permintaan_harian_rata2 = $this->faker->numberBetween(1, 10);
        $permintaan_harian_max = $permintaan_harian_rata2 + $this->faker->numberBetween(2, 5);
        $waktu_tunggu_rata2 = $this->faker->numberBetween(3, 7);
        $waktu_tunggu_max = $waktu_tunggu_rata2 + $this->faker->numberBetween(1, 3);
        $permintaan_tahunan = $permintaan_harian_rata2 * 365;
        $biaya_pemesanan = $this->faker->numberBetween(50000, 200000);
        $biaya_penyimpanan = $harga_bahan * 0.2; // 20% dari harga

        // Hitung EOQ: √((2 * D * S) / H)
        $eoq = sqrt((2 * $permintaan_tahunan * $biaya_pemesanan) / $biaya_penyimpanan);

        // Hitung ROP: (d * L) + SS
        $safety_stock = $permintaan_harian_max * $waktu_tunggu_max - $permintaan_harian_rata2 * $waktu_tunggu_rata2;
        $rop = ($permintaan_harian_rata2 * $waktu_tunggu_rata2) + $safety_stock;

        return [
            'bahan_baku_id' => 'BB' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'nama_bahan' => $nama_bahan,
            'stok_bahan' => $stok_bahan,
            'satuan_bahan' => $this->faker->randomElement($units),
            'lokasi_bahan' => $this->faker->randomElement($locations),
            'harga_bahan' => $harga_bahan,
            'permintaan_harian_rata2_bahan' => $permintaan_harian_rata2,
            'permintaan_harian_maksimum_bahan' => $permintaan_harian_max,
            'waktu_tunggu_rata2_bahan' => $waktu_tunggu_rata2,
            'waktu_tunggu_maksimum_bahan' => $waktu_tunggu_max,
            'permintaan_tahunan' => $permintaan_tahunan,
            'biaya_pemesanan_bahan' => $biaya_pemesanan,
            'biaya_penyimpanan_bahan' => $biaya_penyimpanan,
            'safety_stock_bahan' => max(0, $safety_stock),
            'rop_bahan' => max(0, $rop),
            'eoq_bahan' => max(1, round($eoq)),
        ];
    }
}
