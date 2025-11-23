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
        Schema::create('bahan_baku', function (Blueprint $table) {
            $table->string('bahan_baku_id', 6)->primary(); // BB001
            $table->string('nama_bahan', 50);
            $table->integer('stok_bahan');
            $table->string('satuan_bahan', 10);
            $table->string('lokasi_bahan', 50);
            $table->decimal('harga_bahan', 15, 2);
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
            
            $table->string('dibuat_oleh', 6)->nullable();
            $table->string('diubah_oleh', 6)->nullable();
            $table->string('dihapus_oleh', 6)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('dibuat_oleh')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('diubah_oleh')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('dihapus_oleh')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_baku');
    }
};
