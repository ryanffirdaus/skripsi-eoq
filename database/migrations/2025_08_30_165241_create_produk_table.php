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
        Schema::create('produk', function (Blueprint $table) {
            $table->string('produk_id', 50)->primary();
            $table->string('nama_produk', 100);
            $table->integer('stok_produk');
            $table->string('satuan_produk', 10);
            $table->string('lokasi_produk', 100);
            $table->decimal('hpp_produk', 20, 2);
            $table->decimal('harga_jual', 20, 2);
            $table->integer('permintaan_harian_rata2_produk');
            $table->integer('permintaan_harian_maksimum_produk');
            $table->integer('waktu_tunggu_rata2_produk');
            $table->integer('waktu_tunggu_maksimum_produk');
            $table->integer('permintaan_tahunan');
            $table->decimal('biaya_pemesanan_produk', 20, 2);
            $table->decimal('biaya_penyimpanan_produk', 20, 2);
            $table->integer('safety_stock_produk');
            $table->integer('rop_produk');
            $table->integer('eoq_produk');
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->string('deleted_by', 50)->nullable();
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
        Schema::dropIfExists('produk');
    }
};
