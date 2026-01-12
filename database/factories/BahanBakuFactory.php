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

        return [
            'bahan_baku_id' => 'BB' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'nama_bahan' => $nama_bahan,
            'stok_bahan' => $stok_bahan,
            'satuan_bahan' => $this->faker->randomElement($units),
            'lokasi_bahan' => $this->faker->randomElement($locations),
            'harga_bahan' => $harga_bahan,
        ];
    }
}
