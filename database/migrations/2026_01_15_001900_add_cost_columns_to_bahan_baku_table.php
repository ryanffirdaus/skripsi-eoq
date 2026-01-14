<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add cost parameter columns for automatic EOQ/ROP/SS calculation
     */
    public function up(): void
    {
        Schema::table('bahan_baku', function (Blueprint $table) {
            // Ordering cost (average procurement cost per order)
            // Auto-updated from pengadaan.biaya_pemesanan (rolling average)
            $table->decimal('biaya_pemesanan_per_order', 15, 2)
                  ->default(50000)
                  ->after('harga_bahan')
                  ->comment('Average ordering cost per order (auto-updated from transactions)');
            
            // Holding cost as percentage of item value per year
            // Auto-updated based on item price changes and characteristics
            $table->decimal('biaya_penyimpanan_persen', 5, 2)
                  ->default(20.00)
                  ->after('biaya_pemesanan_per_order')
                  ->comment('Annual holding cost as % of item price (auto-calibrated)');
        });
        
        Schema::table('produk', function (Blueprint $table) {
            // Same for produk
            $table->decimal('biaya_pemesanan_per_order', 15, 2)
                  ->default(50000)
                  ->after('hpp_produk')
                  ->comment('Average ordering cost per order (auto-updated from transactions)');
            
            $table->decimal('biaya_penyimpanan_persen', 5, 2)
                  ->default(20.00)
                  ->after('biaya_pemesanan_per_order')
                  ->comment('Annual holding cost as % of item price (auto-calibrated)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_baku', function (Blueprint $table) {
            $table->dropColumn(['biaya_pemesanan_per_order', 'biaya_penyimpanan_persen']);
        });
        
        Schema::table('produk', function (Blueprint $table) {
            $table->dropColumn(['biaya_pemesanan_per_order', 'biaya_penyimpanan_persen']);
        });
    }
};
