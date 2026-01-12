<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropColumn([
                'permintaan_harian_rata2_produk',
                'permintaan_harian_maksimum_produk',
                'waktu_tunggu_rata2_produk',
                'waktu_tunggu_maksimum_produk',
                'permintaan_tahunan',
                'biaya_pemesanan_produk',
                'biaya_penyimpanan_produk',
                'safety_stock_produk',
                'rop_produk',
                'eoq_produk',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->integer('permintaan_harian_rata2_produk');
            $table->integer('permintaan_harian_maksimum_produk');
            $table->integer('waktu_tunggu_rata2_produk');
            $table->integer('waktu_tunggu_maksimum_produk');
            $table->integer('permintaan_tahunan');
            $table->decimal('biaya_pemesanan_produk', 15, 2);
            $table->decimal('biaya_penyimpanan_produk', 15, 2);
            $table->integer('safety_stock_produk');
            $table->integer('rop_produk');
            $table->integer('eoq_produk');
        });
    }
};
