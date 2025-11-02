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
            $table->string('bahan_baku_id', 10)->primary();
            $table->string('nama_bahan', 100);
            $table->integer('stok_bahan');
            $table->string('satuan_bahan', 10);
            $table->string('lokasi_bahan', 100);
            $table->decimal('harga_bahan', 20, 2);
            $table->integer('permintaan_harian_rata2_bahan');
            $table->integer('permintaan_harian_maksimum_bahan');
            $table->integer('waktu_tunggu_rata2_bahan');
            $table->integer('waktu_tunggu_maksimum_bahan');
            $table->integer('permintaan_tahunan');
            $table->decimal('biaya_pemesanan_bahan', 20, 2);
            $table->decimal('biaya_penyimpanan_bahan', 20, 2);
            $table->integer('safety_stock_bahan');
            $table->integer('rop_bahan');
            $table->integer('eoq_bahan');
            $table->string('created_by', 10)->nullable();
            $table->string('updated_by', 10)->nullable();
            $table->string('deleted_by', 10)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('user_id')->on('users')->onDelete('set null');
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
