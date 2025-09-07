<?php

namespace Database\Factories;

use App\Models\PenerimaanDetail;
use App\Models\PenerimaanBahanBaku;
use App\Models\PembelianDetail;
use App\Models\BahanBaku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PenerimaanDetail>
 */
class PenerimaanDetailFactory extends Factory
{
    protected $model = PenerimaanDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qtyOrdered = $this->faker->numberBetween(10, 100);
        $qtyReceived = $this->faker->numberBetween(0, $qtyOrdered);

        // Quality check simulation
        $qualityPassRate = $this->faker->numberBetween(70, 100) / 100;
        $qtyAccepted = (int) floor($qtyReceived * $qualityPassRate);
        $qtyRejected = $qtyReceived - $qtyAccepted;

        $statusQuality = $this->determineQualityStatus($qtyReceived, $qtyAccepted, $qtyRejected);
        $hargaSatuan = $this->faker->numberBetween(5000, 100000);

        return [
            'penerimaan_id' => function () {
                return PenerimaanBahanBaku::factory()->create()->penerimaan_id;
            },
            'pembelian_detail_id' => function () {
                return PembelianDetail::inRandomOrder()->first()?->pembelian_detail_id ??
                    PembelianDetail::factory()->create()->pembelian_detail_id;
            },
            'bahan_baku_id' => function (array $attributes) {
                $pembelianDetail = PembelianDetail::find($attributes['pembelian_detail_id']);
                return $pembelianDetail ? $pembelianDetail->item_id : BahanBaku::factory()->create()->bahan_baku_id;
            },
            'nama_bahan' => function (array $attributes) {
                $bahanBaku = BahanBaku::find($attributes['bahan_baku_id']);
                return $bahanBaku ? $bahanBaku->nama_bahan : $this->faker->word();
            },
            'satuan' => function (array $attributes) {
                $bahanBaku = BahanBaku::find($attributes['bahan_baku_id']);
                return $bahanBaku->satuan ?? $this->faker->randomElement(['kg', 'pcs', 'liter', 'meter']);
            },
            'qty_ordered' => $qtyOrdered,
            'qty_received' => $qtyReceived,
            'qty_accepted' => $qtyAccepted,
            'qty_rejected' => $qtyRejected,
            'status_quality' => $statusQuality,
            'catatan_quality' => $qtyRejected > 0 ? $this->faker->sentence() : null,
            'harga_satuan' => $hargaSatuan,
            'total_diterima' => $qtyAccepted * $hargaSatuan,
        ];
    }

    /**
     * Create detail from specific pembelian detail
     */
    public function fromPembelianDetail(PembelianDetail $pembelianDetail): static
    {
        return $this->state(function (array $attributes) use ($pembelianDetail) {
            $qtyOrdered = $pembelianDetail->qty_po;
            $qtyReceived = $this->faker->numberBetween(
                (int) ceil($qtyOrdered * 0.8),
                $qtyOrdered
            );

            // Quality check with realistic rates
            $qualityPassRate = $this->faker->numberBetween(85, 100) / 100;
            $qtyAccepted = (int) floor($qtyReceived * $qualityPassRate);
            $qtyRejected = $qtyReceived - $qtyAccepted;

            $statusQuality = $this->determineQualityStatus($qtyReceived, $qtyAccepted, $qtyRejected);

            return [
                'pembelian_detail_id' => $pembelianDetail->pembelian_detail_id,
                'bahan_baku_id' => $pembelianDetail->item_id,
                'nama_bahan' => $pembelianDetail->nama_item,
                'satuan' => $pembelianDetail->satuan,
                'qty_ordered' => $qtyOrdered,
                'qty_received' => $qtyReceived,
                'qty_accepted' => $qtyAccepted,
                'qty_rejected' => $qtyRejected,
                'status_quality' => $statusQuality,
                'harga_satuan' => $pembelianDetail->harga_satuan,
                'total_diterima' => $qtyAccepted * $pembelianDetail->harga_satuan,
            ];
        });
    }

    /**
     * Set as fully received and passed quality
     */
    public function fullyReceived(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'qty_received' => $attributes['qty_ordered'],
                'qty_accepted' => $attributes['qty_ordered'],
                'qty_rejected' => 0,
                'status_quality' => 'passed',
                'catatan_quality' => null,
            ];
        });
    }

    /**
     * Set as partially received
     */
    public function partiallyReceived(): static
    {
        return $this->state(function (array $attributes) {
            $qtyReceived = (int) floor($attributes['qty_ordered'] * 0.7);
            $qtyAccepted = (int) floor($qtyReceived * 0.9);
            $qtyRejected = $qtyReceived - $qtyAccepted;

            return [
                'qty_received' => $qtyReceived,
                'qty_accepted' => $qtyAccepted,
                'qty_rejected' => $qtyRejected,
                'status_quality' => $qtyRejected > 0 ? 'partial' : 'passed',
                'catatan_quality' => $qtyRejected > 0 ? 'Sebagian barang tidak sesuai spesifikasi' : null,
            ];
        });
    }

    /**
     * Set as having quality issues
     */
    public function withQualityIssues(): static
    {
        return $this->state(function (array $attributes) {
            $qtyReceived = $attributes['qty_ordered'];
            $qtyAccepted = (int) floor($qtyReceived * 0.6); // 60% pass rate
            $qtyRejected = $qtyReceived - $qtyAccepted;

            return [
                'qty_received' => $qtyReceived,
                'qty_accepted' => $qtyAccepted,
                'qty_rejected' => $qtyRejected,
                'status_quality' => 'partial',
                'catatan_quality' => 'Ditemukan cacat pada ' . $qtyRejected . ' unit barang',
            ];
        });
    }

    /**
     * Determine quality status based on quantities
     */
    private function determineQualityStatus(int $qtyReceived, int $qtyAccepted, int $qtyRejected): string
    {
        if ($qtyReceived == 0) {
            return 'pending';
        } elseif ($qtyRejected == 0) {
            return 'passed';
        } elseif ($qtyAccepted == 0) {
            return 'failed';
        } else {
            return 'partial';
        }
    }

    public function goodQuality(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status_quality' => 'passed',
                'qty_rejected' => 0,
                'catatan_quality' => null,
            ];
        });
    }

    public function withMajorQualityIssues(): static
    {
        return $this->state(function (array $attributes) {
            $qtyReceived = $attributes['qty_ordered'];
            $qtyAccepted = (int) floor($qtyReceived * 0.4); // 40% pass rate
            $qtyRejected = $qtyReceived - $qtyAccepted;

            return [
                'qty_received' => $qtyReceived,
                'qty_accepted' => $qtyAccepted,
                'qty_rejected' => $qtyRejected,
                'status_quality' => 'failed',
                'catatan_quality' => 'Ditemukan cacat pada ' . $qtyRejected . ' unit barang',
            ];
        });
    }
}
