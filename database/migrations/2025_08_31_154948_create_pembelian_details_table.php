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
            $table->string('pembelian_detail_id')->primary();
            $table->string('pembelian_id');
            $table->string('pengadaan_detail_id'); // Reference to pengadaan detail
            $table->enum('item_type', ['bahan_baku', 'produk']);
            $table->string('item_id');
            $table->string('nama_item');
            $table->string('satuan');
            $table->integer('qty_po'); // Quantity in PO
            $table->integer('qty_diterima')->default(0); // Quantity received
            $table->decimal('harga_satuan', 12, 2);
            $table->decimal('total_harga', 15, 2);
            $table->text('spesifikasi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('pembelian_id')->references('pembelian_id')->on('pembelian')->onDelete('cascade');
            $table->foreign('pengadaan_detail_id')->references('pengadaan_detail_id')->on('pengadaan_detail')->onDelete('restrict');

            // Indexes
            $table->index(['pembelian_id', 'item_type', 'item_id']);
            $table->index('pengadaan_detail_id');
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
