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
            $table->string('pengadaan_detail_id')->primary();
            $table->string('pengadaan_id');
            $table->string('pemasok_id')->nullable();
            $table->enum('item_type', ['bahan_baku', 'produk']); // Type of item being procured
            $table->string('item_id'); // bahan_baku_id or produk_id
            $table->string('nama_item'); // Denormalized for performance
            $table->string('satuan');
            $table->integer('qty_diminta'); // Quantity requested
            $table->integer('qty_disetujui')->nullable(); // Quantity approved
            $table->integer('qty_diterima')->default(0); // Quantity received
            $table->decimal('harga_satuan', 20, 2);
            $table->decimal('total_harga', 25, 2);
            $table->text('catatan')->nullable();
            $table->string('alasan_kebutuhan')->nullable(); // Why this item is needed (for ROP: "Stok di bawah ROP")
            $table->timestamps();

            $table->foreign('pengadaan_id')->references('pengadaan_id')->on('pengadaan')->onDelete('cascade');
            $table->foreign('pemasok_id')->references('pemasok_id')->on('pemasok')->onDelete('cascade');

            // Index for better performance
            $table->index(['pengadaan_id', 'item_type', 'item_id']);
            $table->index(['item_type', 'item_id']);
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
