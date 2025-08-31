<?php

namespace Database\Factories;

use App\Models\Pembelian;
use App\Models\Pengadaan;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pembelian>
 */
class PembelianFactory extends Factory
{
    protected $model = Pembelian::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tanggalPembelian = $this->faker->dateTimeBetween('-30 days', 'now');
        $tanggalJatuhTempo = $this->faker->dateTimeBetween($tanggalPembelian, '+60 days');

        $subtotal = $this->faker->numberBetween(1000000, 50000000);
        $pajak = $subtotal * 0.11; // PPN 11%
        $diskon = $this->faker->numberBetween(0, $subtotal * 0.1);
        $totalBiaya = $subtotal + $pajak - $diskon;

        $status = $this->faker->randomElement([
            'draft',
            'sent',
            'confirmed',
            'partial_received',
            'received',
            'invoiced',
            'paid'
        ]);

        $metodePembayaran = $this->faker->randomElement([
            'cash',
            'transfer',
            'credit',
            'cheque'
        ]);

        return [
            'pengadaan_id' => Pengadaan::inRandomOrder()->first()?->pengadaan_id ??
                Pengadaan::factory()->create()->pengadaan_id,
            'nomor_po' => 'PO-' . strtoupper(Str::random(8)),
            'tanggal_pembelian' => $tanggalPembelian,
            'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
            'supplier_id' => Supplier::inRandomOrder()->first()?->supplier_id ?? 'SUP001',
            'subtotal' => $subtotal,
            'pajak' => $pajak,
            'diskon' => $diskon,
            'total_biaya' => $totalBiaya,
            'status' => $status,
            'metode_pembayaran' => $metodePembayaran,
            'catatan' => $this->faker->optional()->sentence(),
            'created_by' => User::inRandomOrder()->first()?->user_id ?? 'US001',
        ];
    }

    /**
     * Create pembelian from specific pengadaan
     */
    public function fromPengadaan(Pengadaan $pengadaan): static
    {
        return $this->state(function (array $attributes) use ($pengadaan) {
            return [
                'pengadaan_id' => $pengadaan->pengadaan_id,
                'supplier_id' => $pengadaan->supplier_id,
                'subtotal' => $pengadaan->total_biaya,
                'pajak' => $pengadaan->total_biaya * 0.11,
                'diskon' => 0,
                'total_biaya' => $pengadaan->total_biaya * 1.11,
                'tanggal_pembelian' => now(),
                'tanggal_jatuh_tempo' => now()->addDays(30),
                'status' => 'draft',
                'created_by' => User::first()?->user_id ?? 'US001',
            ];
        });
    }

    /**
     * Set status to specific value
     */
    public function status(string $status): static
    {
        return $this->state(function (array $attributes) use ($status) {
            return [
                'status' => $status,
            ];
        });
    }

    /**
     * Set as draft status
     */
    public function draft(): static
    {
        return $this->status('draft');
    }

    /**
     * Set as sent status
     */
    public function sent(): static
    {
        return $this->status('sent');
    }

    /**
     * Set as confirmed status
     */
    public function confirmed(): static
    {
        return $this->status('confirmed');
    }

    /**
     * Set as received status
     */
    public function received(): static
    {
        return $this->status('received');
    }

    /**
     * Set as paid status
     */
    public function paid(): static
    {
        return $this->status('paid');
    }
}
