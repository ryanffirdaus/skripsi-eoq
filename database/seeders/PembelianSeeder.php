<?php

namespace Database\Seeders;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Pengadaan;
use App\Models\PengadaanDetail;
use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PembelianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Get approved pengadaan that don't have pembelian yet
            $approvedPengadaan = Pengadaan::where('status', 'approved')
                ->whereDoesntHave('pembelian')
                ->with(['pengadaanDetails.bahanBaku'])
                ->limit(10)
                ->get();

            if ($approvedPengadaan->isEmpty()) {
                $this->command->info('No approved pengadaan found. Creating some first...');
                // If no approved pengadaan exists, create some
                $pengadaan = Pengadaan::factory()->count(5)->create(['status' => 'approved']);
                foreach ($pengadaan as $p) {
                    PengadaanDetail::factory()->count(rand(2, 5))->create([
                        'pengadaan_id' => $p->pengadaan_id
                    ]);
                }
                $approvedPengadaan = $pengadaan;
            }

            // Create pembelian from each approved pengadaan
            foreach ($approvedPengadaan as $pengadaan) {
                $this->command->info("Creating pembelian for pengadaan: {$pengadaan->pengadaan_id}");

                $pembelian = Pembelian::factory()
                    ->fromPengadaan($pengadaan)
                    ->create();

                // Create pembelian details for each pengadaan detail
                $this->command->info("  Found {$pengadaan->pengadaanDetails->count()} pengadaan details");
                foreach ($pengadaan->pengadaanDetails as $pengadaanDetail) {
                    $this->command->info("  Processing detail: {$pengadaanDetail->pengadaan_detail_id}");

                    // Calculate how much we can still order
                    $alreadyOrdered = PembelianDetail::where('pengadaan_detail_id', $pengadaanDetail->pengadaan_detail_id)
                        ->sum('qty_po');

                    $remainingQty = $pengadaanDetail->qty_disetujui - $alreadyOrdered;
                    $this->command->info("    Total qty: {$pengadaanDetail->qty_disetujui}, Already ordered: {$alreadyOrdered}, Remaining: {$remainingQty}");

                    if ($remainingQty > 0) {
                        // Order between 50% to 100% of remaining quantity
                        $qtyToOrder = min($remainingQty, rand(ceil($remainingQty * 0.5), $remainingQty));

                        $this->command->info("    Creating detail with qty: {$qtyToOrder}");

                        $detail = PembelianDetail::factory()
                            ->fromPengadaanDetail($pengadaanDetail, $qtyToOrder)
                            ->create([
                                'pembelian_id' => $pembelian->pembelian_id
                            ]);

                        $this->command->info("    Created detail: {$detail->pembelian_detail_id}");
                        $this->command->info("  - Ordered {$qtyToOrder} of {$pengadaanDetail->bahanBaku->nama_bahan}");
                    } else {
                        $this->command->info("    No remaining quantity to order");
                    }
                }

                // Recalculate pembelian totals based on details
                $pembelian->refresh();
                $subtotal = $pembelian->pembelianDetails->sum('total_harga');
                $pajak = $subtotal * 0.11;
                $totalBiaya = $subtotal + $pajak;

                $pembelian->update([
                    'subtotal' => $subtotal,
                    'pajak' => $pajak,
                    'total_biaya' => $totalBiaya,
                ]);
            }

            $this->command->info('PembelianSeeder completed successfully!');
        });
    }

    /**
     * Create pembelian from pengadaan with proper validations
     */
    private function createPembelianFromPengadaan(Pengadaan $pengadaan): void
    {
        // Buat pembelian dari pengadaan
        $pembelian = Pembelian::factory()
            ->fromPengadaan($pengadaan)
            ->create();

        // Hitung total untuk update pengadaan
        $totalBiaya = 0;

        // Buat detail pembelian dari detail pengadaan
        foreach ($pengadaan->detail as $pengadaanDetail) {
            // Cek apakah masih ada sisa yang bisa dipesan
            $qtyTelahDipesan = PembelianDetail::where('pengadaan_detail_id', $pengadaanDetail->pengadaan_detail_id)
                ->sum('qty_po');

            $sisaQty = $pengadaanDetail->qty_disetujui - $qtyTelahDipesan;

            if ($sisaQty > 0) {
                // Tentukan qty yang akan dipesan (bisa sebagian atau semua)
                $qtyPesan = fake()->boolean(80) ? $sisaQty : fake()->numberBetween(1, $sisaQty);

                try {
                    $detail = PembelianDetail::factory()
                        ->forPembelian($pembelian)
                        ->fromPengadaanDetail($pengadaanDetail, $qtyPesan)
                        ->create();

                    $totalBiaya += $detail->total_harga;

                    // Randomly set some items as received
                    if (fake()->boolean(30)) {
                        if (fake()->boolean(70)) {
                            // Fully received
                            $detail->update(['qty_diterima' => $detail->qty_po]);
                        } else {
                            // Partially received
                            $partialQty = fake()->numberBetween(1, $detail->qty_po - 1);
                            $detail->update(['qty_diterima' => $partialQty]);
                        }
                    }
                } catch (\Exception $e) {
                    // Skip jika tidak bisa membuat detail (kemungkinan sudah habis)
                    continue;
                }
            }
        }

        // Update total biaya pembelian
        $pajak = $totalBiaya * 0.11;
        $totalDenganPajak = $totalBiaya + $pajak;

        $pembelian->update([
            'subtotal' => $totalBiaya,
            'pajak' => $pajak,
            'total_biaya' => $totalDenganPajak,
        ]);

        // Update pengadaan dengan nomor PO
        $pengadaan->update([
            'nomor_po' => $pembelian->nomor_po,
        ]);

        // Set random status untuk variasi
        $statuses = ['draft', 'sent', 'confirmed', 'partial_received', 'received'];
        $randomStatus = fake()->randomElement($statuses);
        $pembelian->update(['status' => $randomStatus]);

        $this->command->info("Created pembelian {$pembelian->nomor_po} from pengadaan {$pengadaan->pengadaan_id}");
    }

    /**
     * Create standalone pembelian (not from pengadaan)
     */
    private function createStandalonePembelian(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $pembelian = Pembelian::factory()->create();

            // Buat 2-5 detail item per pembelian
            $detailCount = fake()->numberBetween(2, 5);
            $totalBiaya = 0;

            for ($j = 0; $j < $detailCount; $j++) {
                $detail = PembelianDetail::factory()
                    ->forPembelian($pembelian)
                    ->create();

                $totalBiaya += $detail->total_harga;

                // Randomly set some items as received
                if (fake()->boolean(40)) {
                    if (fake()->boolean(60)) {
                        $detail->fullyReceived()->save();
                    } else {
                        $detail->partiallyReceived()->save();
                    }
                }
            }

            // Update total biaya pembelian
            $pajak = $totalBiaya * 0.11;
            $totalDenganPajak = $totalBiaya + $pajak;

            $pembelian->update([
                'subtotal' => $totalBiaya,
                'pajak' => $pajak,
                'total_biaya' => $totalDenganPajak,
            ]);

            $this->command->info("Created standalone pembelian {$pembelian->nomor_po}");
        }
    }
}
