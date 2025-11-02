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
        Schema::create('pengadaan_detail', function (Blueprint $table) {
            $table->string('pengadaan_detail_id', 20)->primary();
            $table->string('pengadaan_id', 10);
            $table->string('pemasok_id', 10)->nullable();
            $table->enum('jenis_barang', ['bahan_baku', 'produk']); // Type of item being procured
            $table->string('barang_id', 10); // bahan_baku_id or produk_id
            $table->integer('qty_diminta'); // Quantity requested
            $table->integer('qty_disetujui')->nullable(); // Quantity approved
            $table->integer('qty_diterima')->default(0); // Quantity received
            $table->decimal('harga_satuan', 20, 2);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('pengadaan_id')->references('pengadaan_id')->on('pengadaan')->onDelete('cascade');
            $table->foreign('pemasok_id')->references('pemasok_id')->on('pemasok')->onDelete('cascade');

            // Index for better performance
            $table->index(['pengadaan_id', 'jenis_barang', 'barang_id']);
            $table->index(['jenis_barang', 'barang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengadaan_detail');
    }
};
