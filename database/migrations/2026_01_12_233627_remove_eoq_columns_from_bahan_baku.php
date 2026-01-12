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
        Schema::table('bahan_baku', function (Blueprint $table) {
            $table->dropColumn([
                'permintaan_harian_rata2_bahan',
                'permintaan_harian_maksimum_bahan',
                'waktu_tunggu_rata2_bahan',
                'waktu_tunggu_maksimum_bahan',
                'permintaan_tahunan',
                'biaya_pemesanan_bahan',
                'biaya_penyimpanan_bahan',
                'safety_stock_bahan',
                'rop_bahan',
                'eoq_bahan',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_baku', function (Blueprint $table) {
            $table->integer('permintaan_harian_rata2_bahan');
            $table->integer('permintaan_harian_maksimum_bahan');
            $table->integer('waktu_tunggu_rata2_bahan');
            $table->integer('waktu_tunggu_maksimum_bahan');
            $table->integer('permintaan_tahunan');
            $table->decimal('biaya_pemesanan_bahan', 15, 2);
            $table->decimal('biaya_penyimpanan_bahan', 15, 2);
            $table->integer('safety_stock_bahan');
            $table->integer('rop_bahan');
            $table->integer('eoq_bahan');
        });
    }
};
