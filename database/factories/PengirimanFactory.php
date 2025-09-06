<?php

namespace Database\Factories;

use App\Models\Pengiriman;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pengiriman>
 */
class PengirimanFactory extends Factory
{
    protected $model = Pengiriman::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kurirOptions = ['JNE', 'J&T', 'TIKI', 'POS Indonesia', 'SiCepat', 'AnterAja', 'Gojek'];
        $selectedKurir = $this->faker->randomElement($kurirOptions);

        $status = $this->faker->randomElement(['pending', 'shipped', 'delivered']);
        $tanggalKirim = $status !== 'pending' ? $this->faker->dateTimeBetween('-7 days', 'now') : null;
        $tanggalDiterima = $status === 'delivered' && $tanggalKirim ?
            $this->faker->dateTimeBetween($tanggalKirim, '+3 days') : null;

        return [
            'pesanan_id' => Pesanan::factory(),
            'nomor_resi' => $this->faker->optional(0.8)->numerify('##########'),
            'kurir' => $selectedKurir,
            'biaya_pengiriman' => $this->faker->numberBetween(10000, 50000),
            'estimasi_hari' => $this->faker->numberBetween(1, 5),
            'status' => $status,
            'tanggal_kirim' => $tanggalKirim,
            'tanggal_diterima' => $tanggalDiterima,
            'catatan' => $this->faker->optional(0.3)->sentence(),
            'created_by' => User::factory(),
            'updated_by' => function (array $attributes) {
                return $attributes['created_by'];
            },
        ];
    }

    /**
     * Status pending state
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'tanggal_kirim' => null,
            'tanggal_diterima' => null,
            'nomor_resi' => null,
        ]);
    }

    /**
     * Status shipped state
     */
    public function shipped(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'shipped',
            'tanggal_kirim' => $this->faker->dateTimeBetween('-3 days', 'now'),
            'tanggal_diterima' => null,
            'nomor_resi' => $this->faker->numerify('##########'),
        ]);
    }

    /**
     * Status delivered state
     */
    public function delivered(): static
    {
        $tanggalKirim = $this->faker->dateTimeBetween('-7 days', '-2 days');

        return $this->state(fn(array $attributes) => [
            'status' => 'delivered',
            'tanggal_kirim' => $tanggalKirim,
            'tanggal_diterima' => $this->faker->dateTimeBetween($tanggalKirim, 'now'),
            'nomor_resi' => $this->faker->numerify('##########'),
        ]);
    }

    /**
     * Status cancelled state
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'cancelled',
            'tanggal_kirim' => null,
            'tanggal_diterima' => null,
            'nomor_resi' => $this->faker->optional(0.3)->numerify('##########'),
        ]);
    }
}
