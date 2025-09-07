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
        Schema::create('pembelian_detail', function (Blueprint $table) {
            $table->string('pembelian_detail_id', 11)->primary();

            // Foreign key ke header pembelian
            $table->string('pembelian_id', 10)->index();
            $table->foreign('pembelian_id')->references('pembelian_id')->on('pembelian')->onDelete('cascade');

            // Foreign key ke detail pengadaan untuk traceability
            $table->string('pengadaan_detail_id', 11)->index();
            $table->foreign('pengadaan_detail_id')->references('pengadaan_detail_id')->on('pengadaan_detail')->onDelete('restrict');

            // Kolom untuk item (bisa bahan baku atau produk)
            $table->string('item_type', 50);
            $table->string('item_id', 10);
            $table->string('nama_item');
            $table->string('satuan', 50);

            // Kuantitas
            $table->integer('qty_dipesan');
            $table->integer('qty_diterima')->default(0);

            // Harga
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('total_harga', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian_detail');
    }
};
