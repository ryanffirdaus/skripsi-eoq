<?php

namespace App\Console\Commands;

use App\Models\BahanBaku;
use App\Models\Produk;
use Illuminate\Console\Command;

class TestStockReduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:stock-reduction {--type=bahan_baku} {--id=} {--qty=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test stock reduction to trigger automatic pengadaan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $id = $this->option('id');
        $qty = (int) $this->option('qty');

        if ($type === 'bahan_baku') {
            $this->reduceBahanBakuStock($id, $qty);
        } elseif ($type === 'produk') {
            $this->reduceProdukStock($id, $qty);
        } else {
            $this->error('Invalid type. Use --type=bahan_baku or --type=produk');
            return 1;
        }

        return 0;
    }

    private function reduceBahanBakuStock($id = null, $qty = 10)
    {
        if ($id) {
            $bahanBaku = BahanBaku::find($id);
            if (!$bahanBaku) {
                $this->error("BahanBaku with ID {$id} not found");
                return;
            }
            $items = collect([$bahanBaku]);
        } else {
            // Get first bahan baku with stock
            $items = BahanBaku::where('stok_bahan', '>', 0)->limit(3)->get();
        }

        foreach ($items as $item) {
            $oldStock = $item->stok_bahan;
            $newStock = max(0, $oldStock - $qty);

            $this->info("Reducing stock for: {$item->nama_bahan}");
            $this->info("  Current stock: {$oldStock} {$item->satuan_bahan}");
            $this->info("  ROP: {$item->rop_bahan} {$item->satuan_bahan}");
            $this->info("  Safety Stock: {$item->safety_stock_bahan} {$item->satuan_bahan}");
            $this->info("  Threshold: " . ($item->rop_bahan + $item->safety_stock_bahan) . " {$item->satuan_bahan}");
            $this->info("  Reducing by: {$qty}");
            $this->info("  New stock will be: {$newStock} {$item->satuan_bahan}");

            if ($newStock <= ($item->rop_bahan + $item->safety_stock_bahan)) {
                $this->warn("  ⚠️  This will trigger automatic pengadaan!");
            }

            if ($this->confirm("Proceed with stock reduction?")) {
                $item->update(['stok_bahan' => $newStock]);
                $this->info("  ✓ Stock updated successfully");
            } else {
                $this->info("  ✗ Skipped");
            }

            $this->newLine();
        }
    }

    private function reduceProdukStock($id = null, $qty = 10)
    {
        if ($id) {
            $produk = Produk::find($id);
            if (!$produk) {
                $this->error("Produk with ID {$id} not found");
                return;
            }
            $items = collect([$produk]);
        } else {
            // Get first produk with stock
            $items = Produk::where('stok_produk', '>', 0)->limit(3)->get();
        }

        foreach ($items as $item) {
            $oldStock = $item->stok_produk;
            $newStock = max(0, $oldStock - $qty);

            $this->info("Reducing stock for: {$item->nama_produk}");
            $this->info("  Current stock: {$oldStock} {$item->satuan_produk}");
            $this->info("  ROP: {$item->rop_produk} {$item->satuan_produk}");
            $this->info("  Safety Stock: {$item->safety_stock_produk} {$item->satuan_produk}");
            $this->info("  Threshold: " . ($item->rop_produk + $item->safety_stock_produk) . " {$item->satuan_produk}");
            $this->info("  Reducing by: {$qty}");
            $this->info("  New stock will be: {$newStock} {$item->satuan_produk}");

            if ($newStock <= ($item->rop_produk + $item->safety_stock_produk)) {
                $this->warn("  ⚠️  This will trigger automatic pengadaan!");
            }

            if ($this->confirm("Proceed with stock reduction?")) {
                $item->update(['stok_produk' => $newStock]);
                $this->info("  ✓ Stock updated successfully");
            } else {
                $this->info("  ✗ Skipped");
            }

            $this->newLine();
        }
    }
}
