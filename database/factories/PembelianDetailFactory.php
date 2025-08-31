<?php

namespace Database\Factories;

use App\Models\PembelianDetail;
use App\Models\Pembelian;
use App\Models\PengadaanDetail;
use App\Models\BahanBaku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PembelianDetail>
 */
class PembelianDetailFactory extends Factory
{
    protected $model = PembelianDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qtyPo = $this->faker->numberBetween(1, 100);
        $qtyDiterima = $this->faker->numberBetween(0, $qtyPo);
        $hargaSatuan = $this->faker->numberBetween(1000, 100000);
        $totalHarga = $qtyPo * $hargaSatuan;

        return [
            'pembelian_id' => function () {
                return Pembelian::factory()->create()->pembelian_id;
            },
            'pengadaan_detail_id' => null, // Will be set when creating from pengadaan
            'item_type' => 'bahan_baku',
            'item_id' => function () {
                return BahanBaku::inRandomOrder()->first()?->bahan_baku_id ??
                    BahanBaku::factory()->create()->bahan_baku_id;
            },
            'nama_item' => function (array $attributes) {
                $bahanBaku = BahanBaku::find($attributes['item_id']);
                return $bahanBaku ? $bahanBaku->nama_bahan : $this->faker->word();
            },
            'satuan' => function (array $attributes) {
                $bahanBaku = BahanBaku::find($attributes['item_id']);
                return $bahanBaku ? $bahanBaku->satuan : $this->faker->randomElement(['kg', 'pcs', 'liter', 'meter']);
            },
            'qty_po' => $qtyPo,
            'qty_diterima' => $qtyDiterima,
            'harga_satuan' => $hargaSatuan,
            'total_harga' => $totalHarga,
            'spesifikasi' => $this->faker->optional()->sentence(),
            'catatan' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Create detail from specific pengadaan detail
     */
    public function fromPengadaanDetail(PengadaanDetail $pengadaanDetail, int $qtyPo = null): static
    {
        return $this->state(function (array $attributes) use ($pengadaanDetail, $qtyPo) {
            $qty = $qtyPo ?? $pengadaanDetail->qty_disetujui;

            // Pastikan qty tidak melebihi qty yang disetujui
            $qty = min($qty, $pengadaanDetail->qty_disetujui);

            // Hitung qty yang sudah dipesan sebelumnya
            $qtyTelahDipesan = PembelianDetail::where('pengadaan_detail_id', $pengadaanDetail->pengadaan_detail_id)
                ->sum('qty_po');

            // Pastikan total tidak melebihi qty yang disetujui
            $sisaQty = $pengadaanDetail->qty_disetujui - $qtyTelahDipesan;
            $qty = min($qty, $sisaQty);

            if ($qty <= 0) {
                throw new \Exception("Tidak ada sisa kuantitas untuk item {$pengadaanDetail->nama_item}");
            }

            return [
                'pengadaan_detail_id' => $pengadaanDetail->pengadaan_detail_id,
                'item_type' => $pengadaanDetail->item_type,
                'item_id' => $pengadaanDetail->item_id,
                'nama_item' => $pengadaanDetail->nama_item,
                'satuan' => $pengadaanDetail->satuan,
                'qty_po' => $qty,
                'qty_diterima' => 0, // Belum diterima saat baru dibuat
                'harga_satuan' => $pengadaanDetail->harga_satuan,
                'total_harga' => $qty * $pengadaanDetail->harga_satuan,
                'spesifikasi' => $pengadaanDetail->alasan_kebutuhan,
                'catatan' => null,
            ];
        });
    }

    /**
     * Set specific pembelian
     */
    public function forPembelian(Pembelian $pembelian): static
    {
        return $this->state(function (array $attributes) use ($pembelian) {
            return [
                'pembelian_id' => $pembelian->pembelian_id,
            ];
        });
    }

    /**
     * Set received quantity
     */
    public function received(int $qtyDiterima = null): static
    {
        return $this->state(function (array $attributes) use ($qtyDiterima) {
            $qty = $qtyDiterima ?? $attributes['qty_po'];
            return [
                'qty_diterima' => min($qty, $attributes['qty_po']),
            ];
        });
    }

    /**
     * Set as fully received
     */
    public function fullyReceived(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'qty_diterima' => $attributes['qty_po'],
            ];
        });
    }

    /**
     * Set as partially received
     */
    public function partiallyReceived(): static
    {
        return $this->state(function (array $attributes) {
            $partialQty = max(1, intval($attributes['qty_po'] * 0.5));
            return [
                'qty_diterima' => $partialQty,
            ];
        });
    }
}
