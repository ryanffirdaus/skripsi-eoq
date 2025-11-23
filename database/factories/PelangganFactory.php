<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pelanggan>
 */
class PelangganFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 1;

        $companies = [
            'PT Teknologi Maju',
            'CV Smart Solutions',
            'PT IoT Indonesia',
            'CV Digital Nusantara',
            'PT Automation Systems',
            'CV Smart Tech',
            'PT Innovation Labs',
            'CV Future Technology',
            'PT Robotics Indonesia',
            'CV Electronic Solutions',
        ];

        $cities = ['Jakarta', 'Bandung', 'Surabaya', 'Yogyakarta', 'Semarang', 'Medan', 'Makassar', 'Palembang'];

        $isCompany = $this->faker->boolean(70); // 70% chance company, 30% individual

        if ($isCompany) {
            $nama = $this->faker->randomElement($companies);
        } else {
            $nama = $this->faker->name();
        }

        $city = $this->faker->randomElement($cities);
        $alamat_pembayaran = $this->faker->streetAddress() . ', ' . $city . ' ' . $this->faker->postcode();
        $alamat_pengiriman = $this->faker->boolean(80) ? $alamat_pembayaran : $this->faker->streetAddress() . ', ' . $city . ' ' . $this->faker->postcode();

        return [
            'pelanggan_id' => 'PL' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'nama_pelanggan' => $nama,
            'email_pelanggan' => $this->faker->unique()->safeEmail(),
            'nomor_telepon' => $this->faker->phoneNumber(),
            'alamat_pembayaran' => $alamat_pembayaran,
            'alamat_pengiriman' => $alamat_pengiriman,
        ];
    }
}
